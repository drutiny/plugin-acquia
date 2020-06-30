<?php

namespace Drutiny\Acquia\DomainList;

use Drutiny\Acquia\CloudApiV2;
use Drutiny\Credential\CredentialsUnavailableException;
use Drutiny\Credential\Manager;
use Drutiny\DomainList\DomainListInterface;
use Drutiny\Http\Client;
use Drutiny\Policy;
use Drutiny\Target\TargetInterface;
use Drutiny\DomainList\AbstractDomainList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Param(
 *   name = "custom-only",
 *   description = "Boolean indicator to use only custom domains.",
 * )
 */
class AcquiaCloudDomainList extends AbstractDomainList implements DomainListInterface {

  public function __construct(ContainerInterface $container, TargetInterface $target)
  {
    $this->target = $target;
    $this->credentials = $container->get('credentials')->getConfig('acquia.api.v2');
  }



  /**
   * @return array list of domains.
   */
  public function getDomains(array $options = [])
  {
    $domains = $this->loadDomains();
    return array_filter($domains, function ($domain) use ($options) {
      return !$options['custom-only'] || !(strpos($domain, 'acquia-sites.com') || strpos($domain, 'elb.amazonaws.com') || strpos($domain, 'acsitefactory.com'));
    });
  }

  protected function loadDomains()
  {
    try {
      return $this->loadDomainsFromApiV2();
    }
    catch (CredentialsUnavailableException $e) {}

    try {
      $creds = Manager::load('acquia_api_v1');
      return $this->loadDomainsFromApiV1($creds, $target);
    }
    catch (CredentialsUnavailableException $e) {}

    if (file_exists(getenv('HOME') . '/.acquia/cloudapi.conf')) {
      $creds = json_decode(file_get_contents(getenv('HOME') . '/.acquia/cloudapi.conf'), TRUE);
      return $this->loadDomainsFromApiV1($creds, $target);
    }

    return [];
  }

  protected function loadDomainsFromApiV1($creds, Target $target)
  {
    $client = new Client([
      'base_uri' => 'https://cloudapi.acquia.com/v1/',
      'auth' => [$creds['email'], $creds['key']],
    ]);

    $options = $target->getOptions();

    $path = implode('/', [
      'sites',
      $options['ac-realm'] . ':'  . $options['ac-site'],
      'envs',
      $options['ac-env'],
      'domains.json',
    ]);

    $response = $client->request('GET', $path );
    $sites = json_decode($response->getBody(), TRUE);

    $domains = [];
    foreach ($sites as $site) {
      // Exclude wildcards.
      if (strpos($site['name'], '*') !== FALSE) {
        continue;
      }
      $domains[] = $site['name'];
    }
    return $domains;
  }

  protected function loadDomainsFromApiV2()
  {

    if ($this->target instanceof AcquiaTargetInterface) {
      return $this->target->getEnvironment()['domains'];
    }
    $apps = CloudApiV2::get('applications');
    $options = $this->target->getOptions();

    foreach ($apps['_embedded']['items'] as $app) {
      if (empty($app['hosting'])) {
        continue;
      }
      list($stage, $sitegroup) = explode(':', $app['hosting']['id'], 2);

      if ($options['ac-realm'] == $stage && $options['ac-site'] == $sitegroup) {
        $uuid = $app['uuid'];
        break;
      }
    }

    if (!isset($uuid)) {
      throw new \Exception("Cannot find application in Acquia Cloud API v2 that matches target {$options['ac-realm']}:{$options['ac-site']}.");
    }

    $environments = CloudApiV2::get("applications/$uuid/environments");

    foreach ($environments['_embedded']['items'] as $env) {
      if ($env['name'] == $options['ac-env']) {
        return $env['domains'];
      }
    }
    return [];
  }
}

 ?>
