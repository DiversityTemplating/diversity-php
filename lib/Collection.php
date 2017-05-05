<?php

namespace Diversity;

/**
 * Representing the bulk of all Components used in one page or template.
 *
 * On a page using Components, this Collection can be used to get all scripts, angular bootstrap,
 * and whatnot.
 */
class Collection {
  private $components = array();

  public function __construct() {}

  public function add(Component $component) { $this->components[$component->name] = $component; }

  /**
   * @return array List of scripts (URLs or filenames).
   */
  public function getScripts() {
    $scripts = array();

    foreach ($this->getAllComponents() as $component) {
      $scripts = array_merge($scripts, $component->getScripts());
    }

    return $scripts;
  }

  public function getStyles() {
    $styles = array();

    foreach ($this->getAllComponents() as $component) {
      $styles = array_merge($styles, $component->getStyles());
    }

    return $styles;
  }

  public function renderScriptTags() {
    $tags = '';
    foreach ($this->getScripts() as $script) {
      /// @todo Fetch and minify proprietary scripts.

      $tags .= '<script src="' . htmlspecialchars($script) . '"></script>' . "\n";
    }
    return $tags;
  }

  public function renderStyleTags() {
    $tags = '';
    foreach ($this->getStyles() as $style) {
      /// @todo Fetch and minify proprietary styles.

      $tags .= '<link rel="stylesheet" type="text/css" '
        . 'href="' . htmlspecialchars($style) . '">' . "\n";
    }
    return $tags;
  }

  /**
   * Return an ordered array of all components. Dependencies up front before their dependees.
   */
  public function getAllComponents() {
    $all_components = array();

    foreach ($this->components as $component) {
      $all_components[$component->name] = $component;
      $all_components = array_merge($all_components, $component->getDependencies());
    }

    // First we reverse the list to get the correct dependency oredering, i.e. deps upfront
    $all_components = array_reverse($all_components);

    // Then we need to remove duplicate components, while keeping order.
    $lookup = array();
    $filtered = array();

    foreach ($all_components as $component) {
      if (!isset($lookup[$component->name])) {
        $lookup[$component->name] = true;
        $filtered[] = $component;
      }
    }

    return $filtered;
  }

  public function needsAngular() {
    foreach ($this->getAllComponents() as $component) {
      if (isset($component->spec->angular)) return true;
    }
    return false;
  }

  /**
   * @return string JavaScript with the angular bootstrap call.
   */
  public function renderAngularBootstrap() {
    return 'angular.bootstrap(document, ' . json_encode($this->getAngularModules()) . ");\n";
  }

  /**
   * @return array Flat array of module names.
   */
  public function getAngularModules() {
    $modules = array();

    foreach ($this->getAllComponents() as $component) {
      if (isset($component->spec->angular)) $modules[] = $component->spec->angular;
    }

    return $modules;
  }
}
