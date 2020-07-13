<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Http\Client;
use Drutiny\LanguageManager;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * API client for CSKB.
 */
class SourceApi {

  protected $client;
  protected $cache;

  public function __construct(Client $client, CacheInterface $cache, ContainerInterface $container)
  {
      $this->cache = $cache;
      $this->client = $client->create([
        'base_uri' => $container->getParameter('acquia.api.base_uri'),
        'headers' => [
          'User-Agent' => 'drutiny-cli/3.x',
          'Accept' => 'application/vnd.api+json',
          'Accept-Encoding' => 'gzip',
        ],
        'decode_content' => 'gzip',
        'allow_redirects' => FALSE,
        'connect_timeout' => 10,
        'verify' => FALSE,
        'timeout' => 300,
      ]);
  }

  /**
   * Retrieve a list of policies.
   */
  public function getPolicyList(LanguageManager $languageManager) {
      $lang_code = $languageManager->getCurrentLanguage();

      return $this->cache->get('acquia.api.policy_list.'.$lang_code, function (ItemInterface $item) use ($lang_code) {
          $offset = 0;
          $result = [];

          // To retrive content in the specified language we must seek the API
          // within the respective language.
          $prefix = $lang_code == 'en' ? '/' : $lang_code.'/';

          do {
              $response = json_decode($this->client->get($prefix.'jsonapi/node/policy', [
                'query' => [
                  'filter[status][value]' => 1,
                  'filter[field_scope_visibility][value]' => 'external',
                  // Only include content that contains a translations for the
                  // specified language.
                  'filter[langcode]' => $lang_code,
                  'fields[node--policy]' => 'field_name,field_class,title',
                  'fields[taxonomy_term--drutiny_audit_classes]' => 'name',
                  'include' => 'field_class',
                  'page[offset]' => $offset
                ],
                ])->getBody(), true);

              foreach ($response['data'] as $row) {
                  $uuid = $row['relationships']['field_class']['data']['id'];
                  $term = $this->findEntity($response, $uuid);
                  $row['attributes']['class'] = $term['attributes']['name'];
                  $row['attributes']['uuid'] = $row['id'];
                  $result[] = $row['attributes'];
                  $offset++;
              }
          }
          while (isset($response['links']['next']) && count($response['data']));

          return $result;
      });
  }

  /**
   * Retrieve full data on a single policy.
   */
  public function getPolicy($uuid, $lang_code = 'en'):array
  {
    return $this->cache->get('acquia.api.policy.'.$uuid.'.'.$lang_code, function (ItemInterface $item) use ($uuid, $lang_code) {
        $prefix = $lang_code == 'en' ? '/' : $lang_code.'/';
        $response = json_decode($this->client->get($prefix.'jsonapi/node/policy/'.$uuid, [
          'query' => [
            'filter[status][value]' => 1,
            'filter[field_scope_visibility][value]' => 'external',
            'filter[langcode]' => $lang_code,
            'include' => 'field_tags',
          ],
          ])->getBody(), true);
        return $response;
        });
  }

  /**
   * Pull an entity from the included key in JSON:API response.
   */
  protected function findEntity(array $response, $uuid):array
  {
      foreach ($response['included'] as $include) {
          if ($include['id'] == $uuid) {
              return $include;
          }
      }
  }

  /**
   * Retrieve a list of profiles.
   */
  public function getProfileList(LanguageManager $languageManager):array
  {
    $lang_code = $languageManager->getCurrentLanguage();
    return $this->cache->get('acquia.api.profile_list.'.$lang_code, function (ItemInterface $item) use ($lang_code) {
        $limit = 100;
        $offset = 0;
        $result = [];

        // To retrive content in the specified language we must seek the API
        // within the respective language.
        $prefix = $lang_code == 'en' ? '/' : $lang_code.'/';

        do {

            $response = json_decode($this->client->get($prefix.'jsonapi/node/profile', [
              'query' => [
                'filter[status][value]' => 1,
                'filter[field_scope_visibility][value]' => 'external',
                // Only include content that contains a translations for the
                // specified language.
                'filter[langcode]' => $lang_code,
                'fields[node--profile]' => 'title,field_name',
                'page[limit]' => $limit,
                'page[offset]' => $offset
              ],
              ])->getBody(), true);

            foreach ($response['data'] as $row) {
                $row['attributes']['uuid'] = $row['id'];
                $result[] = $row['attributes'];
            }
            $offset += $limit;
        }
        while (count($response['data']) >= $limit);

        return $result;
    });
  }

  /**
   * Pull the full data object of a profile.
   */
  public function getProfile($uuid)
  {
    return $this->cache->get('acquia.api.profile.'.$uuid, function (ItemInterface $item) use ($uuid) {
        $response = json_decode($this->client->get('jsonapi/node/profile/'.$uuid, [
          'query' => [
            'filter[status][value]' => 1,
            'filter[field_scope_visibility][value]' => 'external',
          ],
          ])->getBody(), true);
        return $response['data'];
        });
  }
}
