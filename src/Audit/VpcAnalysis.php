<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Gather VPC information.
 */
class VpcAnalysis extends AbstractAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $app = $this->target['acquia.cloud.application']->export();
    $this->set('environment', $this->target['acquia.cloud.environment']->export());
    $this->set('app', $app);

    $servers = $this->target['aht.app_data']['environment_list'][$this->target['acquia.cloud.environment.name']]['servers'];
    $vpc_ids = [];
    foreach ($servers as $server) {
        if (array_key_exists('vpc_id', $server['info']) && !in_array($server['info']['vpc_id'], $vpc_ids)) {
            $vpc_ids[] = $server['info']['vpc_id'];
        }
    }
    $vpc_info = [];
    foreach ($vpc_ids as $id) {
        // Fetch VPC information using AHT command.
        // Command: aht vpc:info <vpc_id> -> aht vpc:info $id
    }
    $this->set('app_info', $servers);
    $this->set('vpc_info', $vpc_info);
  }
}
