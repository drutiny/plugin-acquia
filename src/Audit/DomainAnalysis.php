<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;

/**
 * Check to ensure Production Mode is enabled on Acquia Cloud.
 */
class DomainAnalysis extends EnvironmentAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $env = $this->getParameter('environment');
    $this->set('domains', array_map(function ($domain) {
      $record = exec(sprintf('dig +noall +answer %s | head -1', $domain));
      $record = preg_split("/\t|\s/", $record);
      $record = array_filter(array_map('trim', $record));
      if (empty($record) || (count($record) !== 5)) {
        return [
          'domain' => $domain,
          'ttl' => 'N/A',
          'type' => 'N/A',
          'value' => 'N/A',
          'status' => FALSE,
          'status_text' => 'unpublished'
        ];
      }
      list($domain, $ttl, $in, $type, $value) = $record;
      return [
        'domain' => $domain,
        'ttl' => $ttl,
        'type' => $type,
        'value' => $value,
        'status' => TRUE,
        'status_text' => 'published'
      ];
    }, $env['domains']));

  }

}
