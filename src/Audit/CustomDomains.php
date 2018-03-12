<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * Ensure an environment has custom domains set.
 */
class CustomDomains extends CloudApiAwareCheck {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $opts = $sandbox->drush()->getOptions();

    $sitename = $opts['ac-realm'] . ':' . $opts['ac-site'];

    // https://cloudapi.acquia.com/v1/sites/realm:mysite/envs/prod/domains.json
    $domains = $this->api($sandbox, 'sites/' . $sitename . '/envs/' . $opts['ac-env'] . '/domains.json');

    $domains = array_filter($domains, function ($domain) {
      // Do not include ELB domains or Acquia default domains.
      return !(strpos($domain['name'], 'acquia-sites.com') || strpos($domain['name'], 'elb.amazonaws.com'));
    });

    if (empty($domains)) {
      return FALSE;
    }

    $sandbox->setParameter('domains', $domains);

    return TRUE;
  }

}
