<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Http\Client;
use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use GuzzleHttp\HandlerStack;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Plugin as DrutinyPlugin;
use Drutiny\Plugin\FieldType;

#[Plugin(name: 'acquia:lift')]
#[PluginField(
  name: 'access_key_id',
  description: "Your Acquia Lift Access Key ID. Acquia will provide you with your keys\nafter you subscribe to Omnichannel. See https://docs.acquia.com/lift/omni/rest_api/",
  type: FieldType::CREDENTIAL
)]
#[PluginField(
  name: 'secret_access_key',
  description: 'Your Acquia Lift Secret Access Key. Acquia will provide you with your\nkeys after you subscribe to Omnichannel. See https://docs.acquia.com/lift/omni/rest_api/',
  type: FieldType::CREDENTIAL
)]
#[PluginField(
  name: 'api_endpoint',
  description: 'The API server URL of the Acquia Lift Admin. This information may be\nprovided to you with your keys, and is available from your Insight page.\nThis varies based on your assigned API server.',
  type: FieldType::CONFIG
)]
class LiftProfileManagerClient {

    /**
     * Create a new HttpGuzzle instance for the Lift API.
     */
    static public function create(DrutinyPlugin $plugin, Client $client)
    {
        $key = new Key($plugin->access_key_id, $plugin->secret_access_key);
        $middleware = new HmacAuthMiddleware($key);

        if (empty($handler)) {
          $handler = HandlerStack::create();
        }
        $handler->push($middleware);
        return $client->create([
          'handler' => $handler,
          'base_uri' => $plugin->api_endpoint,
        ]);
    }
}
