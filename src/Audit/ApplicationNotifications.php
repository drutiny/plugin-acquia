<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class ApplicationNotifications extends CloudApiAnalysis {

  public function configure():void
  {
    parent::configure();
    $this->setDeprecated();
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $filters = [
      'created_at>=' . $this->getParameter('reporting_period_start')->format('c'),
      'created_at<=' . $this->getParameter('reporting_period_end')->format('c'),
    ];
    $calls['notifications'] = [
      'path' => "/applications/{acquia.cloud.application.uuid}/notifications",
      'options' => [ 'query' => [
        'sort' => '-created_at',
        'filters' => implode(';', $filters)
      ]]
    ];
    $this->setParameter('calls', $calls);
    $this->setParameter('is_legacy', true);
    parent::gather($sandbox);
  }
}
