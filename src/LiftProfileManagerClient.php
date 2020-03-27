<?php

namespace Drutiny\Acquia;

use Drutiny\Http\Client;
# use GuzzleHttp\Client;
use Drutiny\Container;
use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use GuzzleHttp\HandlerStack;

class LiftProfileManagerClient {
  static public function get() {
    $creds = Container::credentialManager('acquia_lift');
    $key = new Key($creds['access_key_id'], $creds['secret_access_key']);
    $middleware = new HmacAuthMiddleware($key);

    if (empty($handler)) {
      $handler = HandlerStack::create();
    }
    $handler->push($middleware);
    return new Client([
      'handler' => $handler,
      'base_uri' => $creds['api_endpoint'],
    ]);
  }
}
