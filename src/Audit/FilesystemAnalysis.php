<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;
use Drutiny\Annotation\Param;

/**
 * Audit the usage of the filesystem.
 *
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "not_applicable",
 *  type = "string",
 *  default = "false",
 *  description = "The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "unit",
 *  description = "the unit of measurement to describe the volume usage in. E.g. B,M,G,T.",
 *  default = "G"
 * )
 */
class FilesystemAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $unit = $sandbox->getParameter('unit', "G");

    // Report file system disk space usage based on the unit
    $output = $sandbox->exec("df -B$unit | grep gfs");

    // Remove all occurrences the storage unit and % from the output.
    // This will allow the values to be used in conditional expressions.
    $output = str_replace([$unit,'%'], '', $output);

    list($volume, $capacity, $used, $free, $usage, $mountpoint) = array_values(array_filter(preg_split("/\t|\s/", $output)));

    $sandbox->setParameter('filesystem', [
      'volume' => $volume,
      'capacity' => (int)$capacity,
      'used' => (int)$used,
      'free' => (int)$free,
      'percent_used' => (int)$usage,
      'mountpoint' => $mountpoint,
      'unit' => $unit,
    ]);
  }
}