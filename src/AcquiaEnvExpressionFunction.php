<?php

namespace Drutiny\Acquia;

use Drutiny\Annotation\ExpressionSyntax;
use Drutiny\Sandbox\Sandbox;
use Drutiny\ExpressionFunction\ExpressionFunctionInterface;

/**
 * @ExpressionSyntax(
 * name = "AcquiaEnv",
 * usage = "AcquiaEnv()",
 * description = "Returns the environment object from Acquia Cloud API v2."
 * )
 */
class AcquiaEnvExpressionFunction implements ExpressionFunctionInterface {
  static public function compile(Sandbox $sandbox)
  {
    return 'AcquiaEnv()';
  }

  static public function evaluate(Sandbox $sandbox)
  {
    $env = CloudApiDrushAdaptor::getEnvironment($sandbox->getTarget());
    return $env;
  }
}

 ?>
