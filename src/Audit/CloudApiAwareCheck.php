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
  static protected $credentials;

  /**
   * Find Cloud API credentials to query cloud API with.
   */
  protected function requireHasCloudApiAccess(Sandbox $sandbox)
  {
    $options = $sandbox->drush()->getOptions();
    $keys = ['ac-site', 'ac-env', 'ac-realm'];

    foreach ($keys as $key) {
      if (!array_key_exists($key, $options)) {
        return FALSE;
      }
    }

    // Performance optimisation to only find the credentials once.
    if ($this->hasCloudApiCredentials()) {
      return TRUE;
    }

    try {
      // File might not exist which will throw an error.
      $output = $sandbox->exec('cat ~/.acquia/cloudapi.conf');
    }
    catch (ProcessFailedException $e) {
      // Check again locally, if this fails then we cannot run this check.
      $output = $sandbox->localExec('cat ~/.acquia/cloudapi.conf');

    }
    $creds = json_decode($output, TRUE);

    $this->setCloudApiCredentials($creds['email'], $creds['key']);
    return TRUE;
  }

  protected function setCloudApiCredentials($email, $apiToken)
  {
    self::$credentials = [
      'email' => $email,
      'key' => $apiToken,
    ];
  }

  protected function hasCloudApiCredentials() {
    return !empty(self::$credentials);
  }

  protected function getCloudApiClient()
  {
    $cloudapi = CloudApiClient::factory(array(
        'username' => self::$credentials['email'],  // Email address used to log into the Acquia Network
        'password' => self::$credentials['key'],  // Acquia Network password
    ));
    return $cloudapi;
  }

}
