<?php

namespace Drutiny\Acquia\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Drutiny\Acquia\Plugin\AcquiaTelemetry;
use Drutiny\Plugin\PluginRequiredException;
use Zumba\Amplitude\Amplitude;
use loophp\phposinfo\OsInfo;

class Drutiny implements EventSubscriberInterface
{
    protected AcquiaTelemetry $plugin;
    protected Amplitude $telemetry;
    protected $consent = false;

    public function __construct(AcquiaTelemetry $plugin, Amplitude $telemetry)
    {
        $this->plugin = $plugin;
        $this->telemetry = $telemetry;
    }

    public static function getSubscribedEvents()
    {
        return [
          'application.run' => 'initializeTelemetry',
          'application.done' => 'sendTelemetry',
          'console.command' => 'trackCommand',
          'assessment' => 'trackAssessment',
          'audit' => 'trackAudit'
        ];
    }

    /**
     * Initiatize amplitude analytics.
     */
    public function initializeTelemetry(GenericEvent $event)
    {
        try {
            $config = $this->plugin->load();
        } catch (PluginRequiredException $e) {
            $this->plugin->setup();
            $config = $this->plugin->load();
        }
        $this->consent = $config['consent'];
        if (!$this->consent) {
            return;
        }
    }

    /**
     * Initiatize amplitude analytics.
     */
    public function sendTelemetry(GenericEvent $event)
    {
        $this->telemetry->setDeviceId(OsInfo::uuid())->logQueuedEvents();
    }

    /**
     * Track the command data.
     */
    public function trackCommand(ConsoleCommandEvent $event)
    {
        if (!$this->consent) {
            return;
        }
        $command = $event->getCommand();
        $input = $event->getInput();

        $args = $input->getArguments();
        if ($input->hasOption('uri')) {
            $args['uri'] = $input->getOption('uri');
        }

        $this->telemetry
          ->queueEvent('console.command', $args)
          ->setDeviceId(OsInfo::uuid())->logQueuedEvents();
    }

    /**
     * Track Assessment.
     */
    public function trackAssessment(GenericEvent $event)
    {
        if (!$this->consent) {
            return;
        }
        $args = $event->getArguments();
        $this->telemetry
          ->queueEvent('assessment', $args)
          ->setDeviceId(OsInfo::uuid())->logQueuedEvents();
    }

    /**
     * Track Assessment.
     */
    public function trackAudit(GenericEvent $event)
    {
        if (!$this->consent) {
            return;
        }
        $args = $event->getArguments();
        // Do not track who pass/failed what.
        unset($args['uri']);
        $this->telemetry
          ->queueEvent('audit', $args);
    }
}
