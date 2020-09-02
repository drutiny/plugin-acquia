<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class EnvironmentAnalysis extends AbstractAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $environment_id = $this->target['acquia.cloud.environment.id'];
    $app = $this->target['acquia.cloud.application']->export();
    $this->set('environment', $this->target['acquia.cloud.environment']->export());
    $this->set('app', $app);
    $client = $this->container->get('acquia.cloud.api')->getClient();

    // $this->set('runtimes', $client->getAvailableRuntimes([
    //   'environmentId' => $environment_id
    // ]));

    $this->set('cron', $client->getCronJobsByEnvironmentId([
      'environmentId' => $environment_id
    ]));

    $this->set('databases', $client->getEnvironmentsDatabases([
      'environmentId' => $environment_id
    ]));

    $this->set('dns', $client->getEnvironmentsDns([
      'environmentId' => $environment_id
    ]));

    // $this->set('logs', $client->getEnvironmentsLogs([
    //   'environmentId' => $environment_id
    // ]));

    $this->set('servers', $client->getEnvironmentsServers([
      'environmentId' => $environment_id
    ]));

    $this->set('apm_settings', $client->getEnvironmentsApmSetting([
      'environmentId' => $environment_id
    ]));

    // $this->set('ssl_settings', $client->getSsl([
    //   'environmentId' => $environment_id
    // ]));

    $this->set('certificates', $client->getCertificates([
      'environmentId' => $environment_id
    ]));

    $this->set('csrs', $client->getCertificateSigningRequests([
      'environmentId' => $environment_id
    ]));

    if ($app['hosting']['type'] != 'acsf') {
      $this->set('variables', $client->getEnvironmentsVariables([
        'environmentId' => $environment_id
      ]));
    }
  }
}
