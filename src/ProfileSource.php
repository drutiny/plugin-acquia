<?php

namespace Drutiny\Acquia;

use Drutiny\Profile\ProfileSourceInterface;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\Profile;
use Drutiny\Profile\ProfileSource as DrutinyProfileSource;
use Drutiny\Report\Format;
use Symfony\Component\Yaml\Yaml;

/**
 * Load profiles from CSKB.
 */
class ProfileSource implements ProfileSourceInterface {

  /**
   * Base URL of the API endpoint.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return parse_url($this->getBaseUrl(), PHP_URL_HOST);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl() {
    return 'https://cskb.acquia.com/o/drutiny-api/';
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {
    $api = new CskbApi($this->getBaseUrl());
    $list = [];
    foreach ($api->getProfileList() as $listedPolicy) {
      $list[$listedPolicy['name']] = $listedPolicy;
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition) {
    // Parse YAML files into respective PHP data types.
    $yaml_fields = [
      'format_html_content',
      'excluded_policies',
      'policies',
    ];
    foreach ($yaml_fields as $key) {
      if (!isset($definition[$key])) {
        continue;
      }
      if (empty($definition[$key])) {
        unset($definition[$key]);
        continue;
      }
      $definition[$key] = Yaml::parse($definition[$key]);

      if (!is_array($definition[$key])) {
        throw new \Exception("Profile field '$key' should be an array but " . gettype($definition[$key]) . " given.");
      }
    }

    $profile = new Profile();
    $profile->setTitle($definition['title'])
      ->setName($definition['name'])
      ->setFilepath($this->getName() . '/profile/' . $definition['name']);

    // Policies.
    if (isset($definition['policies'])) {
      foreach ($definition['policies'] as $name => $metadata) {
        $weight = array_search($name, array_keys($definition['policies']));
        $profile->addPolicyDefinition(PolicyDefinition::createFromProfile($name, $weight, $metadata));
      }
    }

    // Policies to exclude.
    if (isset($definition['excluded_policies'])) {
      $profile->addExcludedPolicies($definition['excluded_policies']);
    }

    // Additional Profiles to include.
    if (isset($definition['include'])) {
      foreach ($definition['include'] as $name) {
        $include = DrutinyProfileSource::loadProfileByName($name);
        $profile->addInclude($include);
      }
    }

    // Formatting options.
    $format_html_options = [];
    if (isset($definition['format_html_content'])) {
      $format_html_options['content'] = $definition['format_html_content'];
    }
    if (isset($definition['format_html_template'])) {
      $format_html_options['template'] = $definition['format_html_template'];
    }
    if (!empty($format_html_options)) {
      $profile->addFormatOptions(Format::create('html', $format_html_options));
    }

    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return -90;
  }

}
