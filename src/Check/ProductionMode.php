<?php

namespace Drutiny\Acquia\Check;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Sandbox\Sandbox;

/**
 * ACSF default theme path.
 */
class ProductionMode extends Check {
  use ValidationTrait\HasCloudApiAccess;

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $opts = $sandbox->drush()->getOptions();
    $path = '/sites/' . $opts['ac-realm'] . ':' . $ops['ac-site'] . '.json';

    $response = $this->cloudApiRequest('GET', $path);

    print_r(json_decode($response->getBody()));


    return FALSE;
  }

}
