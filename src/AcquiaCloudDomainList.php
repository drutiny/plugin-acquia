<?php

namespace Drutiny\Acquia;

use Drutiny\DomainList\DomainListInterface;
use Drutiny\Credential\CredentialsUnavailableException;
use Drutiny\Policy;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\Target;
use Drutiny\Annotation\Param;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiV2;

/**
 * @Param(
 *   name = "custom-only",
 *   description = "Boolean indicator to use only custom domains.",
 * )
 */
class AcquiaCloudDomainList implements DomainListInterface {

  public function __construct(array $metadata)
  {
    $has_creds = file_exists(getenv('HOME') . '/.acquia/cloudapi.conf') || Manager::load('acquia_api_v2') || Manager::load('acquia_api_v1');
    if (!$has_creds) {
      throw new \Exception("Cloud API Credentials not found. Please pass them into the argument.");
    }

    $this->custom_only = !empty($metadata['custom-only']);
  }

  /**
   * @return array list of domains.
   */
  public function getDomains(Target $target)
  {
    $domains = $this->loadDomains($target);
    $custom_only = $this->custom_only;
    return array_filter($domains, function ($domain) use ($custom_only) {
      return !$custom_only || !(strpos($domain, 'acquia-sites.com') || strpos($domain, 'elb.amazonaws.com') || strpos($domain, 'acsitefactory.com'));
    });
  }

  protected function loadDomains(Target $target)
  {
    // Setup the target. Creating a sandbox allows the target access to drush.
    new Sandbox($target, Policy::load('Test:Pass'));

    try {
      return $this->loadDomainsFromApiV2($target);
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
    $client = new \GuzzleHttp\Client([
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

  protected function loadDomainsFromApiV2(Target $target)
  {

    if ($target instanceof AcquiaTargetInterface) {
      return $target->getEnvironment()['domains'];
    }
    $apps = CloudApiV2::get('applications');
    $options = $target->getOptions();

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
