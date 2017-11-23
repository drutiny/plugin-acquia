<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

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

    $site = $this->api($sandbox, 'sites/' . $sitename . '.json');
    $enabled = (bool) $site['production_mode'];

    // ACE = prod, ACSF = [0-9]{2}live
    if (!in_array($opts['ac-env'], ['prod', '01live', '02live', '03live', '04live'])) {
      return $enabled ? Audit::WARNING : Audit::WARNING_FAIL;
    }

    return $enabled;
  }

}
