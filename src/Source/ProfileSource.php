<?php

namespace Drutiny\Acquia\Source;

use Drutiny\ProfileSource\ProfileSourceInterface;
use Drutiny\Profile;
use Drutiny\Profile\ProfileSource as DrutinyProfileSource;
use Drutiny\LanguageManager;
use Drutiny\Acquia\Api\SourceApi;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load profiles from CSKB.
 */
class ProfileSource extends SourceBase implements ProfileSourceInterface {

  const API_ENDPOINT = 'jsonapi/node/profile';

  /**
   * {@inheritdoc}
   */
  public function getList(LanguageManager $languageManager) {
    $list = [];
    $query = $this->getRequestParams();
    $query['query']['fields[node--profile]'] = 'title,field_name';

    foreach ($this->client->getList($this->getApiPrefix().self::API_ENDPOINT, $query) as $item) {
      $list[$item['field_name']] = [
        'name' => $item['field_name'],
        'title' => $item['title'],
        'uuid' => $item['uuid'],
        'language' => $languageManager->getCurrentLanguage(),
      ];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition) {
    $query = $this->getRequestParams();
    $endpoint = $this->getApiPrefix().self::API_ENDPOINT.'/'.$definition['uuid'];
    $response = $this->client->get($endpoint, $query);

    $fields = $response['data']['attributes'];

    $profile = $this->container->get('profile');
    $profile->setProperties([
      'title' => $fields['title'],
      'name' => $fields['field_name'],
      'uuid' => $definition['uuid'],
      'description' => $fields['field_description'],
      'policies' => Yaml::parse($fields['field_policies']),
      'excluded_policies' => !empty($fields['field_excluded_policies']) ? Yaml::parse($fields['field_excluded_policies']) : [],
      'include' => $fields['field_include'],
      'format' => [
        'html' => [
          'content' => Yaml::parse($fields['field_html_content']),
          'template' => $fields['field_html_template'],
        ]
      ]
    ]);

    return $profile;
  }
}
