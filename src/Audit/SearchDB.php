<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Plugin\Drupal7\Audit\ModuleDisabled;

/**
 *
 */
class SearchDB extends ModuleDisabled {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

    $sandbox->setParameter('module', 'search_api_db');
    if (parent::audit($sandbox)) {
      return TRUE;
    }

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

    $sandbox->setParameter('indexes', $number_of_db_indexes);

    $output = $sandbox->drush()->sqlq('SELECT COUNT(item_id) FROM {search_api_db_default_node_index};');

    $sandbox->setParameter('items', $output);

    return FALSE;
  }

}
