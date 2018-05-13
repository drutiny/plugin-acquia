<?php

namespace Drutiny\Acquia;

use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Sandbox\Sandbox;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * An abstract audit aware of the Acquia Cloud API v1.
 */
class CloudApiV2 {

  static $client;

  public static function get($path)
  {
    $response = self::getApiClient()->request('GET', $path);
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
    $stack->push(new CacheMiddleware(), 'cache');

    self::$client = new Client([
        'handler' => $stack,
        'base_uri' => 'https://cloud.acquia.com/api/',
    ]);

    return self::$client;
  }

}
