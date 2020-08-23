<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Http\Client;
use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use GuzzleHttp\HandlerStack;
use Drutiny\Acquia\Plugin\AcquiaLiftPlugin;

class LiftProfileManagerClient {

    /**
     * Create a new HttpGuzzle instance for the Lift API.
     */
    static public function create(AcquiaLiftPlugin $plugin, Client $client)
    {
        $creds = $plugin->load();
        $key = new Key($creds['access_key_id'], $creds['secret_access_key']);
        $middleware = new HmacAuthMiddleware($key);

        if (empty($handler)) {
          $handler = HandlerStack::create();
        }
        $handler->push($middleware);
        return $client->create([
          'handler' => $handler,
          'base_uri' => $creds['api_endpoint'],
        ]);
    }
}
