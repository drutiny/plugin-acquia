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

    $res = $this->getApiClient($sandbox)->request('GET', 'sites/' . $sitename . '.json');

    $site = json_decode($res->getBody(), TRUE);
    $enabled = (bool) $site['production_mode'];
    return $enabled;
  }

}
