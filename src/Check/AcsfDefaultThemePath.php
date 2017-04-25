<?php

namespace Drutiny\Acquia\Check;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Sandbox\Sandbox;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "ACSF default theme path",
 *  description = "Ensure there are no hard coded references to the default theme path in the deployed theme as this can and will cause a lot of HTTP 404s.",
 *  remediation = "Use a preprocess hook, and inject the path to the asset, using a function such as <code>drupal_get_path()</code>.",
 *  success = "No default theme paths found.",
 *  failure = "Default theme path issue:plural :prefix found - <ul><li><code>:issues</code></li></ul>",
 *  exception = "Could not determine usage of default theme paths.",
 *  not_available = "No custom theme is linked.",
 * )
 */
class AcsfDefaultThemePath extends Check {

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();

    $root = $stat['root'];
    $site = $stat['site'];

    $look_out_for = "sites\/all\/themes\/";

    // This command is probably more complex then it should be due to wanting to
    // remove the main theme folder prefix.
    //
    // Yields something like:
    //
    // ./zen/template.php:159:    $path = drupal_get_path_alias($_GET['q']);
    // ./zen/template.php:162:    $arg = explode('/', $_GET['q']);.
    $command = CMD<<<
    grep -nrI --exclude=*.txt --exclude=*.md $look_out_for $root/$site/themes/site/;
    CMD;

    $output = $sandbox->exec($command);
    var_dump($output);
    return FALSE;
    $output = (string) $this->context->remoteExecutor->execute($command);

    // The ACSF site can have no custom theme repo linked, in which case we
    // will see a "du: cannot access ... No such file or directory" error
    // response. For now, we force this command to not fail using an or
    // statement.
    if (preg_match('/^nope$/', $output)) {
      throw new DoesNotApplyException();
    }

    if (preg_match('/^nothemeissues/', $output)) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    // Output from find is a giant string with newlines to seperate the files.
    $rows = explode("\n", $output);
    $rows = array_map('trim', $rows);
    $rows = array_map('htmlspecialchars', $rows);
    $rows = array_filter($rows);

    $this->setToken('issues', implode('</code></li><li><code>', $rows));
    $this->setToken('plural', count($rows) > 1 ? 's' : '');
    $this->setToken('prefix', count($rows) > 1 ? 'were' : 'was');

    return count($rows) === 0;
  }

}
