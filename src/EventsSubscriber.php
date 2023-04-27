<?php

namespace Drutiny\Acquia;

use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Plugin as DrutinyPlugin;
use Drutiny\Plugin\FieldType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

#[Plugin(name: 'acquia:telemetry')]
#[PluginField(
  name: 'consent',
  description: "Enable anonymous sharing of usage and performance data with Acquia",
  type: FieldType::CONFIG
)]
class EventsSubscriber implements EventSubscriberInterface {

    public function __construct(protected CloudApi $api, protected DrutinyPlugin $plugin)
    {
    }

    public static function getSubscribedEvents() {
        return [
            'target.load' => 'loadTargetListener'
        ];
    }

    /**
     * Load Acquia Cloud API information from a drush alias.
     */
    public function loadTargetListener(GenericEvent $event) {
        $target = $event->getArgument('target');
        if (!$target->hasProperty('drush.ac-site') || $target->hasProperty('acquia.cloud.environment')) {
            return;
        }
        $application = $this->api->findApplication($target['drush.ac-realm'], $target['drush.ac-site']);
        $this->api->mapToTarget($application, $target, 'acquia.cloud.application');

        $environment = $this->api->findEnvironment($application['uuid'], $target['drush.ac-env']);
        $this->api->mapToTarget($environment, $target, 'acquia.cloud.environment');

        list($machine_name, ) = explode('.', $environment['default_domain']);
        $target['acquia.cloud.machine_name'] = $machine_name;

        $target->setUri($environment['active_domain']);
    }
}