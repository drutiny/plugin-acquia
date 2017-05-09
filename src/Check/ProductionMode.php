<?php

namespace Drutiny\Acquia\Check;

use Drutiny\Sandbox\Sandbox;

/**
 * ACSF default theme path.
 */
class ProductionMode extends CloudApiAwareCheck {

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $opts = $sandbox->drush()->getOptions();
    $sitename = $opts['ac-realm'] . ':' . $opts['ac-site'];
    $site = $this->getCloudApiClient()->site($sitename);

    return $site->productionMode();
  }

}
