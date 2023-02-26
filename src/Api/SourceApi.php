<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drutiny\Acquia\Plugin\CskbEndpoint;
use Drutiny\Attribute\Plugin;
use Drutiny\Attribute\PluginField;
use Drutiny\Plugin as DrutinyPlugin;
use Drutiny\Plugin\FieldType;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client as GuzzleClient;

#[Plugin(name: 'cskb:endpoint')]
#[PluginField(
  name: 'base_url',
  description: "Where to find CSKB (https://cskb.acquia.com)",
  type: FieldType::CONFIG,
  default: 'https://cskb.acquia.com/'
)]
class SourceApi {

  protected GuzzleClient $client;
  protected array $config;

  public function __construct(Client $client, protected LoggerInterface $logger, DrutinyPlugin $plugin)
  {
      $this->client = $client->create([
        'base_uri' => $plugin->base_url,
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

  public function get(string $endpoint, array $params = [])
  {
      return json_decode($this->client->get($endpoint, $params)->getBody(), true);
  }

  public function send($request, $options)
  {
    return $this->client->send($request, $options);
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
