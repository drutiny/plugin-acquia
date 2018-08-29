<?php

namespace Drutiny\Acquia;

use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Http\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drutiny\Target\InvalidTargetException;

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
      $response = self::getApiClient()->request('GET', $path, $options);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      if ($response->getStatusCode() == 403) {
        $error = json_decode($response->getBody(), TRUE);
        throw new InvalidTargetException('[Acquia Cloud API v2] ' . $error['error'] . ': ' . $error['message']);
      }
      throw new InvalidTargetException('[Acquia Cloud API v2] ' . $e->getMessage());
    }

    $json = $response->getBody();
    return json_decode($json, TRUE);
  }

  protected static function getApiClient()
  {
    if (isset(self::$client)) {
      return self::$client;
    }
    $creds = Manager::load('acquia_api_v2');

    $key = new Key($creds['key_id'], $creds['secret']);

    $middleware = new HmacAuthMiddleware($key);

    $stack = HandlerStack::create();
    $stack->push($middleware);

    self::$client = new Client([
        'handler' => $stack,
        'base_uri' => 'https://cloud.acquia.com/api/',
    ]);

    return self::$client;
  }

}
