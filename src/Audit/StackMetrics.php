<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Acquia\Api\CloudApi;
use Drutiny\Attribute\DataProvider;
use Drutiny\Audit\AuditValidationException;
use Drutiny\Attribute\Deprecated;
use Drutiny\Attribute\Parameter;

/**
 * 
 */
#[Deprecated('Use ' . CloudApiAnalysis::class)]
#[Parameter('metrics', enums: [
  'apache-requests', 'bal-cpu', 'bal-memory', 'cron-memory', 'db-cpu', 'db-disk-size', 'db-disk-usage', 'db-memory', 'file-disk-size', 'file-cpu', 'file-disk-usage', 'file-memory', 'http-2xx', 'http-3xx', 'http-4xx', 'http-5xx', 'mysql-slow-query-count', 'nginx-requests', 'out-of-memory', 'php-proc-max-reached-site', 'php-proc-max-reached-total', 'php-proc-site', 'php-proc-total', 'varnish-cache-hit-rate', 'varnish-requests', 'web-cpu', 'web-memory'
], description: 'A metric to pull.')]
#[Parameter('chart-type','The type of graph, either bar or line.', enums: ['bar', 'line'] )]
#[Parameter('chart-height', 'The height of the graph in pixels.')]
#[Parameter('chart-width', 'The width of the graph in pixels.')]
#[Parameter('y-axis-label', 'Custom label for the y-axis.', default: 'Percentage')]
#[Parameter('stacked', 'Determines whether or not the graph data should be stacked.',)]
#[Parameter('maintain-aspect-ratio', '')]
class StackMetrics extends CloudApiAnalysis {


  #[DataProvider]
  public function gather(CloudApi $api) {
    $env = $this->target['acquia.cloud.environment.id'];

    $metrics = $this->getParameter('metrics');

    if (!is_array($metrics)) {
      throw new AuditValidationException("Metrics parameter must be an array. " . ucwords(gettype($metrics)) . ' given.');
    }

    $duration = ($this->reportingPeriodEnd->format('U') - $this->reportingPeriodStart->format('U'))/60;
    $resolution = 'minute';
    // > 180 minutes (3 hours) means we need to use a higher resolution.
    if ($duration > 180) {
      $resolution = 'hour';
    }
    // Greater than 12.5 days means we need to use a higher resolution.
    if ($duration > 18000) {
      $resolution = 'day';
    }

    $response = $this->call($api, verb: 'get', path: "/environments/{acquia.cloud.environment.uuid}/metrics/stackmetrics/data", options: [
      'query' => [
        'filter' => implode(',', array_map(function ($metric) {
          return 'metric:' . $metric;
        }, $metrics)),
        'from' => $this->reportingPeriodStart->format(\DateTime::ATOM),
        'to' => $this->reportingPeriodEnd->format(\DateTime::ATOM),
        'resolution' => $resolution,
      ]
    ]);

    $table_headers = ['Date'];
    $table_rows = [];

    foreach ($response as $item) {
      if (!empty($item->metadata->host)) {
        list($item->name,) = explode('.', $item->metadata->host, 2);
      }
      if (!isset($item->name)) {
        $item->name = $item->metric;
      }
      elseif (count($metrics) > 1) {
        $item->name .= ':' . $item->metric;
      }
      $table_headers[] = $item->name;

      $idx = array_search($item->name, $table_headers);
      foreach ($item->datapoints as $plot) {
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
