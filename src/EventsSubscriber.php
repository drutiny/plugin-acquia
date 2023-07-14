<?php

namespace Drutiny\Acquia;

use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Plugin as DrutinyPlugin;
use Drutiny\Plugin\FieldType;
use Drutiny\Plugin\Question;
use Drutiny\Report\Report;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Zumba\Amplitude\Amplitude;

#[Plugin(name: 'acquia:telemetry')]
#[PluginField(
  name: 'consent',
  description: "Enable anonymous sharing of usage and performance data with Acquia",
  type: FieldType::CONFIG,
  ask: Question::CONFIRMATION
)]
class EventsSubscriber implements EventSubscriberInterface {

    public function __construct(
        protected CloudApi $api, 
        protected DrutinyPlugin $plugin,
        protected Amplitude $amplitude,
    ) {}

    public static function getSubscribedEvents() {
        return [
            'target.load' => 'loadTargetListener',
            'report.create' => 'trackReport'
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

    /**
     * Send report data metrics to Amplitude.
     */
    public function trackReport(Report $report):void {
        if (!$this->plugin->isInstalled() || !$this->plugin->consent) {
            return;
        }
        $this->amplitude->queueEvent('profle.result', [
            'name' => $report->profile->name,
            'report' => $report->uuid,
            'timing' => $report->timing,
            'start' => $report->reportingPeriodStart->format('c'),
            'target' => $report->target->getTargetName()
        ]);

        foreach ($report->results as $result) {
            $this->amplitude->queueEvent('policy.result', [
                'name' => $result->policy->name,
                'status' => $result->getType(),
                'report' => $report->uuid,
                'timing' => $result->timing
            ]);
        }

        $this->amplitude->logQueuedEvents();
    }
}