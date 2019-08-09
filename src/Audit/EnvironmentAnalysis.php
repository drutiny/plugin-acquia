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

    $sandbox->setParameter('environment', $environment);
    $sandbox->setParameter('app', $app);

    $client = CloudApiV2::getApiClient();

    $sandbox->setParameter('runtimes', $client->getAvailableRuntimes([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('cron', $client->getCronJobsByEnvironmentId([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('databases', $client->getEnvironmentsDatabases([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('dns', $client->getEnvironmentsDns([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('logs', $client->getEnvironmentsLogs([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('servers', $client->getEnvironmentsServers([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('apm_settings', $client->getEnvironmentsApmSetting([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('ssl_settings', $client->getSsl([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('certificates', $client->getCertificates([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('csrs', $client->getCertificateSigningRequests([
      'environmentId' => $environment['id']
    ]));

    $sandbox->setParameter('variables', $client->getEnvironmentsVariables([
      'environmentId' => $environment['id']
    ]));
  }

}
