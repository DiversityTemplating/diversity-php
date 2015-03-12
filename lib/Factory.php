<?php

namespace Diversity;

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
}
