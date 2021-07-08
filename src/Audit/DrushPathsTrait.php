<?php

namespace Drutiny\Acquia\Audit;

/**
 * Utility for `drush status` path variables.
 */
trait DrushPathsTrait
{
    /**
     * @var array<string, string>
     */
    protected ?array $paths = NULL;

    /**
     * Expand placeholders in the path.
     */
    protected function expandPath(string $path): string
    {
        return strtr($path, $this->getPaths());
    }

    /**
     * Dynamic getter for $paths.
     *
     * @return array<string, string>
     */
    protected function getPaths(): array
    {
        if ($this->paths === NULL) {
            $stat = $this->target
                ->getService('drush')
                ->status(['format' => 'json'])
                ->run(function($output) {
                    return json_decode($output, true);
                });

            // Backwards compatibility. %paths is no longer present since Drush 8.
            if (!isset($stat['%paths'])) {
                foreach ($stat as $key => $value) {
                    $stat['%paths']['%'.$key] = $value;
                }
            }

            $this->paths = $stat['%paths'];
        }

        return $this->paths;
    }
}
