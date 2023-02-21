<?php

namespace Drutiny\Acquia;

use Drutiny\Acquia\Helper\CloudApiHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;


class EventsSubscriber implements EventSubscriberInterface {

    public function __construct(protected CloudApiHelper $helper)
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
        $application = $this->helper->findApplication($target['drush.ac-realm'], $target['drush.ac-site']);
        $this->helper->mapToTarget($application, $target, 'acquia.cloud.application');

        $environment = $this->helper->findEnvironment($application['uuid'], $target['drush.ac-env']);
        $this->helper->mapToTarget($environment, $target, 'acquia.cloud.environment');

        $target->setUri($environment['active_domain']);
    }
}