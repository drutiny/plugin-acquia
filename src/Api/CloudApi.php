<?php

namespace Drutiny\Acquia\Api;

use AcquiaCloudApi\AcquiaCloudApi;
use Drutiny\Acquia\Plugin\AcquiaCloudPlugin;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Plugin\FieldType;

/**
 * Drutiny wrapper to Acquia Cloud API.
 */

 #[Plugin(
    name: 'acquia:cloud', 
    class: AcquiaCloudPlugin::class
)]
 #[PluginField(
   name: 'key_id',
   description: "Your Key ID to connect to the Acquia Cloud API v2 with. To generate an\nAPI access token, login to https://cloud.acquia.com, then visit\nhttps://cloud.acquia.com/a/profile/tokens, and click **Create Token**:",
   type: FieldType::CREDENTIAL
 )]
 #[PluginField(
   name: 'secret',
   description: 'Your API secret to connect to the Acquia Cloud API v2 with:',
   type: FieldType::CREDENTIAL
 )]
class CloudApi
{
    public function __construct(protected AcquiaCloudPlugin $plugin) {}
    public function getClient(): AcquiaCloudApi
    {
        return new AcquiaCloudApi($this->plugin->key_id, $this->plugin->secret);
    }
}
