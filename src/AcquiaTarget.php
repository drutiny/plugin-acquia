<?php

namespace Drutiny\Acquia;

use Drutiny\Entity\EventDispatchedDataBag;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Target\TargetSourceInterface;
use Drutiny\Target\Service\LocalService;
use Drutiny\Target\TargetInterface;
use Drutiny\Acquia\Api\CloudApi;
use AcquiaCloudApi\AcquiaCloudApi;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * @Drutiny\Annotation\Target(
 *  name = "acquia"
 * )
 */
class AcquiaTarget extends DrushTarget implements TargetSourceInterface
{
    protected AcquiaCloudApi $api;
    protected CacheInterface $cache;
    protected ProgressBar $progressBar;

    public function __construct(
      LocalService $local,
      LoggerInterface $logger,
      EventDispatchedDataBag $databag,
      CloudApi $api,
      CacheInterface $cache,
      ProgressBar $progressBar)
    {
        $this->api = $api->getClient();
        $this->cache = $cache;
        $this->progressBar = $progressBar;
        parent::__construct($local, $logger, $databag);
    }

    /**
     * {@inheritdoc}
     */
    public function getId():string
    {
      return $this['acquia.cloud.environment.id'];
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

        $this->setUri($this['acquia.cloud.environment.active_domain']);
        $this->buildAttributes();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTargets():array
    {
      $targets = [];
      $response = $this->api->getApplications();
      $this->progressBar->start($response['total']);
      foreach ($response['_embedded']['items'] as $app) {
        $this->logger->notice("Building environment targets for {$app['name']}.");
        $env_res = $this->api->getApplicationEnvironments(['applicationUuid' => $app['uuid']]);
        foreach ($env_res['_embedded']['items'] as $env) {
          $targets[] = [
            'id' => $env['id'],
            'uri' => $env['active_domain'],
            'name' => sprintf('%s: %s', $env['label'], $app['name']),
          ];
        }
        $this->progressBar->advance();
      }
      $this->progressBar->finish();
      return $targets;
    }
}
