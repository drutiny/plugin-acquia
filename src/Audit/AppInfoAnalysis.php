<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;

/**
 * Ensure an environment has custom domains set.
 */
class AppInfoAnalysis extends CloudApiAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    $calls['teams'] = [
      'path' => "/applications/{acquia.cloud.application.uuid}/teams",
    ];
    $calls['features'] = [
      'path' => "/applications/{acquia.cloud.application.uuid}/features" 
    ];
    $calls['identity_providers'] = [
      'path' => '/identity-providers'
    ];

    $this->setParameter('calls', $calls);
    $this->setParameter('is_legacy', true);
    parent::gather($sandbox);

    $members = [];
    foreach ($this->get('teams')['_embedded']['items'] as $team) {
      $team_members = $this->call(path: "/teams/{$team->uuid}/members", options: [
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
  }

}
