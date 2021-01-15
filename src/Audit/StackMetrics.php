<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Acquia\AcquiaTargetInterface;
use Drutiny\AuditValidationException;
use Drutiny\Report\Format\HTML;
use Drutiny\Report\Twig\Helper;

/**
 *
 * @Param(
 *  name = "maintain-aspect-ratio",
 *  description = "Maintain the original canvas aspect ratio (width / height) when resizing.",
 *  type = "boolean",
 *  default = TRUE
 * )
 */
class StackMetrics extends AbstractAnalysis {


    public function configure()
    {
        parent::configure();
        
        $this->addParameter(
          'metrics',
          static::PARAMETER_OPTIONAL,
          'one of apache-requests, bal-cpu, bal-memory, cron-memory, db-cpu, db-disk-size, db-disk-usage, db-memory, file-disk-size, file-cpu, file-disk-usage, file-memory, http-2xx, http-3xx, http-4xx, http-5xx, mysql-slow-query-count, nginx-requests, out-of-memory, php-proc-max-reached-site, php-proc-max-reached-total, php-proc-site, php-proc-total, varnish-cache-hit-rate, varnish-requests, web-cpu, web-memory ',
        );
        $this->addParameter(
          'chart-type',
          static::PARAMETER_OPTIONAL,
          'The type of graph, either bar or line.',
          'bar'
        );
        $this->addParameter(
          'chart-height',
          static::PARAMETER_OPTIONAL,
          'The height of the graph in pixels.'
        );
        $this->addParameter(
          'chart-width',
          static::PARAMETER_OPTIONAL,
          'The width of the graph in pixels.'
        );
        $this->addParameter(
          'y-axis-label',
          static::PARAMETER_OPTIONAL,
          'Custom label for the y-axis.',
          'Percentage'
        );
        $this->addParameter(
          'stacked',
          static::PARAMETER_OPTIONAL,
          'Determines whether or not the graph data should be stacked.',
        );
        $this->addParameter(
          'maintain-aspect-ratio',
          static::PARAMETER_OPTIONAL,
          '',
        );
    }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    $api = $this->container->get('acquia.cloud.api')->getClient();
    $env = $this->target['acquia.cloud.environment.id'];

    $metrics = $this->getParameter('metrics');

    if (!is_array($metrics)) {
      throw new AuditValidationException("Metrics parameter must be an array. " . ucwords(gettype($metrics)) . ' given.');
    }

    $response = $api->getClient()->get('environments/' . $env . '/metrics/stackmetrics/data', ['query' => [
      'filter' => implode(',', array_map(function ($metric) {
        return 'metric:' . $metric;
      }, $metrics)),
      'from' => $sandbox->getReportingPeriodStart()->format(\DateTime::ISO8601),
      'to' => $sandbox->getReportingPeriodEnd()->format(\DateTime::ISO8601),
    ]]);
    $response = json_decode($response->getBody(), true);

    $table_headers = ['Date'];
    $table_rows = [];

    foreach ($response['_embedded']['items'] as $item) {
      if (!empty($item['metadata']['host'])) {
        list($item['name'],) = explode('.', $item['metadata']['host'], 2);
      }
      if (!isset($item['name'])) {
        $item['name'] = $item['metric'];
      }
      elseif (count($metrics) > 1) {
        $item['name'] .= ':' . $item['metric'];
      }
      $table_headers[] = $item['name'];

      $idx = array_search($item['name'], $table_headers);
      foreach ($item['datapoints'] as $plot) {
        // $y == value
        // $x == epoch
        list($y, $x) = $plot;

        // Convert unix timestamp plot point to readable datetime.
        if (!isset($table_rows[$x])) {
          $table_rows[$x] = [ date('Y-m-d H:i:s', $x) ];
        }

        $table_rows[$x][$idx] = $y;
      }
    }

    // Sort the table columns by index.
    array_walk($table_rows, 'ksort');

    $this->set('result', $response);
    $this->set('env', $env);
    $this->set('table_headers', $table_headers);
    $this->set('table_rows', array_values($table_rows));

    // graph

    $graph = [
      'type' => 'line',
      'labels' => 'tr td:first-child',
      'hide-table' => TRUE,
      'height' => $this->getParameter('chart-height', 500),
      //'width' => $this->getParameter('chart-width', 400),
      'stacked' => $this->getParameter('stacked',FALSE),
      'y-axis' => $this->getParameter('y-axis-label','Percentage'),
      'maintain-aspect-ratio' => $this->getParameter('maintain-aspect-ratio',TRUE),
      'title' => $this->getPolicy()->title,
      'series' => [],
      'series-labels' => [],
      'legend' => 'bottom',
    ];

    foreach ($table_headers as $idx => $name) {
      if ($name == 'Date') {
        continue;
      }
      $nth = $idx + 1;
      $graph['series'][] = 'tr td:nth-child(' . $nth . ')';
      $graph['series-labels'][] = 'tr th:nth-child(' . $nth . ')';
    }
    $graph['series'] = implode(',', $graph['series']);
    $graph['series-labels'] = implode(',', $graph['series-labels']);

    $this->set('graph', $graph);
  }

}
