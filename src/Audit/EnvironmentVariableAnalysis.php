<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;

/**
 * Retrieve all custom environment variables for a particular Acquia Cloud environment.
 * @Token(
 *  name = "env",
 *  type = "string",
 *  description = "The Acquia Cloud Environment."
 * )
 * @Token(
 *  name = "sitegroup",
 *  type = "string",
 *  description = "The name of the sitegroup (docroot)."
 * )
 * @Token(
 *  name = "variables",
 *  type = "array",
 *  description = "The list of custom environment variables for a particular Acquia Cloud environment."
 * )
 */
class EnvironmentVariableAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);
    
    // Grab the environment and sitegroup name
    $app = $sandbox->getParameter('app');
    $hosting_id = explode(':', $app['hosting']['id']);
    list($env, $sitegroup) = $hosting_id;

    // Replace the %sitegroup and %env placeholders if they were used in the expression.
    $expression = $sandbox->getParameter('expression');
    $expression = strtr($expression, ['%sitegroup' => $sitegroup, '%env' => $env]);
    $sandbox->setParameter('expression', $expression);

    // Create environment and sitegroup tokens
    $sandbox->setParameter('env', $env);
    $sandbox->setParameter('sitegroup', $sitegroup);

    $data = $sandbox->getParameter('variables');
    $variables=[];

    foreach ($data['_embedded']['items'] as $item) {
      $variables[$item['name']] = $item['value'];
    }

    // Create the variables token
    $sandbox->setParameter('variables', $variables);
  }

}
