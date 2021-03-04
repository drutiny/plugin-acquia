<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;

/**
 * Adds the database size to the database result set.
 */
class DatabaseAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $data = $this->get('databases');

    $this->set('databases', array_map(function ($database) use ($sandbox) {
      // Extract the machine_name from the db_url.
      $strArray = explode('/',$database['url']);
      $db_machine_name = end($strArray);

      $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size
              FROM information_schema.tables
              WHERE table_schema='{$db_machine_name}'
              GROUP BY table_schema;";

      $result = $this->target->getService('drush')->sqlq($sql)->run(function ($output) {
        return (float) trim($output);
      });

      //$result = $sandbox->drush()->sqlq($sql);

      $database['machine_name'] = $db_machine_name;
      $database['size'] = $result;
      return $database;
    }, $data['_embedded']['items']));

  }

}
