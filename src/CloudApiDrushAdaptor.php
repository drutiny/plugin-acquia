<?php

namespace Drutiny\Acquia;

use Drutiny\Target\DrushTarget;

class CloudApiDrushAdaptor {

  public static function getEnvironment(DrushTarget $target)
  {
    if (($target instanceof AcquiaTarget) && $target->getEnvironment()) {
      return $target->getEnvironment();
    }
    $options = $target->getOptions();

    if (!isset($options['ac-realm'], $options['ac-site'], $options['ac-env'])) {
      throw new \Exception("Acquia Cloud metadata not found in drush alias. Please ensure drush aliases are downloaded from https://cloud.acquia.com.");
    }

    $app = self::findApplication($options['ac-realm'], $options['ac-site']);
    $env = self::findEnvironment($app['uuid'], $options['ac-env']);
    return CloudApiV2::get('environments/' . $env['id']);
  }

  public static function findApplication($realm, $site)
  {
    $apps = CloudApiV2::get('applications');

    foreach ($apps['_embedded']['items'] as $app) {
      if (empty($app['hosting'])) {
        continue;
      }
      list($stage, $sitegroup) = explode(':', $app['hosting']['id'], 2);

      if ($realm == $stage && $site == $sitegroup) {
        return $app;
      }
    }
    throw new \Exception("Cannot find Acquia application matching target criteria: {$options['ac-realm']}:{$options['ac-site']}.");
  }

  public static function findEnvironment($uuid, $env)
  {
    $environments = CloudApiV2::get("applications/$uuid/environments");

    foreach ($environments['_embedded']['items'] as $environment) {
      if ($environment['name'] == $env) {
        return $environment;
      }
    }
    throw new \Exception("Cannot find Acquia application environment: $env.");
  }
}
