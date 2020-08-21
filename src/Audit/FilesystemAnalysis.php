<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;


/**
 * Audit the usage of the filesystem.
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
 *  name = "unit",
 *  type = "string",
 *  description = "the unit of measurement to describe the volume usage in. E.g. B,M,G,T.",
 *  default = "G"
 * )
 * @Param(
 *  name = "filesystem",
 *  type = "array",
 *  description = "the storage usage information for both disk and inodes.",
 * )
 */
class FilesystemAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $unit = $sandbox->getParameter('unit', "G");

    // Report file system disk space and inode usage.
    $output = $sandbox->exec("df -B$unit | grep gfs && df --inodes | grep gfs");
    list($disk, $inode) = array_values(explode(PHP_EOL,$output));

    // Remove all occurrences the storage unit and % from the output.
    // This will allow the values to be used in conditional expressions.
    $disk = str_replace([$unit,'%'], '', $disk);

    // Parse the usage data into variables.
    list($disk_volume, $disk_capacity, $disk_used, $disk_free, $disk_usage, $disk_mountpoint) = array_values(array_filter(preg_split("/\t|\s/", $disk)));
    list($inode_volume, $inode_capacity, $inode_used, $inode_free, $inode_usage, $inode_mountpoint) = array_values(array_filter(preg_split("/\t|\s/", $inode)));

    $sandbox->setParameter('filesystem', [
      'disk' => [
        'volume' => $disk_volume,
        'capacity' => (int)$disk_capacity,
        'used' => (int)$disk_used,
        'free' => (int)$disk_free,
        'percent_used' => (int)$disk_usage,
        'mountpoint' => $disk_mountpoint,
        'unit' => $unit,
      ],
      'inode' => [
        'volume' => $inode_volume,
        'capacity' => (int)$inode_capacity,
        'used' => (int)$inode_used,
        'free' => (int)$inode_free,
        'percent_used' => (int)$inode_usage,
        'mountpoint' => $inode_mountpoint,
      ]
    ]);
  }
}