<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class EnvironmentAnalysis extends CloudApiAnalysis {
  
  public function configure():void
  {
    parent::configure();
    $this->setDeprecated();
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    $calls['cron'] = [
      'path' => '/environments/{acquia.cloud.environment.uuid}/crons'
    ];

    $calls['databases'] = [
      'path' => '/environments/{acquia.cloud.environment.uuid}/databases'
    ];

    $calls['dns'] = [
      'path' => '/environments/{acquia.cloud.environment.uuid}/dns'
    ];

    $calls['servers'] = [
      'path' => '/environments/{acquia.cloud.environment.uuid}/servers'
    ];

    $calls['apm_settings'] = [
      'path' => '/subscriptions/{acquia.cloud.application.subscription.uuid}/apm'
    ];

    $calls['certificates'] = [
      'path' => '/environments/{acquia.cloud.environment.uuid}/ssl/certificates'
    ];

    $calls['csrs'] = [
      'path' => '/environments/{acquia.cloud.environment.uuid}/ssl/csrs'
    ];

    $calls['search_settings'] = [
      'path' => '/applications/{acquia.cloud.application.uuid}/search/config-sets'
    ];

    if (array_key_exists('remote_admin', $this->target['acquia.cloud.application.flags']) &&  $this->target['acquia.cloud.application.flags']['remote_admin']) {
      $calls['remote_admin'] = [
        'path' => '/applications/{acquia.cloud.application.uuid}/settings/ra'
      ];  
    }

    if ($this->target['acquia.cloud.application.hosting']['type'] != 'acsf') {
      $calls['variables'] = [
        'path' => '/environments/{acquia.cloud.environment.uuid}/variables'
      ]; 
    }
    $this->setParameter('is_legacy', true);
    $this->setParameter('calls', $calls);
    parent::gather($sandbox);
  }
}
