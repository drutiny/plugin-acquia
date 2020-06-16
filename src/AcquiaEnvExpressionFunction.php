<?php

namespace Drutiny\Acquia;

use Drutiny\ExpressionLanguage\Func\FunctionInterface;
use Drutiny\ExpressionLanguage\Func\ExpressionFunction;
use Drutiny\Target\TargetInterface;

/**
 * Returns the environment object from Acquia Cloud API v2.
 */
class AcquiaEnvExpressionFunction extends ExpressionFunction implements FunctionInterface {

  private $target;

  public function __construct(TargetInterface $target)
  {
    $this->target = $target;
  }

  public function getName()
  {
      return 'AcquiaEnv';
  }

  public function getCompiler()
  {
      return function () {
        return 'AcquiaEnv()';
      };
  }

  public function getEvaluator()
  {
      return function ($args) {
          return CloudApiDrushAdaptor::getEnvironment($this->target);
      };
  }
}

 ?>
