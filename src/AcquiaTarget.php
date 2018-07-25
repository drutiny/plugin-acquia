<?php

namespace Drutiny\Acquia;

use Drutiny\Container;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Driver\DrushRouter;

/**
 * @Drutiny\Annotation\Target(
 *  name = "acquia"
 * )
 */
class AcquiaTarget extends DrushTarget implements AcquiaTargetInterface {

  protected $environment;

  public function validate()
  {
    $drush = DrushRouter::createFromTarget($this);
    $drush->setOptions([
      'format' => 'json',
    ]);
    $status = $drush->status();

    if (!isset($status['files'])) {
      throw new InvalidTargetException("Drush status indicates target is not valid: " . $this->uri());
    }
    return parent::validate();
  }

  /**
   * Parse target data.
   */
  public function parse($target_data) {
    // Matches drush style alias syntax.
    if (preg_match('/^@[a-z0-9]+\.[a-z0-9]+$/', $target_data)) {
      parent::parse($target_data);
      $this->environment = CloudApiDrushAdaptor::getEnvironment($this);

      $env_id = $this->environment['id'];
      Container::getLogger()->info("Environment id is $env_id. Recommend refering to target as acquia:$env_id to optmise target load time.");
    }
    // Look for Acquia Cloud API v2 UUID.
    elseif (preg_match('/^(([a-z0-9]+)-){5}([a-z0-9]+)$/', $target_data)) {
      $env_id = $target_data;
      Container::getLogger()->info("Loading environment from API...");
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
      throw new InvalidTargetException("Unknown target data: $target_data.");
    }

    if (!$this->uri()) {
      $this->setUri(end($this->environment['domains']));
    }

    return $this;
  }

  public function getEnvironment()
  {
    return $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function runDrushCommand($method, array $args, array $options, $pipe = '')
  {
    return $this->exec('@pipe @bin @alias @options @method @args', [
      '@method' => $method,
      '@alias' => $this->getAlias(),
      '@bin' => 'drush-launcher',
      '@args' => implode(' ', $args),
      '@options' => implode(' ', $options),
      '@pipe' => $pipe
    ]);
  }
}


 ?>
