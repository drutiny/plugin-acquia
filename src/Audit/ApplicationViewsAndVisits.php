<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Sandbox\Sandbox;

/**
 * Supply data around application views and visits.
 */
class ApplicationViewsAndVisits extends CloudApiAnalysis {

  public function configure():void
  {
    $this->setDeprecated();
    $this->addParameter(
      'from',
      static::PARAMETER_OPTIONAL,
      'A relative interval from the reporting-period-end to calculate the ApplicationUsageData from.',
      '-1 month'
    );
    parent::configure();
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    // Use the from parameter if present, otherwise base the from date based
    // on the reporting period start time.
    $from = $this->get('from') ? date('c', strtotime($this->get('from'))) : $this->getParameter('reporting_period_start')->format('c');

    $calls['metrics'] = [
      'path' => "/applications/{acquia.cloud.application.uuid}/metrics/usage/data",
      'options' => ['query' => [
        'filter' => implode(';', [
          'from=' . $from,
          'to=' . $this->getParameter('reporting_period_end')->format('c'),
        ])
      ]]
    ];

    $calls['entitlements'] = [
      'path' => "/subscriptions/{metrics.0.metadata.subscription.uuids.0}/entitlements",
    ];
    $this->setParameter('calls', $calls);
    $this->setParameter('is_legacy', true);
    parent::gather($sandbox);
  }
}
