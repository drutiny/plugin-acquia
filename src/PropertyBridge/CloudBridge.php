<?php

namespace Drutiny\Acquia\PropertyBridge;

use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Entity\Exception\DataNotFoundException;
use Drutiny\Event\DataBagEvent;
use Drutiny\Target\Service\RemoteService;
use Drutiny\Target\InvalidTargetException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CloudBridge implements EventSubscriberInterface
{
    protected $api;

    public function __construct(CloudApi $api, CacheInterface $cache, LoggerInterface $logger)
    {
        $this->api = $api->getClient();
        $this->cache = $cache;
        if (method_exists($logger, 'withName')) {
          $logger = $logger->withName(get_class($this));
        }
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
          'set:acquia.cloud.environment.ssh_url' => 'loadRemoteBridge',
          'set:drush.ac-realm' => 'loadCloudBridge',
        ];
    }

    /**
     * Attempt to load Acquia Cloud environment from Drush Alias.
     */
    public function loadCloudBridge(DataBagEvent $event)
    {
        $target = $event->getDataBag()->getObject();

        try {
            $app = $this->findApplication(
                $event->getValue(),
                $target->getProperty('drush.ac-site')
            );

            foreach ($app as $key => $value) {
                if ('_' == substr($key, 0, 1)) {
                    continue;
                }
                $target['acquia.cloud.application.'.$key] = $value;
            }

            $env = $this->findEnvironment(
                $app['uuid'],
                $target->getProperty('drush.ac-env'),
            );

            foreach ($env as $key => $value) {
                if ('_' == substr($key, 0, 1)) {
                    continue;
                }
                $target['acquia.cloud.environment.'.$key] = $value;
            }

            $target->setUri($target['acquia.cloud.environment.active_domain']);
        }
        // If the config doesn't exist then do nothing.
        catch (DataNotFoundException $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    public static function loadRemoteBridge(DataBagEvent $event)
    {
        $ssh_url = $event->getValue();
        $target = $event->getDatabag()->getObject();

        list($user, $host) = explode('@', $ssh_url, 2);

        try {
            $service = new RemoteService($target['service.exec']->get('local'));
            $service->setConfig('User', $user);
            $service->setConfig('Host', $host);
            $target['service.exec']->addHandler($service, 'acquia');
        }
        // If the config doesn't exist then do nothing.
        catch (DataNotFoundException $e) {
            $this->logger->error($e->getMessage());
            return;
        }
        $target['drush.alias'] = "@$user";
    }

    protected function findApplication($realm, $site)
    {
        $apps = $this->cache->get('acquia.cloud.applications', function (ItemInterface $item) {
            return $this->api->getApplications();
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

    protected function findEnvironment($uuid, $env)
    {
        $environments = $this->cache->get('acquia.cloud.'.$uuid.'.environments', function (ItemInterface $item) use ($uuid) {
            return $this->api->getApplicationEnvironments([
                'applicationUuid' => $uuid,
            ]);
        });

        foreach ($environments['_embedded']['items'] as $environment) {
            if ($environment['name'] == $env) {
                return $environment;
            }
        }
        throw new \Exception("Cannot find Acquia application environment: $env.");
    }
}
