<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\AcquiaTargetInterface;
use Drutiny\Acquia\CloudApiV2;

/**
 * Ensure an environment has custom domains set.
 */
class AppInfo extends Audit {

  protected function requireCloudApiV2()
  {
    return Manager::load('acquia_api_v2');
  }

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {

    $target = $sandbox->getTarget();
    if ($target instanceof AcquiaTargetInterface) {
      $env = $target->getEnvironment();
      $app = CloudApiV2::get('applications/' . $env['application']['uuid']);
    }
    else {
      $options = $target->getOptions();
      $app = CloudApiDrushAdaptor::findApplication($options['ac-realm'], $options['ac-site']);
    }
    $sandbox->setParameter('app', $app);

    $client = CloudApiV2::getApiClient();

    // $sandbox->setParameter('databases', $client->getApplicationDatabases([
    //   'applicationUuid' => $app['uuid'],
    // ]));
    //
    // $sandbox->setParameter('hosting_settings', $client->getApplicationHostingSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $sandbox->setParameter('legacy_product_keys_settings', $client->getApplicationLegacyProductKeysSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $sandbox->setParameter('remote_administration_settings', $client->getApplicationRemoteAdministrationSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $sandbox->setParameter('search_settings', $client->getApplicationSearchSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $sandbox->setParameter('security_settings', $client->getApplicationSecuritySettings([
    //   'applicationUuid' => $app['uuid']
    // ]));

    $sandbox->setParameter('teams', $teams = $client->getApplicationTeams([
      'applicationUuid' => $app['uuid']
    ]));

    $members = [];
    foreach ($teams['_embedded']['items'] as $team) {
      $team_members = $client->getTeamMembers([
        'teamUuid' => $team['uuid'],
        'limit' => 100
      ]);

      foreach ($team_members['_embedded']['items'] as $team_member) {
        $is_new = !isset($members[$team_member['uuid']]);
        $member = $members[$team_member['uuid']] ?? $team_member;

        foreach ($team_member['roles'] as $role) {
          $member['team_roles'][] = sprintf('%s (%s)', $role['name'], $team['name']);
        }
        $members[$member['uuid']] = $member;
      }
    }

    $sandbox->setParameter('members', array_values($members));

    $sandbox->setParameter('features', $client->getApplicationFeatures([
      'applicationUuid' => $app['uuid']
    ]));

    $sandbox->setParameter('identity_providers', $client->getIdentityProviders());

    return Audit::NOTICE;
  }

}
