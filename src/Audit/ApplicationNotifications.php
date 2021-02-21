<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class ApplicationNotifications extends AbstractAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $environment_id = $this->target['acquia.cloud.environment.id'];
    $app = $this->target['acquia.cloud.application']->export();
    $this->set('environment', $this->target['acquia.cloud.environment']->export());
    $this->set('app', $app);
    $client = $this->container->get('acquia.cloud.api')->getClient();

    $filters = [
      'created_at>=' . $this->getParameter('reporting_period_start')->format('c'),
    ];

    $this->set('notifications', $client->getApplicationNotifications([
      'applicationUuid' => $app['uuid'],
      'sort' => '-created_at',
      'filter' => implode(';', $filters)
    ]));
  }
}
