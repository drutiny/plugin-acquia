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

    $sql = "SELECT CONCAT(TABLE_SCHEMA, ':', ROUND(SUM(data_length + index_length) / 1024 / 1024, 1)) as size
            FROM information_schema.tables
            GROUP BY table_schema;";

    $results = $this->target->getService('drush')->sqlq($sql)->run(function ($output) {
      return explode("\n", trim($output));
    });
    $db_sizes = [];
    // We will get result like [<db_name>:<size>].
    // Format and convert it to array.
    foreach ($results as $result) {
      $db = explode(":", trim($result));
      $db_sizes[$db[0]] = $db[1];
    }

    $this->set('databases', array_map(function ($database) use ($sandbox, $db_sizes) {
      // Extract the machine_name from the db_url.
      $strArray = explode('/',$database['url']);
      $db_machine_name = end($strArray);

      $database['machine_name'] = $db_machine_name;
      $database['size'] = array_key_exists($db_machine_name, $db_sizes) ? $db_sizes[$db_machine_name] : 0;
      return $database;
    }, $data['_embedded']['items']));

  }

}
