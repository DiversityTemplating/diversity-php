<?php

namespace Diversity;

use Diversity\Component;

/**
 * A factory for constructing Component instances.
 *
 * @todo This will be abstract, use the subclasses instead.
 */
class Factory {
  private $factory;

  /**
   * @deprecated Use the subclasses instead
   */
  public function __construct($settings = array()) {
    $this->factory = new Factory\Local($settings);
  }

  /**
   * Get a Component
   *
   * @return Diversity\Component
   */
  public function get($component, $version = null) {
    return $this->factory->get($component, $version);
  }

  /**
   * Get an asset - absolute or relative URL/path.
   *
   * (This need not be backwards compatible, it is called from the component, and the component
   * knows its parent.)
   */
  public function getAsset(Component $component, $asset) {}
}
