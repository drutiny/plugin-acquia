<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Http\Client;
use Drutiny\LanguageManager;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drutiny\Acquia\Plugin\CskbEndpoint;

/**
 * API client for CSKB.
 */
class SourceApi {

  protected $client;
  protected CacheInterface $cache;
  protected array $config;

  public function __construct(Client $client, CacheInterface $cache, ContainerInterface $container, CskbEndpoint $plugin)
  {
      $this->cache = $cache;
      $this->config = $plugin->load();
      $this->client = $client->create([
        'base_uri' => $this->config['base_url'] ?? $container->getParameter('acquia.api.base_uri'),
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

  public function setCache(CacheInterface $cache)
  {
      $this->cache = $cache;
  }

  public function get(string $endpoint, array $params = [])
  {
      if (isset($this->config['share_key'])) {
        $params['query']['share'] = $this->config['share_key'];
      }
      $cid = 'acquia.api.'.hash('md5', $endpoint.http_build_query($params));
      return $this->cache->get($cid, function (ItemInterface $item) use ($endpoint, $params) {
        return json_decode($this->client->get($endpoint, $params)->getBody(), true);
      });
  }

  public function getList(string $endpoint, array $params = [])
  {
    $offset = 0;
    $result = [];
    do {
      $params['query']['page[offset]'] = $offset;
      $response = $this->get($endpoint, $params);

      foreach ($response['data'] as $row) {
          foreach ($row['relationships'] ?? [] as $field_name => $field) {
            if (empty($field['data'])) {
              continue;
            }
            $relations = isset($field['data'][0]) ? $field['data'] : [$field['data']];

            foreach ($relations as $link) {
              if ($entity = $this->findEntity($response, $link['id'])) {
                $row['attributes'][$field_name][] = $entity;
              }
            }
          }
          // $uuid = $row['relationships']['field_class']['data']['id'];
          // $term = $this->findEntity($response, $uuid);
          // $row['attributes']['class'] = $term['attributes']['name'];
          $row['attributes']['uuid'] = $row['id'];
          $result[] = $row['attributes'];
          $offset++;
      }
    }
    while (isset($response['links']['next']) && count($response['data']));
    return $result;
  }

  /**
   * Pull an entity from the included key in JSON:API response.
   */
  protected function findEntity(array $response, $uuid):array
  {
      foreach ($response['included'] ?? [] as $include) {
          if ($include['id'] == $uuid) {
              return $include;
          }
      }
      return [];
  }
}
