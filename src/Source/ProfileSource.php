<?php

namespace Drutiny\Acquia\Source;

use Drutiny\ProfileSource\ProfileSourceInterface;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\Profile;
use Drutiny\Profile\ProfileSource as DrutinyProfileSource;
use Drutiny\Report\Format;
use Drutiny\Acquia\Api\SourceApi;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load profiles from CSKB.
 */
class ProfileSource extends SourceBase implements ProfileSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function getList() {
    $list = [];
    foreach ($this->client->getProfileList() as $profile) {
      $list[$profile['field_name']] = [
        'name' => $profile['field_name'],
        'title' => $profile['title'],
        'uuid' => $profile['uuid'],
      ];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition) {

    $response = $this->client->getProfile($definition['uuid']);

    $fields = $response['attributes'];

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
