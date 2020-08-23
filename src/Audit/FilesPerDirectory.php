<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Sandbox\Sandbox;

/**
 * @Token(
 *  name = "directories",
 *  description = "A multidimensional array containing directory data breaching the limit.",
 *  default = {},
 *  type = "array"
 * )
 */
class FilesPerDirectory extends Audit {


    public function configure()
    {
        $this->addParameter(
          'limit',
          static::PARAMETER_OPTIONAL,
          'The limit of files per directory.'  
        );

    }

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    // Build a count of files in every directory in the public filesystem.
    $stat = $sandbox->drush(['format' => 'json'])->status();
    $files = implode('/', [$stat['root'], $stat['files']]);
    $limit = $this->getParameter('limit');

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

    $this->set('directories', $directories);

    return FALSE;
  }

}
