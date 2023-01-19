<?php

namespace Drutiny\Acquia;

use Drutiny\ExpressionLanguage\Func\FunctionInterface;
use Drutiny\ExpressionLanguage\Func\ExpressionFunction;
use Drutiny\Target\TargetInterface;
use Closure;

/**
 * Returns the environment object from Acquia Cloud API v2.
 */
class AcquiaEnvExpressionFunction extends ExpressionFunction implements FunctionInterface {

  private $target;

  public function __construct(TargetInterface $target)
  {
    $this->target = $target;
  }

  public function getName(): string
  {
      return 'AcquiaEnv';
  }

  public function getCompiler():Closure
  {
      return function () {
        return 'target("acquia.cloud.environment")';
      };
  }

  public function getEvaluator():Closure
  {
      return function ($args) {
          return $this->target['acquia.cloud.environment']->export();
      };
  }
}

 ?>
