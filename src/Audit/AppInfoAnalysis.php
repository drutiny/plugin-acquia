<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Ensure an environment has custom domains set.
 */
class AppInfoAnalysis extends AbstractAnalysis {

  public function configure()
  {
    parent::configure();
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    $client = $this->container->get('acquia.cloud.api')->getClient();
    $app = $this->target['acquia.cloud.application']->export();
    $this->set('drush', $this->target['drush']->export());

    $this->set('app', $app);

    // $this->set('databases', $client->getApplicationDatabases([
    //   'applicationUuid' => $app['uuid'],
    // ]));
    //
    // $this->set('hosting_settings', $client->getApplicationHostingSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $this->set('legacy_product_keys_settings', $client->getApplicationLegacyProductKeysSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $this->set('remote_administration_settings', $client->getApplicationRemoteAdministrationSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $this->set('search_settings', $client->getApplicationSearchSettings([
    //   'applicationUuid' => $app['uuid']
    // ]));
    //
    // $this->set('security_settings', $client->getApplicationSecuritySettings([
    //   'applicationUuid' => $app['uuid']
    // ]));

    $this->set('teams', $teams = $client->getApplicationTeams([
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

    $this->set('members', array_values($members));

    $this->set('features', $client->getApplicationFeatures([
      'applicationUuid' => $app['uuid']
    ]));

    $this->set('identity_providers', $client->getIdentityProviders());

  }

}
