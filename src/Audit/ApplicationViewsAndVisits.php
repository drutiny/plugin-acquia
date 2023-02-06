<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Supply data around application views and visits.
 */
class ApplicationViewsAndVisits extends AbstractAnalysis {

  public function configure():void
  {
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
  //  $environment_id = $this->target['acquia.cloud.environment.id'];
    $app = $this->target['acquia.cloud.application']->export();
    $this->set('environment', $this->target['acquia.cloud.environment']->export());
    $this->set('app', $app);
    $client = $this->container->get('acquia.cloud.api')->getClient();

    // Use the from parameter if present, otherwise base the from date based
    // on the reporting period start time.
    $from = $this->get('from') ? date('c', strtotime($this->get('from'))) : $this->getParameter('reporting_period_start')->format('c');

    $this->set('metrics', $metrics = $client->getApplicationsUsageData([
      'applicationUuid' => $app['uuid'],
      'filter' => implode(';', [
        'from=' . $from,
        'to=' . $this->getParameter('reporting_period_end')->format('c'),
      ])
    ]));

    $subscription = $metrics['_embedded']['items'][0]['metadata']['subscription']['uuids'][0];
    $this->set('entitlements', $client->getSubscriptionEntitlements([
      'subscriptionUuid' => $subscription
    ]));
  }
}
