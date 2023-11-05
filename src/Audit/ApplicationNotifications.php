<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Attribute\DataProvider;
use Drutiny\Attribute\Deprecated;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
#[Deprecated]
class ApplicationNotifications extends CloudApiAnalysis {

  #[DataProvider(-1)]
  public function gatherNotifications() {
    $filters = [
      'created_at>=' . $this->reportingPeriodStart->format('c'),
      'created_at<=' . $this->reportingPeriodEnd->format('c'),
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
  }
}
