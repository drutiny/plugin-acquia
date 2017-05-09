<?php

namespace Drutiny\Acquia\Check\ValidationTrait;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Process\Exception\ProcessFailedException;

trait HasCloudApiAccess {
  protected $apiCredentials = [];

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

    try {
      // File might not exist which will throw an error.
      $output = $sandbox->exec('cat ~/.acquia/cloudapi.conf');
    }
    catch (ProcessFailedException $e) {
      // Check again locally, if this fails then we cannot run this check.
      $output = $sandbox->localExec('cat ~/.acquia/cloudapi.conf');

    }
    $this->apiCredentials = json_decode($output);
    return TRUE;
  }
}

 ?>
