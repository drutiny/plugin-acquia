<?php

namespace Drutiny\Acquia\DomainList;

use Drutiny\DomainList\AbstractDomainList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Acquia Site Factory Domain List.
 */
class AcquiaSiteFactoryDomainList extends AbstractDomainList {
  /**
   * The maximum number of sites returned in a single API command to Site
   * Factory.
   *
   * @see https://www.[YOURACSF].acsitefactory.com/api/v1#List-sites
   */
  const LIST_SITES_LIMIT = 100;

  public function __construct(ContainerInterface $container, CacheInterface $cache)
  {
    $this->credentials = $container->get('credentials')->load('acsf:api');
    $this->client = $container->get('http.client');
    $this->cache = $cache;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function configure()
  {
      $this->addOption('factory', 'The domain of the site factory console.');
      $this->addOption('primary-only', 'Use to select domains from sites that are primary.');
      $this->addOption('no-internal', 'Do not return any domains that come from an Acquia owned apex.');
      $this->addOption('stack', 'Integer. The stack number to pull domains for.');
  }



  /**
   * @return array list of domains.
   */
  public function getDomains(array $options = [])
  {
    if (!isset($options['factory'])) {
        return [];
    }
    if (!isset($this->credentials->{$options['factory']})) {
        return [];
    }
    $api_creds = $this->credentials->{$options['factory']};
    $client = $this->client->create([
      'base_uri' => 'https://' . $options['factory'] . '/api/v1/',
      'auth' => [$api_creds['username'], $api_creds['key']],
    ]);

    $page = 1;
    $sites = [];
    $count = 0;
    do {
      $response = $client->request('GET', 'sites', ['query' => [
        'limit' => self::LIST_SITES_LIMIT,
        'page' => $page,
      ]]);

      $json = json_decode($response->getBody(), TRUE);
      $count += count($json['sites']);

      foreach ($json['sites'] as $site) {
          if (isset($options['stack']) && ($site['stack_id'] != $options['stack'])) {
              continue;
          }
          if (isset($options['primary-only']) && !$site['is_primary']) {
              continue;
          }
          $sites[] = $site;
      }
      $page++;
    }
    while ($count < $json['count']);

    // Build an array of domains to represent each site. Sites with invalid
    // domains will contain a FALSE value to be filtered out later.
    $domains = array_map(function ($site) use ($client, $options) {
      try {
        $info = $this->cache->get('acsf.domains.'.$options['factory']. $site['id'], function ($item) use ($client, $site) {
          $response = $client->request('GET', 'domains/' . $site['id']);
          return json_decode($response->getBody(), TRUE);
        });

        // Custom domains take precedence over defaul "protected" domains.
        $domains = array_merge($info['domains']['custom_domains'], $info['domains']['protected_domains'], [$site['domain']]);

        // We only want to return a single domain for a site.
        // So if the filters are available we want to apply them here to
        // before we choose which domain in an array to return.
        $domains = array_filter($domains, function ($domain) use ($options) {
            if (isset($options['no-internal']) &&  (strpos($domain, '.acsitefactory.com') !== FALSE)) {
              return false;
            }
            return true;
        });

        $domains = $this::prioritySort($domains);

        // If there are domains that passed the filter,
        // use the first domain as the valid domain to use.
        if (count($domains)) {
          return reset($domains);
        }
        return FALSE;
      }
      catch (\Exception $e) {}
    }, $sites);

    return array_filter($domains);
  }

  /**
   * Priority ordering of domains. Ideally the domains that are most likely to
   * be the public website are listed at the top, and the domains that are most
   * likely to be internal only at the bottom.
   *
   * @param array $domains
   * @return array
   */
  public static function prioritySort(array $domains) {
    usort($domains, function ($a, $b) {
      if (strpos($a, 'www.') !== FALSE) {
        return -1;
      }
      if (strpos($b, 'www.') !== FALSE) {
        return 1;
      }
      if (strpos($a, '.acsitefactory.com') !== FALSE) {
        return 1;
      }
      if (strpos($b, '.acsitefactory.com') !== FALSE) {
        return -1;
      }
      // Else prefer generally shorter domains over longer one.
      return strlen($a) - strlen($b);
    });

    return $domains;
  }

}
