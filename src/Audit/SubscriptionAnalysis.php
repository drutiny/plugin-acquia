<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Attribute\DataProvider;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class SubscriptionAnalysis extends CloudApiAnalysis {
  public function configure():void
  {
    parent::configure();
    $this->setDeprecated();
  }

  #[DataProvider(-1)]
  public function gatherApmSettings() {
    $calls['apm_settings'] = [
      'path' => '/subscriptions/{acquia.cloud.application.subscription.uuid}/entitlements'
    ];
    $this->setParameter('calls', $calls);
    $this->setParameter('is_legacy', true);
  }
}
