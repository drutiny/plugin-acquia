<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * Ensure an environment has not registered wildcard domains.
 */
class WildcardDomains extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $domains = array_filter($this->target['acquia.cloud.environment.domains'], function ($domain) {
        return str_contains($domain, '*');
    });

    if (empty($domains)) {
      return TRUE;
    }

    $this->set('domains', array_values($domains));

    return FALSE;
  }

}
