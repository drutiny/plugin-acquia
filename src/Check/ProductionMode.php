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
    


    return FALSE;
  }

}
