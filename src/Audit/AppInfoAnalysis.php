<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Attribute\DataProvider;
use Drutiny\Attribute\Deprecated;

/**
 * Ensure an environment has custom domains set.
 */
#[Deprecated]
class AppInfoAnalysis extends CloudApiAnalysis {

  /**
   * @inheritdoc
   */
  #[DataProvider(-1)]
  public function gatherAppInfo() {
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

  }

  #[DataProvider(1)]
  public function processAppInfo(CloudApi $api) {

    $members = [];
    foreach ($this->get('teams')['_embedded']['items'] as $team) {
      $team_members = $this->call($api, path: "/teams/{$team->uuid}/members", options: [
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

    $this->set('members', $members);
  }
}
