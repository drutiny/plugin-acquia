<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Entity\Exception\DataNotFoundException;
use Drutiny\Target\TargetInterface;

/**
 * Interact with Acquia Cloud API.
 */
interface CloudApiInterface {
    /**
     * Find an Acquia application by its ream and sitename.
     */
    public function findApplication($realm, $site):array;

    /**
     * Find an Acquia environment by its environment name.
     * 
     * @param string $uuid The application UUID.
     * @param string env The environment name.
     * @throws DataNotFoundException
     * @return mixed[]
     */
    public function findEnvironment($uuid, $env):array;
    /**
     * @return array[]
     */
    public function getEnvironments(string $uuid): array;

    /**
     * Get the Acquia environment by its UUID.
     */
    public function getEnvironment($uuid):array;

    public function getApplication($uuid):array;

    /**
     * Map cloud variables to the target.
     */
    public function mapToTarget(array $data, TargetInterface $target, $namespace):void;
}
