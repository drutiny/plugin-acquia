<?php

namespace Drutiny\Acquia;

use Drutiny\Entity\EventDispatchedDataBag;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Target\TargetSourceInterface;
use Drutiny\Target\Service\ExecutionService;
use Drutiny\Target\TargetInterface;
use Drutiny\Acquia\Api\CloudApi;
use AcquiaCloudApi\AcquiaCloudApi;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use GuzzleHttp\Exception\ClientException;

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
      ExecutionService $service,
      LoggerInterface $logger,
      EventDispatchedDataBag $databag,
      CloudApi $api,
      CacheInterface $cache,
      ProgressBar $progressBar)
    {
        $this->api = $api->getClient();
        $this->cache = $cache;
        $this->progressBar = $progressBar;
        parent::__construct($service, $logger, $databag);
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
    public function parse(string $alias, ?string $uri = null): TargetInterface
    {
        $uuid = $alias;

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

        // Load platform up first.
        // $this['acquia.cloud.environment.platform'] = $environment['platform'];

        foreach ($environment as $key => $value) {
            if ('_' == substr($key, 0, 1)) {
                continue;
            }

            try {
              $this['acquia.cloud.environment.'.$key] = $value;
            }
            catch (InvalidTargetException $e) {
              // This occurs which drush cannot obain a drush alias data.
              // It can safely be ignored.
              $this->logger->debug("AcquiaTarget detected unrelated target exception: " . $e->getMessage());
            }
        }

        // When using AHT, this remote service will likely fail, which is fine
        // because we expect AHT for handle the remote commands. This prevents
        // a user prompt from occuring for something that will gracefully fail.
        if ($this->hasProperty('aht.app')) {
          $this['service.exec']->get('acquia')
            ->setConfig('StrictHostKeyChecking', 'no')
            ->setConfig('UserKnownHostsFile', '/dev/null');
        }

        // Build drush metadata starting from the alias if its available.
        $data = $this['service.exec']->run('drush site:alias $DRUSH_ALIAS --format=json', function ($output) {
            return json_decode($output, true);
        });
        $alias = substr($this['drush.alias'], 1);

        if (!isset($data[$alias])) {
          throw new InvalidTargetException("Invalid target: @$alias. Could not retrive drush site alias details.");
        }
        $this['drush']->add($data[$alias]);

        $this->setUri($uri ?? $this['acquia.cloud.environment.active_domain']);
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

        try {
          $env_res = $this->api->getApplicationEnvironments(['applicationUuid' => $app['uuid']]);
          foreach ($env_res['_embedded']['items'] as $env) {
            $targets[] = [
              'id' => $env['id'],
              'uri' => $env['active_domain'],
              'name' => sprintf('%s: %s', $env['label'], $app['name']),
            ];
          }
        }
        catch (ClientException $e) {
          $res = json_decode($e->getResponse()->getBody(), true);
          $this->logger->error("{$app['name']}: {$res['message']}");
        }

        $this->progressBar->advance();
      }
      $this->progressBar->finish();
      return $targets;
    }
}
