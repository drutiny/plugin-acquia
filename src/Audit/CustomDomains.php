<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;

/**
 * Ensure an environment has custom domains set.
 */
class CustomDomains extends Audit {

  protected function requireCloudApiV2()
  {
    return Manager::load('acquia_api_v2');
  }

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $environment = CloudApiDrushAdaptor::getEnvironment($sandbox->getTarget());

    $domains = array_filter($environment['domains'], function ($domain) {
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
