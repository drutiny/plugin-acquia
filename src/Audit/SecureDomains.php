<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\AuditValidationException;

/**
 * Ensure an environment has custom domains set.
 */
class SecureDomains extends Audit {

  protected function requireCloudApiV2()
  {
    return Manager::load('acquia_api_v2');
  }

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $environment = CloudApiDrushAdaptor::getEnvironment($sandbox->getTarget());

    // Check each IP associated with the "front" of the environment.
    $ssl_domains = $environment['ips'];

    // Default domain will point at either the Acquia balancers or the ELB.
    $ssl_domains[] = $environment['default_domain'];

    // Build a list of domains that "should" be secured by SSL certed hosted in
    // $ssl_domains with the exception of domains hosted at the edge.
    $domains = array_filter($environment['domains'], function ($domain) {
      // Do not include ELB domains or Acquia default domains.
      return !(strpos($domain, 'acquia-sites.com') || strpos($domain, 'elb.amazonaws.com') || strpos($domain, 'acsitefactory.com'));
    });

    $domain_details = [];
    foreach ($domains as $domain) {
      $domain_details[$domain] = [
        'name' => $domain,
        'secure' => FALSE,
        'certificate' => FALSE,
        'host' => FALSE,
      ];
    }
    $checked_certs = [];

    foreach ($ssl_domains as $host) {
      foreach ($domains as $sni_domain) {
        // No need to try any further certs for SNI domain if already confirmed
        // as secure.
        if ($domain_details[$sni_domain]['secure']) {
          continue;
        }

        try {
          $cert = $this->getCertificate($host, $sni_domain);
        }
        catch (\Exception $e) {
          $sandbox->logger()->warning($e->getMessage());
          continue;
        }

        // No need to check the same cert if we've picked it up from somewhere else.
        if (in_array($cert['extensions']['subjectKeyIdentifier'], $checked_certs)) {
          continue;
        }
        $checked_certs[] = $cert['extensions']['subjectKeyIdentifier'];

        // SANs are the only places where we can check for the domain.
        if (!isset($cert['extensions']['subjectAltName'])) {
          continue;
        }

        $san = $cert['extensions']['subjectAltName'];

        $secured_domains = array_filter($domains, function ($domain) use ($san) {
          return preg_match("/DNS:$domain(,|$)/", $san);
        });

        foreach ($secured_domains as $domain) {
          $domain_details[$domain]['secure'] = TRUE;
          $domain_details[$domain]['certificate'] = strtr('CN - O', $cert['subject']);
          $domain_details[$domain]['host'] = $host;
        }
      }
    }

    $insecured = array_filter($domain_details, function ($domain) {
      return !$domain['secure'];
    });

    $this->set('domains', array_values($domain_details));

    if (count($insecured) == count($domain_details)) {
      return FALSE;
    }

    return count($insecured) ? Audit::WARNING : TRUE;
  }

  protected function getCertificate($host, $sni_domain)
  {
    $url = 'ssl://' . $host . ':443';

    $context = stream_context_create([
      "ssl" => [
        "capture_peer_cert" => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'SNI_enabled' => true,
        'SNI_server_name' => $sni_domain,
      ]]);
    if (!$client = @stream_socket_client($url, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context)) {
      throw new \Exception("$host (SNI: $sni_domain) did not accept an SSL connection on port $port: [$errno] $errstr");
    }

    $cert = stream_context_get_params($client);
    $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

    $certinfo['issued'] = date('Y-m-d H:i:s', $certinfo['validFrom_time_t']);
    return $certinfo;
  }

}
