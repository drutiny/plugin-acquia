<?php

namespace Drutiny\Acquia\Api;

class AcquiaCliNotAvailableException extends \Exception {
  public function __construct()
  {
    parent::__construct("Acquia CLI could not be found. Please ensure Acquia CLI is installed locally on your system.");
  }
}