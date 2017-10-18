<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;

/**
 * ACSF default theme path.
 */
class AcsfDeployWorkspaceCleaned extends Audit implements RemediableInterface {
  use ValidationTrait\IsFactorySite;

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $output = $sandbox->exec('find /mnt/tmp/repo_* -maxdepth 1 -mindepth 1 -ctime +1 -type d');
    $lines = array_filter(explode(PHP_EOL, $output));

    $sandbox->setParameter('directories', $lines);

    return empty($lines);
  }

  public function remediate(Sandbox $sandbox) {
    $list = $sandbox->getParameter('directories');

    foreach ($list as $dir) {
        $sandbox->exec('rm -rf ' . $dir);
    }

    return $this->check($sandbox);
  }

}
