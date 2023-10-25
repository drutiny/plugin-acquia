<?php

namespace Drutiny\Acquia;

use DateTimeImmutable;
use DateTimeZone;
use Drutiny\Acquia\Api\Analytics;
use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Console\Application;
use Drutiny\Plugin as DrutinyPlugin;
use Drutiny\Plugin\FieldType;
use Drutiny\Plugin\Question;
use Drutiny\Report\Report;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

#[Plugin(name: 'acquia:telemetry')]
#[PluginField(
  name: 'consent',
  description: "Enable anonymous sharing of usage and performance data with Acquia",
  type: FieldType::CONFIG,
  ask: Question::CONFIRMATION
)]
class EventsSubscriber implements EventSubscriberInterface {

    private DateTimeImmutable $requestTime;

    public function __construct(
        protected CloudApi $api, 
        protected DrutinyPlugin $plugin,
        protected Analytics $analytics,
        protected Application $application
    ) {
        $this->requestTime = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

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
        $agent = sprintf('%s %s', $this->application->getName(), $this->application->getVersion());

        $this->analytics->queueEvent('report.build', [
            'timestamp' => $this->requestTime->format('U'),
            'report' => $report->uuid,
            'profile' => $report->profile->name,
            'timing' => $report->timing,
            'start' => $report->reportingPeriodStart->format('c'),
            'end' => $report->reportingPeriodEnd->format('c'),
            'target' => $report->target->getTargetName(),
            'domain' => $report->target['domain'],
            'agent' => $agent,
        ]);

        foreach ($report->results as $result) {
            $this->analytics->queueEvent('policy.audit', [
                'policy' => $result->policy->name,
                'status' => $result->getType(),
                'report' => $report->uuid,
                'timing' => $result->timing,
                'timestamp' => $this->requestTime->format('U'),
                'agent' => $agent,
            ]);
        }

        $this->analytics->logQueuedEvents();
    }
}