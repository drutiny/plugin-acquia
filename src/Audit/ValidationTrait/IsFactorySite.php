<?php

namespace Drutiny\Acquia\Audit\ValidationTrait;
use Drutiny\Sandbox\Sandbox;

trait IsFactorySite {

  /**
   * Detect that this site runs on Acquia Cloud Site Factory.
   *
   * @param Sandbox $sandbox
   * @return bool
   */
  protected function requireIsFactorySite(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();
    return strpos($stat['files'], 'sites/g/files') === 0;
  }

}
