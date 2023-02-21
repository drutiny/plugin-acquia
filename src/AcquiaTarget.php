<?php

namespace Drutiny\Acquia;

use Drutiny\Entity\EventDispatchedDataBag;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Target\TargetSourceInterface;
use Drutiny\Target\TargetInterface;
use Drutiny\Acquia\Api\CloudApi;
use AcquiaCloudApi\AcquiaCloudApi;
use AcquiaCloudApi\Exception\ApiErrorException;
use Drutiny\Acquia\Helper\CloudApiHelper;
use Drutiny\Attribute\AsTarget;
use Drutiny\LocalCommand;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\Service\ServiceFactory;
use Drutiny\Target\Transport\SshTransport;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Acquia Target
 */
#[AsTarget(name: 'acquia')]
class AcquiaTarget extends DrushTarget implements TargetSourceInterface
{
    protected AcquiaCloudApi $api;

    public function __construct(
      LoggerInterface $logger,
      EventDispatchedDataBag $databag,
      LocalCommand $localCommand,
      ServiceFactory $serviceFactory,
      EventDispatcher $eventDispatcher,
      protected CloudApiHelper $helper,
      CloudApi $api,
      protected CacheInterface $cache,
      protected ProgressBar $progressBar)
    {
        $this->api = $api->getClient();
        parent::__construct(
          logger: $logger, 
          databag: $databag, 
          localCommand: $localCommand, 
          serviceFactory: $serviceFactory,
          eventDispatcher: $eventDispatcher
        );
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
        $is_uuid = preg_match('/^(([a-z0-9]+)-){5}([a-z0-9]+)$/', $uuid);

        // Look for Acquia Cloud API v2 UUID.
        if (!$is_uuid && !preg_match('/(prod|network|devcloud|enterprise-g1):([a-zA-Z0-9]+)\.([a-zA-Z0-9]+)/', $alias, $matches)) {
            throw new InvalidTargetException("Unknown target data: $alias.");
        }

        $this->logger->info('Loading environment from API...');
        if ($is_uuid) {
          $environment = $this->helper->getEnvironment($uuid);
          $application = $this->helper->getApplication($environment['application']['uuid']);
        }
        else {
          list(, $realm, $app, $env) = $matches;
          $application = $this->helper->findApplication($realm, $app);
          $environment = $this->helper->findEnvironment($application['uuid'], $env);
        }
        $this->helper->mapToTarget($application, $this, 'acquia.cloud.application');
        $this->helper->mapToTarget($environment, $this, 'acquia.cloud.environment');

        $this->setUri($uri ?: $environment['active_domain']);
        
        list($user, $host) = explode('@', $environment['ssh_url'], 2);
        // At the remote, this is what the Drush alias should be. This becomes
        // an environment variable (e.g. $DRUSH_ALIAS).
        $this['drush.alias'] = "@$user";
        // Tell DrushService where the drupal site is.
        $this['drush.root'] = "/var/www/html/$user/docroot";

        $this->addRemoteTransport($user, $host);
        $this->rebuildEnvVars();
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
        catch (ApiErrorException $e) {
          $this->logger->warning($app['name'] .": ".$e->getMessage());
        }

        $this->progressBar->advance();
      }
      $this->progressBar->finish();
      return $targets;
    }

    /**
     * Load up the environment data from Acquia Cloud API.
     */
    protected function loadEnvironmentFromCloudApi($uuid) {
      $environment = $this->cache->get('acquia.cloud.environment.'.$uuid, function (ItemInterface $item) use ($uuid) {
          return $this->api->getEnvironment([
            'environmentId' => $uuid,
          ]);
      });

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
      return $environment;
    }

    /**
     * Setup SSH based remote exection service.
     */
    protected function addRemoteTransport($user, $host):void
    {
        // Don't need to setup transport if its already handled be an extending class.
        if ($this->transport instanceof SshTransport) {
          return;
        }
        $this->transport = new SshTransport($this->localCommand);
        $this->transport->setConfig('User', $user);
        $this->transport->setConfig('Host', $host);
    }
}
