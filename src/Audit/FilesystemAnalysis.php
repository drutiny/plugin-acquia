<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class FilesystemAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $output = $sandbox->exec('df -h | grep gfs');
    list($volume, $capacity, $used, $free, $usage, $mountpoint) = array_values(array_filter(preg_split("/\t|\s/", $output)));
    $this->set('filesystem', [
      'volume' => $volume,
      'capacity' => $capacity,
      'used' => $used,
      'free' => $free,
      'usage' => $usage,
      'mountpoint' => $mountpoint,
    ]);
  }

}
