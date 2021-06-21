<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;

/**
 * Retrieve all custom environment variables for a particular Acquia Cloud environment.
 */
class EnvironmentVariableAnalysis extends EnvironmentAnalysis {


    public function configure()
    {
        $this->addParameter(
          'expression',
          static::PARAMETER_OPTIONAL,
          'The expression language to evaluate. See https://symfony.com/doc/current/components/expression_language/syntax.html',
          true
        );

    }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    // Grab the environment and sitegroup name
    $app = $this->getParameter('app');
    $hosting_id = explode(':', $app['hosting']['id']);
    list($env, $sitegroup) = $hosting_id;

    // Replace the %sitegroup and %env placeholders if they were used in the expression.
    $expression = $this->getParameter('expression');
    $expression = strtr($expression, ['%sitegroup' => $sitegroup, '%env' => $env]);
    $this->set('expression', $expression);

    // Create environment and sitegroup tokens
    $this->set('env', $env);
    $this->set('sitegroup', $sitegroup);

    $data = $this->getParameter('variables');
    $variables=[];

    foreach ($data['_embedded']['items'] as $item) {
      $variables[$item['name']] = $item['value'];
    }

    // Create the variables token
    $this->set('variables', $variables);
  }

}
