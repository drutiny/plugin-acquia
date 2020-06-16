<?php

namespace Drutiny\Acquia;

use AcquiaCloudApi\AcquiaCloudApi;
use GuzzleHttp\Exception\ClientException;
use Drutiny\Acquia\Plugin\AcquiaCloudPlugin;
use Drutiny\Http\Client;

/**
 * Drutiny wrapper to Acquia Cloud API.
 */
class CloudApi {

  public function __construct(AcquiaCloudPlugin $plugin, Client $client)
  {
      $creds = $plugin->load();
      $this->client = new AcquiaCloudApi($creds['key_id'], $creds['secret']);
      $this->client->withClient($client->create());
  }

  public function getClient()
  {
    return $this->client;
  }
}
