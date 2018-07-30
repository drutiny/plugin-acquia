<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Annotation\Param;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\AcquiaTargetInterface;
use Drutiny\Acquia\CloudApiV2;

/**
 * Ensure an environment has custom domains set.
 * @Param(
 *  name = "metrics",
 *  description = "one of apache-requests, bal-cpu, bal-memory, cron-memory, db-cpu, db-disk-size, db-disk-usage, db-memory, file-disk-size, file-cpu, file-disk-usage, file-memory, http-2xx, http-3xx, http-4xx, http-5xx, mysql-slow-query-count, nginx-requests, out-of-memory, php-proc-max-reached-site, php-proc-max-reached-total, php-proc-site, php-proc-total, varnish-cache-hit-rate, varnish-requests, web-cpu, web-memory ",
 *  type = "array",
 *  default = {"web-cpu", "web-memory"}
 * )
 */
class StackMetrics extends AbstractAnalysis {

  protected function requireCloudApiV2(Sandbox $sandbox)
  {
    return Manager::load('acquia_api_v2');
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    $target = $sandbox->getTarget();
    $env = ($target instanceof AcquiaTargetInterface) ? $target->getEnvironment() : CloudApiDrushAdaptor::getEnvironment($target);

    $metrics = $sandbox->getParameter('metrics');

    $response = CloudApiV2::get('environments/' . $env['id'] . '/metrics/stackmetrics/data', [
      'filter' => implode(',', array_map(function ($metric) {
        return 'metric:' . $metric;
      }, $metrics)),
      'from' => $sandbox->getReportingPeriodStart()->format(\DateTime::ISO8601),
      'to' => $sandbox->getReportingPeriodEnd()->format(\DateTime::ISO8601),
    ]);

    $table_headers = [];
    $table_rows = [];


    foreach ($response['_embedded']['items'] as $item) {
      $name = empty($item['host']) ? $item['metric'] : sprintf('%s:%s', $item['host'], $item['metric']);
      $table_headers[] = $name;
    }
    sort($table_headers);

    array_unshift($table_headers, 'Date');

    foreach ($response['_embedded']['items'] as &$item) {
      $name = empty($item['host']) ? $item['metric'] : sprintf('%s:%s', $item['host'], $item['metric']);

      $idx = array_search($name, $table_headers);
      foreach ($item['datapoints'] as &$plot) {
        // Convert unix timestamp plot point to readable datetime.
        if (!isset($table_rows[$plot[1]])) {
          $table_rows[$plot[1]] = [date('Y-m-d H:i:s', $plot[1])];
        }

        $table_rows[$plot[1]][$idx] = $plot[0];
      }
    }

    // Sort the table columns by index.
    array_walk($table_rows, 'ksort');

    $sandbox->setParameter('result', $response);
    $sandbox->setParameter('env', $env);
    $sandbox->setParameter('table_headers', $table_headers);
    $sandbox->setParameter('table_rows', array_values($table_rows));
  }

}
