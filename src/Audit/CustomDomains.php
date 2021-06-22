<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * Ensure an environment has custom domains set.
 */
class CustomDomains extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $domains = array_filter($this->target['acquia.cloud.environment.domains'], function ($domain) {
      // Do not include ELB domains or Acquia default domains.
      return !(strpos($domain, 'acquia-sites.com') || strpos($domain, 'elb.amazonaws.com') || strpos($domain, 'acsitefactory.com'));
    });

    if (empty($domains)) {
      return FALSE;
    }

    $this->set('domains', array_values($domains));

    return TRUE;
  }

}
