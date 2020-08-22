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
 * Analyze the various properties of cron tasks.
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
 *  name = "cron",
 *  type = "array",
 *  description = "An array of cron items that match the expression."
 * )
 */
class CronAnalysis extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $ea = new EnvironmentAnalysis();
    $ea->gather($sandbox);

    $expressionLanguage = new ExpressionLanguage($sandbox);
    $variables = $sandbox->getParameterTokens();
    $na_expression = $sandbox->getParameter('not_applicable', 'false');
    $expression = $sandbox->getParameter('expression', 'true');

    // Check if the policy is applicable or not
    $sandbox->logger()->debug(__CLASS__ . ':INAPPLICABILITY ' . $na_expression);
    if (@$expressionLanguage->evaluate($na_expression, $variables)) {
      return self::NOT_APPLICABLE;
    }

    // Evaluate each schedule task item against the expression and save the matches.
    $matches = [];
    foreach ($variables['cron']['_embedded']['items'] as $cron) {
      if (@$expressionLanguage->evaluate($expression, ['cron' => $cron])) {
        // Build the scheduled time.
        $cron['scheduled_time'] = $cron['minute'] . ' ' . $cron['hour'] . ' ' . $cron['day_month'] . ' ' . $cron['month'] . ' ' . $cron['day_week'];

        // Escape the asterisk (*) to prevent it from being interpreted by
        // markdown.
        $cron['scheduled_time'] = str_replace('*', '\*', $cron['scheduled_time']);

        $matches[] = $cron;
      }
    }

    // Save the cron info that matched the expression
    $sandbox->setParameter('cron', [
      'total' => count($matches),
      'items' => $matches
    ]);

    $sandbox->logger()->debug(__CLASS__ . ':TOKENS ' . Yaml::dump($sandbox->getParameter('cron')));

    return $sandbox->getParameter('negate', false) ? empty($matches) : !empty($matches);
  }
}
