<?php

namespace Drutiny\Acquia\Check;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Sandbox\Sandbox;

/**
 * ACSF default theme path.
 */
class AcsfDefaultThemePath extends Check {
  use ValidationTrait\IsFactorySite;

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();

    $root = $stat['root'];
    $site = $stat['site'];

    $look_out_for = "sites\/all\/themes\/";

    $command = "grep -nrI --exclude=*.txt --exclude=*.md $look_out_for $root/$site/themes/site/ || exit 0;";

    $output = $sandbox->exec($command);
    $lines = explode(PHP_EOL, $output);

    $sandbox->setParameter('issues', $lines);
    $sandbox->setParameter('plural', count($lines) > 1 ? 's' : '');
    $sandbox->setParameter('prefix', count($lines) > 1 ? 'were' : 'was');

    return empty($lines);
  }

}
