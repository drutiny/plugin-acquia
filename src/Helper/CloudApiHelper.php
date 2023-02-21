<?php

namespace Drutiny\Acquia\Helper;

use AcquiaCloudApi\AcquiaCloudApi;
use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Entity\Exception\DataNotFoundException;
use Drutiny\Target\TargetInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CloudApiHelper {

    protected AcquiaCloudApi $client;

    public function __construct(
        protected CacheInterface $cache,
        CloudApi $cloudApi
    )
    {
        $this->client = $cloudApi->getClient();    
    }

    /**
     * Find an Acquia application by its ream and sitename.
     */
    public function findApplication($realm, $site):array
    {
        $apps = $this->cache->get('acquia.cloud.applications', function (CacheItemInterface $item) {
            $item->expiresAfter(86400);
            return $this->client->getApplications();
        });

        foreach ($apps['_embedded']['items'] as $app) {
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
            return $this->client->getApplicationEnvironments([
                'applicationUuid' => $uuid,
            ]);
        });

        foreach ($environments['_embedded']['items'] as $environment) {
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
            return $this->client->getEnvironment([
              'environmentId' => $uuid,
            ]);
        });
    }

    public function getApplication($uuid):array
    {
        return $this->cache->get('acquia.cloud.application.'.$uuid, function (CacheItemInterface $item) use ($uuid) {
            $item->expiresAfter(86400);
            return $this->client->getApplicationByUuid([
              'applicationUuid' => $uuid,
            ]);
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