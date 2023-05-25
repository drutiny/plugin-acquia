<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Audit\Exception\AuditException;

/**
 * Audit gathered data.
 *
 */
class LiftAPIAnalysis extends AbstractAnalysis {
  /**
   * @inheritdoc
   */
  public function gather() {
      $drush = $this->target->getService('drush');
      $command = $drush->configGet('acquia_lift.settings', [
        'format' => 'json',
        'include-overridden' => true,
      ]);
      $config = $command->run(function ($output) {
        return json_decode($output, true);
      });

      foreach ($config as $key => $value) {
        $this->set($key, $value);
      }

      $api_call = $this->getParameter('api');

      $client = $this->container->get('acquia.lift.api');

      $response = $client->get(strtr('/@account/@api_call', [
        '@account' => $config['credential']['account_id'],
        '@api_call' => $api_call
      ]));

      $body = $response->getBody();
      $this->set('response', $json = json_decode($body, TRUE));

      if ($response->getStatusCode() != 200) {
        throw new AuditException(strtr('error: message', $json['value']));
      }
  }
}
