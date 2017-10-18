<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;

/**
 * ACSF default theme path.
 */
class ProductionMode extends CloudApiAwareCheck {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $opts = $sandbox->drush()->getOptions();
    $sitename = $opts['ac-realm'] . ':' . $opts['ac-site'];
    $site = $this->getCloudApiClient()->site($sitename);

    return $site->productionMode();
  }

}
