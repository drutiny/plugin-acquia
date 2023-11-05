<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Attribute\DataProvider;
use Drutiny\Attribute\Parameter;
use Drutiny\Attribute\Deprecated;
use Drutiny\Attribute\Type;

/**
 * Supply data around application views and visits.
 */
#[Parameter(
  name: 'from',
  description: 'A relative interval from the reporting-period-end to calculate the ApplicationUsageData from.',
  default: '-1 month',
  type: Type::STRING,
)]
#[Deprecated]
class ApplicationViewsAndVisits extends CloudApiAnalysis {

  /**
   * @inheritdoc
   */
  #[DataProvider(-1)]
  public function gatherViewsAndVisits() {
    // Use the from parameter if present, otherwise base the from date based
    // on the reporting period start time.
    $from = $this->get('from') ? date('c', strtotime($this->get('from'))) : $this->reportingPeriodStart->format('c');

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
  }
}
