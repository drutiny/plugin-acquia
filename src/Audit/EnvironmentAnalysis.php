<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class EnvironmentAnalysis extends AbstractAnalysis {

  protected function requireCloudApiV2()
  {
    return Manager::load('acquia_api_v2');
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $environment = CloudApiDrushAdaptor::getEnvironment($sandbox->getTarget());
    $app = CloudApiV2::get('applications/' . $environment['application']['uuid']);

    $this->set('environment', $environment);
    $this->set('app', $app);
    $client = CloudApiV2::getApiClient();

    // $this->set('runtimes', $client->getAvailableRuntimes([
    //   'environmentId' => $environment['id']
    // ]));

    $this->set('cron', $client->getCronJobsByEnvironmentId([
      'environmentId' => $environment['id']
    ]));

    $this->set('databases', $client->getEnvironmentsDatabases([
      'environmentId' => $environment['id']
    ]));

    $this->set('dns', $client->getEnvironmentsDns([
      'environmentId' => $environment['id']
    ]));

    // $this->set('logs', $client->getEnvironmentsLogs([
    //   'environmentId' => $environment['id']
    // ]));

    $this->set('servers', $client->getEnvironmentsServers([
      'environmentId' => $environment['id']
    ]));

    $this->set('apm_settings', $client->getEnvironmentsApmSetting([
      'environmentId' => $environment['id']
    ]));

    // $this->set('ssl_settings', $client->getSsl([
    //   'environmentId' => $environment['id']
    // ]));

    $this->set('certificates', $client->getCertificates([
      'environmentId' => $environment['id']
    ]));

    $this->set('csrs', $client->getCertificateSigningRequests([
      'environmentId' => $environment['id']
    ]));

    $this->set('variables', $client->getEnvironmentsVariables([
      'environmentId' => $environment['id']
    ]));
  }

}
