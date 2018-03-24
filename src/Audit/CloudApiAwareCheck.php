<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Acquia\Cloud\Api\CloudApiClient;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * An abstract audit aware of the Acquia Cloud API v1.
 */
abstract class CloudApiAwareCheck extends Audit {

  /**
   * Validate target is against the Acquia Cloud.
   */
  protected function requireAcquiaCloudAlias(Sandbox $sandbox)
  {
    $options = $sandbox->drush()->getOptions();
    $keys = ['ac-site', 'ac-env', 'ac-realm'];

    foreach ($keys as $key) {
      if (!array_key_exists($key, $options)) {
        return FALSE;
      }
    }
  }

  /**
   * Find Cloud API credentials to query cloud API with.
   */
  protected function requireHasCloudApiCredentials(Sandbox $sandbox)
  {
    $has_aht = class_exists('\Drutiny\Acquia\CS\Target\Aht') && ($sandbox->getTarget() instanceof \Drutiny\Acquia\CS\Target\Aht);
    return $has_aht || file_exists(getenv('HOME') . '/.acquia/cloudapi.conf');
  }

  protected function api(Sandbox $sandbox, $path)
  {
    $has_aht = class_exists('\Drutiny\Acquia\CS\Target\Aht') && ($sandbox->getTarget() instanceof \Drutiny\Acquia\CS\Target\Aht);
    if (!$has_aht) {
      $res = $this->getApiClient($sandbox)->request('GET', $path);
      $json = $res->getBody();
    }
    else {
      $json = $sandbox->aht('@sitegroup.env cloudapi --format=json ' . $path);
    }
    return json_decode($json, TRUE);
  }

  private function getApiClient(Sandbox $sandbox)
  {
    try {
      // File might not exist which will throw an error.
      $output = $sandbox->exec('cat ~/.acquia/cloudapi.conf');
      if (empty($output)) {
        $output = $sandbox->localExec('cat ~/.acquia/cloudapi.conf');
      }
    }
    catch (ProcessFailedException $e) {
      // Check again locally, if this fails then we cannot run this check.
      $output = $sandbox->localExec('cat ~/.acquia/cloudapi.conf');
    }
    $creds = json_decode($output, TRUE);

    $client = new \GuzzleHttp\Client([
      'base_uri' => 'https://cloudapi.acquia.com/v1/',
      'auth' => [$creds['email'], $creds['key']],
    ]);

    return $client;
  }

}
