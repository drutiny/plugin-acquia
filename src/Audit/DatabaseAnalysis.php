<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class DatabaseAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $data = $sandbox->getParameter('databases');
    $sandbox->setParameter('databases', array_map(function ($database) use ($sandbox) {

      $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size
              FROM information_schema.tables
              WHERE table_schema='{$database['name']}'
              GROUP BY table_schema;";

      $result = $sandbox->drush()->sqlq($sql);
      $database['size'] = $result[0];
      return $database;
    }, $data['_embedded']['items']));

  }

}
