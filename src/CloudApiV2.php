<?php

namespace Drutiny\Acquia;

use AcquiaCloudApi\AcquiaCloudApi;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Http\Client;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\InvalidTargetException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;

// PrivateCacheStrategy
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * An abstract audit aware of the Acquia Cloud API v1.
 */
class CloudApiV2 {

  static $client;

  public static function get($path, array $params = [])
  {
    $options = [];
    $options['query'] = $params;
    try {
      $response = self::getApiClient()->getClient()->request('GET', '/'.$path, $options);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      if ($response->getStatusCode() == 403) {
        $error = json_decode($response->getBody(), TRUE);
        throw new InvalidTargetException('[Acquia Cloud API v2] ' . $error['error'] . ': ' . $error['message']);
      }
      throw new InvalidTargetException('[Acquia Cloud API v2] ' . $e->getMessage());
    }

    return $response;
  }

  public static function getApiClient()
  {
    if (isset(self::$client)) {
      return self::$client;
    }
    $creds = Manager::load('acquia_api_v2');

    self::$client = new AcquiaCloudApi($creds['key_id'], $creds['secret']);

    return self::$client;
  }

}
