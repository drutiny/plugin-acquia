<?php

namespace Drutiny\Acquia\Api;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use Drutiny\Acquia\Plugin\AcquiaCloudPlugin;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Entity\Exception\DataNotFoundException;
use Drutiny\Http\Client as HttpClient;
use Drutiny\Plugin\FieldType;
use Drutiny\Target\TargetInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

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
class CloudApi extends Connector
{
    public function __construct(
      protected AcquiaCloudPlugin $plugin,
      protected CacheInterface $cache,
      HttpClient $drutinyHttpClient
    )
    {
      parent::__construct([
        'key' => $plugin->key_id,
        'secret' => $plugin->secret
      ]);

      // Allow us to use the Drutiny http middleware (e.g. logging).
      $this->client = $drutinyHttpClient->create();
    }

    public function getApiClient():Client
    {
      return Client::factory($this);
    }

    /**
     * Find an Acquia application by its ream and sitename.
     */
    public function findApplication($realm, $site):array
    {
        $apps = $this->cache->get('acquia.cloud.applications', function (CacheItemInterface $item) {
            $item->expiresAfter(86400);
            $resource = new Applications($this->plugin->getApiClient());
            return json_decode(json_encode($resource->getAll()), true);
        });

        foreach ($apps as $app) {
            if (empty($app['hosting'])) {
                continue;
            }
            list($stage, $sitegroup) = explode(':', $app['hosting']['id'], 2);

            if ($realm == $stage && $site == $sitegroup) {
                return $app;
            }
        }
        throw new DataNotFoundException("Cannot find Acquia application matching target criteria: $realm:$site.");
    }

    /**
     * Find an Acquia environment by its environment name.
     * 
     * @param string $uuid The application UUID.
     * @param string env The environment name.
     */
    public function findEnvironment($uuid, $env):array
    {
        $environments = $this->cache->get('acquia.cloud.'.$uuid.'.environments', function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            $resource = new Environments($this->plugin->getApiClient());
            return json_decode(json_encode($resource->getAll($uuid)), true);
        });

        foreach ($environments as $environment) {
            if ($environment['name'] == $env) {
                return $environment;
            }
        }
        throw new DataNotFoundException("Cannot find Acquia application environment: $env.");
    }

    /**
     * Get the Acquia environment by its UUID.
     */
    public function getEnvironment($uuid):array
    {
        return $this->cache->get('acquia.cloud.environment.'.$uuid, function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            $resource = new Environments($this->plugin->getApiClient());
            return json_decode(json_encode($resource->get($uuid)), true);
        });
    }

    public function getApplication($uuid):array
    {
        return $this->cache->get('acquia.cloud.application.'.$uuid, function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            $resource = new Applications($this->plugin->getApiClient());
            return json_decode(json_encode($resource->get($uuid)), true);
        });
    }

    /**
     * Map cloud variables to the target.
     */
    public function mapToTarget(array $data, TargetInterface $target, $namespace):void
    {
        foreach ($data as $key => $value) {
            if ('_' == substr($key, 0, 1)) {
                continue;
            }
            $target[$namespace.'.'.$key] = $value;
        }
    }
}
