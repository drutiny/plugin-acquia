<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;

/**
 * Ensure an environment has custom domains set.
 */
class AppInfo extends Audit {

  protected function requireCloudApiV2()
  {
    return Manager::load('acquia_api_v2');
  }

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $options = $sandbox->getTarget()->getOptions();
    $app = CloudApiDrushAdaptor::findApplication($options['ac-realm'], $options['ac-site']);
    $sandbox->setParameter('app', $app);

    return Audit::NOTICE;
  }

}
