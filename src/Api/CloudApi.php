<?php

namespace Drutiny\Acquia\Api;

use AcquiaCloudApi\AcquiaCloudApi;
use Drutiny\Acquia\Plugin\AcquiaCloudPlugin;
use Drutiny\Http\Client;
use Drutiny\Plugin\PluginRequiredException;
use Psr\Log\LoggerInterface;

/**
 * Drutiny wrapper to Acquia Cloud API.
 */
class CloudApi
{
    protected AcquiaCloudApi $client;
    protected PluginRequiredException $e;

    public function __construct(AcquiaCloudPlugin $plugin, Client $client, LoggerInterface $logger)
    {
        try {
            $creds = $plugin->load();
            $this->client = new AcquiaCloudApi($creds['key_id'], $creds['secret']);
        } catch (PluginRequiredException $e) {
            $logger->warning($e->getMessage());
        }
    }

    public function getClient(): AcquiaCloudApi
    {
        if (!isset($this->client)) {
            throw new PluginRequiredException('acquia:cloud');
        }
        return $this->client;
    }
}
