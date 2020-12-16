<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class SubscriptionAnalysis extends AbstractAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $subscription = $this->target['acquia.cloud.application.subscription'];
    $subscription_uuid = $subscription['uuid'];

    $client = $this->container->get('acquia.cloud.api')->getClient();

    // $this->set('apm', $client->getSubscriptionApmTypes([
    //   'subscriptionUuid' => $subscription_uuid
    // ]));

    $this->set('applications', $client->getSubscriptionApplications([
      'subscriptionUuid' => $subscription_uuid
    ]));

    $this->set('entitlements', $client->getSubscriptionEntitlements([
      'subscriptionUuid' => $subscription_uuid
    ]));

    $this->set('ides', $client->getSubscriptionIdes([
      'subscriptionUuid' => $subscription_uuid
    ]));

    $this->set('shield_acl', $client->getShieldAcl([
      'subscriptionUuid' => $subscription_uuid
    ]));
  }
}
