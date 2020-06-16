<?php

namespace Drutiny\Acquia\PropertyBridge;

use Drutiny\Event\DataBagEvent;
use Drutiny\Target\Service\RemoteService;
use Drutiny\Target\Service\DrushService;
use Drutiny\Entity\Exception\DataNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drutiny\Acquia\CloudApi;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class CloudBridge implements EventSubscriberInterface {
    protected $api;

    public function __construct(CloudApi $api, CacheInterface $cache, LoggerInterface $logger)
    {
        $this->api = $api->getClient();
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
      return [
        'target.property.bridge.drush' => 'loadCloudBridge',
        'set:acquia.cloud.environment.ssh_url' => 'loadRemoteBridge'
      ];
    }

    /**
     * Search drush config for remote ssh config.
     */
    public static function loadCloudBridge(DataBagEvent $event)
    {
      $bridge = $event->getValue();
      $target = $event->getTarget();

      try {
        $app = $this->findApplication(
          $target->getProperty('drush.ac-realm'),
          $target->getProperty('drush.ac-site')
        );
        $target->setProperty('acquia.cloud.application', $app);

        $env = $this->findEnvironment(
          $app['uuid'],
          $target->getProperty('drush.ac-env'),
        );
        $target->setProperty('acquia.cloud.environment', $env);
      }
      // If the config doesn't exist then do nothing.
      catch (DataNotFoundException $e) {
          $this->logger->error($e->getMessage());
      }
    }

    public static function loadRemoteBridge(DataBagEvent $event)
    {
        $ssh_url = $event->getValue();
        $target = $event->getDatabag()->getObject();
        list($user, $host) = explode('@', $ssh_url, 2);

        try {
          $service = new RemoteService($target['service.local']);
          $service->setConfig('User', $user);
          $service->setConfig('Host', $host);

          $target['service.exec'] = $service;

        }
        // If the config doesn't exist then do nothing.
        catch (DataNotFoundException $e) {
          $this->logger->error($e->getMessage());
          return;
        }

        $data = $service->run("drush site:alias @$user --format=json", function ($output) {
            return json_decode($output, TRUE);
        });

        $target['drush.alias'] = "@$user";
        $target['drush']->add($data[$user]);
    }

    protected function findApplication($realm, $site)
    {
      $apps = $this->cache->get('acquia.cloud.applications', function (ItemInterface $item) {
        return $this->api->get('applications');
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
      throw new \Exception("Cannot find Acquia application matching target criteria: $realm:$site.");
    }

    protected static function findEnvironment($uuid, $env)
    {
      $environments = $this->cache->get('acquia.cloud.'.$uuid.'.environments', function (ItemInterface $item) {
        return $this->api->get("applications/$uuid/environments");
      });

      foreach ($environments['_embedded']['items'] as $environment) {
        if ($environment['name'] == $env) {
          return $environment;
        }
      }
      throw new \Exception("Cannot find Acquia application environment: $env.");
    }
}
