<?php

namespace Drutiny\Acquia\Check\ValidationTrait;
use Drutiny\Sandbox\Sandbox;

trait IsFactorySite {

  /**
   * Detect that this site runs on Acquia Cloud Site Factory.
   */
  protected function requireIsFactorySite(Sandbox $sandbox)
  {
    $stat = $sandbox->drush(['format' => 'json'])->status();
    return strpos($stat['files'], 'sites/g/files') === 0;
  }
}

 ?>
