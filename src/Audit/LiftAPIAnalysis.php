<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Audit gathered data.
 *
 */
class LiftAPIAnalysis extends AbstractAnalysis {


    public function configure()
    {
        $this->addParameter(
          'expression',
          static::PARAMETER_OPTIONAL,
          'The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html',
          true
        );
        $this->addParameter(
          'api',
          static::PARAMETER_OPTIONAL,
          'The name of the account API endpoint: http://docs.lift.acquia.com/profilemanager/#',
          'customer_sites'
        );
        $this->addParameter(
          'not_applicable',
          static::PARAMETER_OPTIONAL,
          'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
          false
        );
    }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
      $drush = $this->getTarget()->getService('drush');
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
        throw new ResponseException($response, strtr('error: message', $json['value']));
      }
  }

}
