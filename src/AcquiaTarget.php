<?php

namespace Drutiny\Acquia;

use Drutiny\Entity\EventDispatchedDataBag;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Target\Service\LocalService;
use Drutiny\Target\TargetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Drutiny\Annotation\Target(
 *  name = "acquia"
 * )
 */
class AcquiaTarget extends DrushTarget
{
    protected $api;
    protected $cache;

    public function __construct(
      LocalService $local,
      LoggerInterface $logger,
      EventDispatchedDataBag $databag,
      CloudApi $api,
      CacheInterface $cache)
    {
        $this->api = $api->getClient();
        $this->cache = $cache;
        parent::__construct($local, $logger, $databag);
    }

    /**
     * Parse target data.
     */
    public function parse($alias): TargetInterface
    {
        list($product, $uuid) = explode(':', $alias, 2);

        // Look for Acquia Cloud API v2 UUID.
        if (!preg_match('/^(([a-z0-9]+)-){5}([a-z0-9]+)$/', $uuid)) {
            throw new InvalidTargetException("Unknown target data: $alias.");
        }

        $this->logger->info('Loading environment from API...');

        $environment = $this->cache->get('acquia.cloud.environment.'.$uuid, function (ItemInterface $item) use ($uuid) {
            return $this->api->getEnvironment([
              'environmentId' => $uuid,
            ]);
        });

        foreach ($environment as $key => $value) {
            if ('_' == substr($key, 0, 1)) {
                continue;
            }
            $this['acquia.cloud.environment.'.$key] = $value;
        }

        $this['uri'] = $this['acquia.cloud.environment.active_domain'];

        $this->buildAttributes();

        return $this;
    }
}
