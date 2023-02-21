<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Process\Process;

/**
 *  A multidimensional array containing directory data breaching the limit.
 */
class FilesPerDirectory extends Audit {


    public function configure():void
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
    $files = implode('/', [$this->target['drush.root'], $this->target['drush.files']]);
    $limit = $this->getParameter('limit');

    // Note the trailing slash at the end of $files to ensure find works over
    // symlinks.
    $command = Process::fromShellCommandline("find $files/ -type f -exec dirname {} \; | uniq -c | awk '\$1 > $limit'");
    $directories = $this->target->execute($command, function ($output) {
      $lines = array_filter(explode(PHP_EOL, $output));
      $directories = [];
      foreach ($lines as $line) {
        list($count, $directory) = explode(' ', trim($line));
        $directories[] = [
          'directory' => str_replace($this->target['drush.root'] . '/', '', $directory),
          'file_count' => $count,
        ];
      }
      return $directories;
    });

    // No limits breached, return as pass.
    if (empty($directories)) {
      return TRUE;
    }

    $this->set('directories', $directories);

    return FALSE;
  }

}
