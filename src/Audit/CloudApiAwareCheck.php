<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Acquia\Cloud\Api\CloudApiClient;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * ACSF default theme path.
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
    return file_exists(getenv('HOME') . '/.acquia/cloudapi.conf');
  }

  protected function getApiClient(Sandbox $sandbox)
  {
    try {
      // File might not exist which will throw an error.
      $output = $sandbox->exec('cat ~/.acquia/cloudapi.conf');
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
