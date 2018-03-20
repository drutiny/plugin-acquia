<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * @Param(
 *  name = "limit",
 *  description = "The limit of files per directory.",
 *  default = 2500,
 *  type = "integer"
 * )
 * @Token(
 *  name = "directories",
 *  description = "A multidimensional array containing directory data breaching the limit.",
 *  default = {},
 *  type = "array"
 * )
 */
class FilesPerDirectory extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    // Build a count of files in every directory in the public filesystem.
    $stat = $sandbox->drush(['format' => 'json'])->status();
    $files = implode('/', [$stat['root'], $stat['files']]);
    $limit = $sandbox->getParameter('limit');

    // Note the trailing slash at the end of $files to ensure find works over
    // symlinks.
    $command = "find $files/ -type f -exec dirname {} \; | uniq -c | awk '\$1 > $limit'";

    // Execute and clean the output into usable data.
    $output = $sandbox->exec($command);
    $lines = array_filter(explode(PHP_EOL, $output));
    $directories = [];
    foreach ($lines as $line) {
      list($count, $directory) = explode(' ', trim($line));
      $directories[] = [
        'directory' => str_replace($stat['root'] . '/', '', $directory),
        'file_count' => $count,
      ];
    }

    // No limits breached, return as pass.
    if (empty($directories)) {
      return TRUE;
    }

    $sandbox->setParameter('directories', $directories);

    return FALSE;
  }

}
