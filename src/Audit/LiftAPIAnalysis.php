<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\LiftProfileManagerClient;
use Drutiny\Annotation\Param;

/**
 * Audit gathered data.
 *
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "api",
 *  type = "string",
 *  default = "customer_sites",
 *  description = "The name of the account API endpoint: http://docs.lift.acquia.com/profilemanager/#"
 * )
 * @Param(
 *  name = "not_applicable",
 *  type = "string",
 *  default = "false",
 *  description = "The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 */
class LiftAPIAnalysis extends AbstractAnalysis {

  protected function requireAPICreds()
  {
    return Manager::load('acquia_lift');
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $config = $sandbox->drush([
      'format' => 'json',
      'include-overridden' => NULL,
      ])->configGet('acquia_lift.settings');

    foreach ($config as $key => $value) {
      $sandbox->setParameter($key, $value);
    }

    $api_call = $sandbox->getParameter('api');

    $response = LiftProfileManagerClient::get()->get(strtr('/@account/@api_call', [
      '@account' => $config['credential']['account_id'],
      '@api_call' => $api_call
    ]));

    $body = $response->getBody();
    $sandbox->setParameter('response', $json = json_decode($body, TRUE));

    if ($response->getStatusCode() != 200) {
      throw new ResponseException($response, strtr('error: message', $json['value']));
    }
  }

}
