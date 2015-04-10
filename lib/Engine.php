<?php

namespace Diversity;

use Diversity\Factory;
use Diversity\Collection;

class Engine {
  private $factory;

  public function __construct(Factory $factory) {
    $this->factory = $factory;
  }

  public function render($settings, array $params) {
    list($components, $html) = $this->subRender($settings, $params, array());
    return $html;
  }

  private function subRender($settings, $params, $path) {
    if (!isset($settings->component)) {
      throw new \LogicException("Can not render from settings without component.");
    }

    // 1. Get component to render.
    $component_name    = $settings->component;
    $component_version = isset($settings->version) ? $settings->version : '*';
    $component = $this->factory->get($component_name, $component_version);
    $components = array($component);

    // 2. Expand $settings - if there is any parts with format "diversity"
    $schema = $component->getSettingsSchema();
    if ($schema && isset($settings->settings)) {
      list($sub_components, $expanded_settings)
        = $this->expandSettings($schema, $settings->settings, $params, $path, $component);
      $components = array_unique(array_merge($components, $sub_components));
    }

    // 3. Render mustache
    $html = $this->renderHtml($component, $components, $expanded_settings, $params, $path);

    return array($components, $html);
  }

  /**
   * @param array     $path       Only used for warning messages.
   * @param Component $component  The component being expanded - for warning messages.
   */
  private function expandSettings($schema, $settings, $params, $path, $component) {
    $components = array();

    if (is_object($settings)) {
      foreach ($settings as $key => $sub_settings) {
        $sub_path = $path;
        $sub_path[] = $key;

        // Get sub-schema.
        if (isset($schema->properties->$key)) $sub_schema = $schema->properties->$key;
        elseif (isset($schema->additionalProperties)) $sub_schema = $schema->additionalProperties;
        else {
          //trigger_error("Couldn't add setting '$key' to '$component' at /"
          //              . implode('/', $path), E_USER_WARNING);
          next;
        }

        // If the property is in format diversity, we need to sub-render it.
        if (isset($sub_schema->format) && $sub_schema->format === 'diversity') {
          list($sub_components, $settings->$key->componentHTML)
            = $this->subRender($sub_settings, $params, $sub_path);
          unset($settings->$key->settings); // Will this help?
        }
        else {
          list($sub_components, $settings->$key)
            = $this->expandSettings($sub_schema, $sub_settings, $params, $sub_path, $component);
        }
        // Merge sub-components from above into components array.
        $components = array_unique(array_merge($components, $sub_components));
      }
    }
    elseif (is_array($settings)) {
      $sub_schema = $schema->items;

      foreach ($settings as $index => $sub_settings) {
        $sub_path = $path;
        $sub_path[] = $index;

        /// @todo Refactor - this is (almost) the same as in object case above!
        if (isset($sub_schema->format) && $sub_schema->format === 'diversity') {
          list($sub_components, $settings[$index]->componentHTML)
            = $this->subRender($sub_settings, $params, $sub_path);
          unset($settings[$index]->settings); // Will this help?
        }
        else {
          list($sub_components, $settings[$index])
            = $this->expandSettings($sub_schema, $sub_settings, $params, $sub_path, $component);
        }
        // Merge sub-components from above into components array.
        $components = array_unique(array_merge($components, $sub_components));
      }
    }

    return array($components, $settings);
  }

  private function renderHtml($main_component, $components, $settings, $params, $path) {
    $params['settings'     ] = $settings;
    $params['components'   ] = $components;

    return $main_component->render($params);
  }
}
