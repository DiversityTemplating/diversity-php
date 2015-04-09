<?php

namespace Diversity;

use Diversity\Component;

/**
 * A factory for constructing Component instances.
 *
 * @todo This will be abstract, use the subclasses instead.
 */
abstract class Factory {
  abstract public function __construct($settings = array());

  /**
   * Get a Component
   *
   * @param string $component  Component name
   * @param string $version    Component version
   *
   * @return Diversity\Component
   */
  abstract public function get($component, $version = null);

  /**
   * Get an asset - absolute or relative URL/path.
   */
  abstract public function getAsset(Component $component, $asset);
}
