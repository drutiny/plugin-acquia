<?php

namespace Drutiny\Acquia;

use Drutiny\Http\Client;
use Drutiny\Container;
use GuzzleHttp\Exception\ConnectException;

/**
 * API client for CSKB.
 */
class CskbApi {

  /**
   * {@inheritdoc}
   */
  public function __construct($base_url = 'https://cskb.acquia.com/o/drutiny-api/') {
    $this->baseUrl = $base_url;
  }

  /**
   * Fetch the API client.
   */
  public static function getClient($base_url) {
    return new Client([
      'base_uri' => $base_url,
      'headers' => [
        'User-Agent' => 'drutiny-cli/3.x',
        'Accept' => 'application/json',
        'Accept-Encoding' => 'gzip',
      ],
      'decode_content' => 'gzip',
      'allow_redirects' => FALSE,
      'connect_timeout' => 10,
      'verify' => FALSE,
      'timeout' => 300,
    ]);
  }

  /**
   * Retrieve a list of policies.
   */
  public function getPolicyList() {
    try {
      return json_decode($this->getClient($this->baseUrl)->get('policy/list')->getBody(), TRUE);
    }
    catch (ConnectException $e) {
      Container::getLogger()->warning($e->getMessage());
      return [];
    }
  }

  /**
   * Retrieve a list of profiles.
   */
  public function getProfileList() {
    try {
      return json_decode($this->getClient($this->baseUrl)->get('profile/list')->getBody(), TRUE);
    }
    catch (ConnectException $e) {
      Container::getLogger()->warning($e->getMessage());
      return [];
    }
  }

}
