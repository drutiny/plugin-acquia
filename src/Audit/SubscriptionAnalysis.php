<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class SubscriptionAnalysis extends CloudApiAnalysis {
  public function configure():void
  {
    parent::configure();
    $this->setDeprecated();
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $calls['apm_settings'] = [
      'path' => '/subscriptions/{acquia.cloud.application.subscription.uuid}/entitlements'
    ];
    $this->setParameter('calls', $calls);
    $this->setParameter('is_legacy', true);
    parent::gather($sandbox);
  }
}
