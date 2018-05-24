<?php

namespace Drutiny\Acquia;

use Drutiny\Target\DrushTarget;

/**
 * @Drutiny\Annotation\Target(
 *  name = "acquia"
 * )
 */
class AcquiaTarget extends DrushTarget {

  protected $environment;

  /**
   * Parse target data.
   */
  public function parse($target_data) {
    // Matches drush style alias syntax.
    if (preg_match('/^@[a-z0-9]+\.[a-z0-9]+$/', $target_data)) {
      parent::parse($target_data);
      $this->environment = CloudApiDrushAdaptor::getEnvironment($this);

      $env_id = $this->environment['id'];
      $this->sandbox()->logger()->info("Environment id is $env_id. Recommend refering to target as acquia:$env_id to optmise target load time.");
    }
    // Look for Acquia Cloud API v2 UUID.
    elseif (preg_match('/^(([a-z0-9]+)-){5}([a-z0-9]+)$/', $target_data)) {
      $env_id = $target_data;
      $this->sandbox()->logger()->info("Loading environment from API...");
      $this->environment = CloudApiV2::get('environments/' . $env_id);

      if (!isset($this->options['ac-site'])) {
        list($this->options['remote-user'], $this->options['remote-host']) = explode('@', $this->environment['ssh_url']);
        list($this->options['ac-site'], $this->options['ac-env']) = explode('.', $this->options['remote-user']);
        $this->options['ac-realm'] = explode('.', $this->environment['default_domain'])[1];
        $this->options['root'] = strtr('/var/www/html/remote-user/docroot', $this->options);
        $this->alias = '@' . $this->options['remote-user'];
      }
    }
    else {
      throw new \Exception("Unknown target data: $target_data.");
    }

    if (!isset($this->uri)) {
      $this->options['uri'] = end($this->environment['domains']);
      $this->setGlobalDefaultOption('uri', end($this->environment['domains']));
    }

    return $this;
  }

  public function getEnvironment()
  {
    return $this->environment;
  }

  /**
   * @inheritdoc
   * Overrides DrushTrait::runCommand().
   */
  public function runCommand($method, $args, $pipe = '') {
    $command = strtr('@pipe drush @alias @options @method @args', [
      '@method' => $method,
      '@args' => implode(' ', $args),
      '@options' => implode(' ', $this->drushOptions),
      '@alias' => $this->alias,
      '@pipe' => $pipe,
    ]);
    return $this->exec($command);
  }
}


 ?>
