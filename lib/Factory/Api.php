<?php

namespace Diversity\Factory;

use Diversity\NotFoundException;
use Diversity\Component;
use Diversity\Factory;
use SAI\Curl;

/**
 * Factory for getting Components out of a diversity-api server.
 */
class Api extends Factory {
  private $api_url, $curl_if, $instances;

  /**
   * @param string $api_url URL to a diversity-api, for example "https://api.diversity.io/".
   * @param SAI\Curl
   */
  public function __construct($api_url, Curl $curl_if) {
    $this->api_url = $api_url;
    $this->curl_if = $curl_if;
  }

  /**
   * Get a Component
   *
   * @return Diversity\Component
   */
  public function get($component, $version = null) {
    if (isset($this->instances[$component])) return $this->instances[$component];

    if ($version[0] === '^') {
      // Caret ^ ranges - Allow changes that do not modify the left-most non-zero digit.
      $version_parts = explode('.', substr($version, 1)); // Split into parts

      $version = '';
      foreach ($version_parts as $part) {
        $version .= $part;
        if ($part !== '0') break;
        $version .= '.';
      }
    }

    $url = $this->api_url . 'components/'
      . $component . '/' . ($version === null ? '*' : $version) . '/';
    $ch = $this->curl_if->init($url . 'files/diversity.json');
    $this->curl_if->setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $spec_json = $this->curl_if->exec($ch);

    if (!$spec_json) {
      throw new NotFoundException(
        "Didn't find a component at '$url': " . $this->curl_if->error($ch)
      );
    }
    $spec = json_decode($spec_json);
    if (empty($spec)) throw new NotFoundException('Malformed json at ' . $url);

    // Rewrite URL from actual version.
    if ($spec->version !== $version) {
      $url = $this->api_url . 'components/' . $component . '/' . $spec->version . '/';
    }

    return $this->instances[$component] = new Component(
      $this,
      array(
        'spec'     => $spec,
        'location' => $url,
        'base_url' => $url . 'files/',
      )
    );
  }

  public function getAsset(Component $component, $asset) {
    $ch = $this->curl_if->init($component->base_url . $asset);
    $this->curl_if->setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return $this->curl_if->exec($ch);
  }
}
