<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Token;
use Drutiny\Annotation\Param;
use Drutiny\ExpressionLanguage;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Acquia\Audit\EnvironmentAnalysis;

/**
 * Server Flags are used to identify how the server is being used and the available services on them.
 *
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaluate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "not_applicable",
 *  type = "string",
 *  default = "false",
 *  description = "The expression language to evaluate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "negate",
 *  type = "boolean",
 *  description = "Reverse the outcome of the expression.",
 *  default = FALSE
 * )
 * @Token(
 *  name = "servers",
 *  type = "array",
 *  description = "The list of custom environment variables for a particular Acquia Cloud environment."
 * )
 * @Token(
 *  name = "flags",
 *  type = "array",
 *  description = "The list of server flags for a particular Acquia Cloud environment."
 * )
 */
class ServerFlagsAnalysis extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $ea = new EnvironmentAnalysis();
    $ea->gather($sandbox);

    $servers = $sandbox->getParameter('servers');
    $flags=[];

    foreach ($servers['_embedded']['items'] as $server) {
      $servers[$server['name']] = $server;
      $flags[$server['name']] = $server['flags'];
    }

    // Create the servers and flags tokens
    $sandbox->setParameter('servers', $servers);
    $sandbox->setParameter('flags', $flags);

    $hasMatches = $this->evaluateFlags($sandbox);

    return $sandbox->getParameter('negate', false) ? !$hasMatches : $hasMatches;
  }

  /**
   * Evaluate each server's flags against the expression. Server details that
   * match the expression will be saved in the 'servers' token.
   * @param \Drutiny\Sandbox\Sandbox $sandbox
   * @return bool|int Returns TRUE if flags matched the expression, false
   * otherwise.
   */
  public function evaluateFlags(Sandbox $sandbox)
  {
    $expressionLanguage = new ExpressionLanguage($sandbox);

    $variables = $sandbox->getParameterTokens();

    $sandbox->logger()->debug(__CLASS__ . ':TOKENS ' . Yaml::dump($variables));

    $expression = $sandbox->getParameter('not_applicable', 'false');
    $sandbox->logger()->debug(__CLASS__ . ':INAPPLICABILITY ' . $expression);
    if (@$expressionLanguage->evaluate($expression, $variables)) {
      return self::NOT_APPLICABLE;
    }

    $expression = $sandbox->getParameter('expression', 'true');
    $matches = [];

    // Evaluate each server's flags against the expression
    // and save the matches.
    foreach ($variables['flags'] as $server_name => $flags) {
      if (@$expressionLanguage->evaluate($expression, $flags)) {
        $matches[$server_name] = $server_name;
      }
    }

    // Remove all servers that did not match.
    $servers = array_intersect_key($sandbox->getParameter('servers', []), $matches);

    // Save the server info that matched the expression
    $sandbox->setParameter('servers', array_values($servers));

    return !empty($matches);
  }
}
