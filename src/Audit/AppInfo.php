<?php

namespace Drutiny\Acquia\Audit;

use AcquiaCloudApi\Endpoints\IdentityProviders;
use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * Ensure an environment has custom domains set.
 */
class AppInfo extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $api = $this->container->get(CloudApi::class);
    $app = $this->target['acquia.cloud.application']->export();

    // @deprecated
    // Use the target directly instead.
    $this->set('drush', $this->target['drush']->export());
    $this->set('app', $app);

    $client = $api->getApiClient();

    $teams = $client->request('get', "/applications/{acquia.cloud.application.uuid}/teams");

    $this->set('teams', $teams = $client->request('get', "/applications/{$app['uuid']}/teams"));

    $members = [];
    foreach ($teams as $team) {
      $team_members = $client->request('get', "/teams/{$team->uuid}/members",[
        'query' => ['limit' => 100]
      ]);

      foreach ($team_members as $team_member) {
        $member = $members[$team_member->uuid] ?? $team_member;

        foreach ($team_member->roles as $role) {
          $member->team_roles[] = sprintf('%s (%s)', $role->name, $team->name);
        }
        $members[$member->uuid] = $member;
      }
    }

    $this->set('members', array_values($members));

    $features = $client->request('get', "/applications/{$app['uuid']}/features");
    $this->set('features', $features);
    $this->set('identity_providers', (new IdentityProviders($client))->getAll());

    return Audit::NOTICE;
  }

}
