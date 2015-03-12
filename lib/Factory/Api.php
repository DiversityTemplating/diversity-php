<?php

namespace Diversity\Factory;

use Diversity\NotFoundException;
use Diversity\Component;
use Diversity\Factory;
use SAI_CurlInterface;

/**
 * Factory for getting Components out of a diversity-api server.
 */
class Api extends Factory {
  private $api_url, $curl_if;

  /**
   * @param string $api_url URL to a diversity-api, for example "https://api.diversity.io/".
   * @param SAI_CurlInterface
   */
  public function __construct($api_url, SAI_CurlInterface $curl_if) {
    $this->api_url = $api_url;
    $this->curl_if = $curl_if;
  }

  /**
   * Get a Component
   *
   * @return Diversity\Component
   */
  public function get($component, $version = null) {
    $url = $this->api_url . 'components/'
      . $component . '/' . ($version === null ? '*' : $version) . '/';

    $ch = $this->curl_if->curl_init($url);
    $this->curl_if->curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $spec_json = $this->curl_if->curl_exec($ch);

    if (!$spec_json) {
      throw new NotFoundException(
        "Didn't find a component at '$url': " . $this->curl_if->curl_error($ch)
      );
    }
    $spec = json_decode($spec_json);
    if (empty($spec)) throw new NotFoundException('Malformed json at ' . $url);

    // Rewrite URL from actual version.
    if ($spec->version !== $version) {
      $url = $this->api_url . 'components/' . $component . '/' . $spec->version . '/';
    }

    return new Component(
      $this,
      array(
        'spec'     => $spec,
        'location' => $url,
        'base_url' => $url . 'files/',
      )
    );
  }
}
