<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * Ensure there is no use of search indexes in the database.
 * @Token(
 *  name = "indexes",
 *  type = "array",
 *  description = "An array of index names found in the database. Only available on failure."
 * )
 * @Token(
 *  name = "items",
 *  type = "integer",
 *  description = "The number of indexed items in the database. Only available on failure."
 * )
 */
class SearchDB extends Audit {

  public function audit(Sandbox $sandbox) {

    // Find out if there are active indexes using the db service class.
    $output = $sandbox->drush()->sqlq("SELECT COUNT(i.machine_name) as count FROM {search_api_index} i LEFT JOIN {search_api_server} s ON i.server = s.machine_name WHERE i.status > 0 AND s.class = 'search_api_db_service';");
    if (empty($output)) {
      return TRUE;
    }
    elseif (count($output) == 1) {
      $number_of_db_indexes = (int) $output[0];
    }
    else {
      $number_of_db_indexes = (int) $output[1];
    }

    $this->set('indexes', $number_of_db_indexes);

    $output = $sandbox->drush()->sqlq('SELECT COUNT(item_id) FROM {search_api_db_default_node_index};');

    $this->set('items', $output);

    return FALSE;
  }

}
