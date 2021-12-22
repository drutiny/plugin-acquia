<?php

namespace Drutiny\Acquia\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Drutiny\Acquia\Plugin\AcquiaTelemetry;
use Drutiny\Plugin\PluginRequiredException;
use Zumba\Amplitude\Amplitude;
use loophp\phposinfo\OsInfo;

class Drutiny implements EventSubscriberInterface {

  protected AcquiaTelemetry $plugin;
  protected Amplitude $telemetry;
  protected $consent = false;

  public function __construct(AcquiaTelemetry $plugin, Amplitude $telemtry)
  {
    $this->plugin = $plugin;
    $this->telemtry = $telemtry;
  }

  public static function getSubscribedEvents()
  {
    return [
      'application.run' => 'initializeTelemetry',
      'console.command' => 'trackCommand'
    ];
  }

  /**
   * Initiatize amplitude analytics.
   */
  public function initializeTelemetry(GenericEvent $event)
  {
     try {
       $config = $this->plugin->load();
     }
     catch (PluginRequiredException $e) {
       $this->plugin->setup();
       $config = $this->plugin->load();
     }
     $this->consent = $config['consent'];
     if (!$this->consent) {
       return;
     }
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

    $this->telemtry
      ->queueEvent('console.command', $args)
      ->setDeviceId(OsInfo::uuid())
      ->logQueuedEvents();
  }
}
