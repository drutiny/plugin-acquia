<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Supply data around application views and visits.
 */
class ApplicationViewsAndVisits extends AbstractAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
  //  $environment_id = $this->target['acquia.cloud.environment.id'];
    $app = $this->target['acquia.cloud.application']->export();
    $this->set('environment', $this->target['acquia.cloud.environment']->export());
    $this->set('app', $app);
    $client = $this->container->get('acquia.cloud.api')->getClient();

    $this->set('metrics', $metrics = $client->getApplicationsUsageData([
      'applicationUuid' => $app['uuid'],
      'filter' => implode(';', [
        'from=' . $this->getParameter('reporting_period_start')->format('c'),
        'to=' => $this->getParameter('reporting_period_end')->format('c'),
      ])
    ]));

    $subscription = $metrics['_embedded']['items'][0]['metadata']['subscription']['uuids'][0];
    $this->set('entitlements', $client->getSubscriptionEntitlements([
      'subscriptionUuid' => $subscription
    ]));
  }
}
