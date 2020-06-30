<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * API client for CSKB.
 */
class SourceApi {
  const CLIENT_ID = 'db65ca21-43cb-4840-9418-d8153e43d61d';
  const CLIENT_SECRET = 'letmein';

  protected $client;
  protected $cache;
  protected $credentials;

  public function __construct(Client $client, ContainerInterface $container, CacheInterface $cache)
  {
      $this->cache = $cache;
      $this->credentials = $container->get('credentials')->setNamespace('acquia:drutiny_api');
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

  protected function getToken(array $token = null):array
  {
    // When no refresh_token exists, a new token must be generated.
    return $this->cache->get('acquia.api.token', function (ItemInterface $item) {
        $response = $this->client->post('oauth/token', ['form_params' => [
          'grant_type' => 'client_credentials',
          'client_id' => static::CLIENT_ID,
          'client_secret' => static::CLIENT_SECRET
          ]]);
        $token = json_decode($response->getBody(), true);
        $token['timestamp'] = time();
        return $token;
    });
  }

  /**
   * Retrieve a list of policies.
   */
  public function getPolicyList() {
      return $this->cache->get('acquia.api.policy_list', function (ItemInterface $item) {
          $token = $this->getToken();
          $limit = 100;
          $offset = 0;
          $result = [];

          do {
              $response = json_decode($this->client->get('jsonapi/node/policy', [
                'query' => [
                  'filter[status][value]' => 1,
                  'filter[field_scope_visibility][value]' => 'external',
                  'fields[node--policy]' => 'field_name,field_class,title',
                  'fields[taxonomy_term--drutiny_audit_classes]' => 'name',
                  'include' => 'field_class',
                  'page[limit]' => $limit,
                  'page[offset]' => $offset
                ],
                'headers' => [
                  'Authorization' => $token['token_type'] . ' ' . $token['access_token'],
                ]
                ])->getBody(), true);

              foreach ($response['data'] as $row) {
                  $uuid = $row['relationships']['field_class']['data']['id'];
                  $term = $this->findEntity($response, $uuid);
                  $row['attributes']['class'] = $term['attributes']['name'];
                  $row['attributes']['uuid'] = $row['id'];
                  $result[] = $row['attributes'];
              }
              $offset += $limit;
          }
          while (count($response['data']) >= $limit);

          return $result;
      });
  }

  public function getPolicy($uuid)
  {
    return $this->cache->get('acquia.api.policy.'.$uuid, function (ItemInterface $item) use ($uuid) {
        $token = $this->getToken();
        $response = json_decode($this->client->get('jsonapi/node/policy/'.$uuid, [
          'query' => [
            'filter[status][value]' => 1,
            'filter[field_scope_visibility][value]' => 'external',
            // 'fields[node--policy]' => 'field_name,field_class,title',
            //'fields[taxonomy_term--drutiny_audit_classes]' => 'name',
            //'fields[taxonomy_term--]'
            'include' => 'field_tags',
          ],
          'headers' => [
            'Authorization' => $token['token_type'] . ' ' . $token['access_token'],
          ]
          ])->getBody(), true);
        return $response;
        });
  }

  protected function findEntity($response, $uuid)
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
  public function getProfileList() {
      return json_decode($this->client->get('profile/list')->getBody(), TRUE);
  }

}
