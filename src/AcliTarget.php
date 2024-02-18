<?php

namespace Drutiny\Acquia;

use Drutiny\Target\Exception\InvalidTargetException;
use Drutiny\Target\TargetSourceInterface;
use Drutiny\Target\TargetInterface;
use Drutiny\Acquia\Api\AcquiaCli;
use Drutiny\Attribute\AsTarget;
use Drutiny\Attribute\UseService;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\Exception\TargetServiceUnavailable;
use Drutiny\Target\Service\Drush;
use Drutiny\Target\Service\ServiceInterface;
use Drutiny\Target\Transport\SshTransport;
use Drutiny\Target\Transport\TransportInterface;

/**
 * Acquia Target
 */
#[UseService(AcquiaCli::class, 'setAcquiaCli')]
#[AsTarget(name: 'acli')]
class AcliTarget extends DrushTarget implements TargetSourceInterface
{
    protected AcquiaCli $api;

    /**
     * {@inheritdoc}
     */
    public function getId():string
    {
      return $this['alias'];
    }

    /**
     * Setter callback for Acquia CLI service.
     */
    public function setAcquiaCli(AcquiaCli $api) {
      $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureService(ServiceInterface $service): void
    {
      // If there is no Drupal site, then there is no point in running a drush service.
      if ($service instanceof Drush && $this['acquia.cloud.environment.vcs[path]'] == 'tags/WELCOME') {
        throw new TargetServiceUnavailable("Acquia CLI target environment has the WELCOME tag deployed. There is no Drupal here.");
      }
      parent::configureService($service);
    }

    /**
     * Parse target data.
     */
    public function parse(string $alias, ?string $uri = null): TargetInterface
    {
        if (str_starts_with($alias, '@')) {
          $alias = substr($alias, 1);
        }
        if (!str_contains($alias, '.')) {
          throw new InvalidTargetException("Incorrect format. Acquia CLI target must use alias syntax: appname.env");
        }
        $this['alias'] = $alias;
        list($app, $env) = explode('.', $alias);
        
        $realm = 'prod';
        if (str_contains($app, ':')) {
          list($realm, $app) = explode(':', $app);
        }

        $this['alias'] = $alias;

        $this->logger->info('Loading environment from API...');
        $application = $this->api->findApplication($realm, $app);
        $environment = $this->api->findEnvironment($application['uuid'], $env);

        $this->api->mapToTarget($application, $this, 'acquia.cloud.application');
        $this->api->mapToTarget($environment, $this, 'acquia.cloud.environment');

        list($machine_name, ) = explode('.', $environment['default_domain']);
        $this['acquia.cloud.machine_name'] = $machine_name;

        $this->setUri($uri ?: $environment['active_domain']);

        if ($environment['type'] == 'drupal') {
          list($user, $host) = explode('@', $environment['ssh_url'], 2);
          // At the remote, this is what the Drush alias should be. This becomes
          // an environment variable (e.g. $DRUSH_ALIAS).
          $this['drush.alias'] = "@$user";
          // Tell DrushService where the drupal site is.
          $this['drush.root'] = "/var/www/html/$user/docroot";
  
          // We need to load the Transport early to load drush attributes correctly.
          $this->transport = $this->loadTransport();
          $this->rebuildEnvVars();

          if ($this['acquia.cloud.environment.vcs[path]'] == 'tags/WELCOME') {
            $this->setUri($uri ?: $environment['default_domain']);
            $this->logger->critical("Acquia target environment has the WELCOME tag deployed. There is no Drupal here.");
            return $this;
          }
  
          $this->buildAttributes();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTargets():array
    {
      $targets = [];
      $applications = $this->api->runApiCommand('api:applications:list');
      foreach ($applications as $app) {
        $this->logger->notice("Building environment targets for {$app['name']}.");

        $environments = $this->api->runApiCommand('api:applications:environment-list', [$app['uuid']]);
        foreach ($environments as $env) {
          $targets[] = [
            'id' => $app['hosting']['id'] . '.' . $env['name'],
            'uri' => $env['active_domain'],
            'name' => sprintf('%s: %s', $env['label'], $app['name']),
          ];
        }
      }
      return $targets;
    }

    /**
     * Setup SSH based remote exection service.
     */
    protected function addRemoteTransport($user, $host):TransportInterface
    {
        // Don't need to setup transport if its already handled be an extending class.
        if ($this->transport instanceof SshTransport) {
          return $this->transport;
        }
        $transport = new SshTransport($this->localCommand);
        $transport->setConfig('User', $user);
        $transport->setConfig('Host', $host);
        return $transport;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTransport(): TransportInterface
    {
      if ($this['acquia.cloud.environment']['type'] == 'drupal') {
        list($user, $host) = explode('@', $this['acquia.cloud.environment']['ssh_url'], 2);
        return $this->addRemoteTransport($user, $host);
      }
      return parent::loadTransport();
    }
}
