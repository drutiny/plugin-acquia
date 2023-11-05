<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Attribute\DataProvider;
use Drutiny\Attribute\Deprecated;

/**
 * Retrieve all custom environment variables for a particular Acquia Cloud environment.
 */
#[Deprecated]
class EnvironmentVariableAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  #[DataProvider(1)]
  public function gatherEnvVars() {

    // Grab the environment and sitegroup name
    $app = $this->target['acquia.cloud.application']->export();
    $hosting_id = explode(':', $app['hosting']['id']);
    list($env, $sitegroup) = $hosting_id;

    // Replace the %sitegroup and %env placeholders if they were used in the expression.
    $expression = $this->getParameter('expression');
    $expression = strtr($expression, ['%sitegroup' => $sitegroup, '%env' => $env]);
    $this->set('expression', $expression);

    // Create environment and sitegroup tokens
    $this->set('env', $env);
    $this->set('sitegroup', $sitegroup);

    $data = $this->get('variables');
    $variables=[];

    if (!empty($data)) {
      foreach ($data as $item) {
        $variables[$item->name] = $item->value;
      }
    }

    // Create the variables token
    $this->set('variables', $variables);
  }

}
