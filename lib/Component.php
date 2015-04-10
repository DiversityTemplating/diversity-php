<?php

namespace Diversity;

use Diversity\Factory;

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;
use LogicException;

/**
 * Representing one actual component (in one specific version).
 */
class Component {

  public function __construct(Factory $factory, $component_data) {

    $this->factory  = $factory;
    $this->spec     = $component_data['spec'];
    $this->name     = $this->spec->name;
    $this->version  = $this->spec->version;

    if (array_key_exists('base_dir', $component_data)) {
      $this->base_dir = $component_data['base_dir'];
    }

    if (array_key_exists('base_url', $component_data)) {
      if (substr($component_data['base_url'], -1) !== '/') {
        // We won't automatically add the slash - Components are produced by a factory, the factory
        // coder should know what she's doing.
        throw new LogicException('base_url must end with a slash.');
      }
      $this->base_url = $component_data['base_url'];
    }
  }

  public function getDependencies() {
    $dependencies = array();

    if (!isset($this->spec->dependencies)) return array();

    foreach ($this->spec->dependencies as $name => $spec) {
      // Assume $spec is a version.
      $component = $this->factory->get($name, $spec);
      $dependencies[$name] = $component;

      // Add the dependent components dependencies.
      $dependencies = array_merge($dependencies, $component->getDependencies());
    }

    return $dependencies;
  }

  public function getScripts() {
    if (!isset($this->base_url)) {
      throw new ConfigurationException("Can't get URL without base_url.");
    }

    $scripts   = array();

    if (!isset($this->spec->script)) return $scripts;

    foreach ((array)$this->spec->script as $script) $scripts[] = $this->makeUrl($script);

    return $scripts;
  }

  public function getSettingsSchema() {
    if (is_string($this->spec->settings)) {
      return json_decode($this->factory->getAsset($this, $this->spec->settings));
    }
    else {
      return $this->spec->settings;
    }
  }

  /**
   * @return array List of style URLs.
   * @exception Diversity\ConfigurationException if run with no archive_url
   */
  public function getStyles() {
    if (!isset($this->base_url)) {
      throw new ConfigurationException("Can't get URL without base_url.");
    }

    $styles = array();

    if (!isset($this->spec->style)) return $styles;

    foreach ((array)$this->spec->style as $style) $styles[] = $this->makeUrl($style);

    return $styles;
  }

  /**
   * @return string View localization json as string.
   */
  public function getViewL10n($language) {
    if (!isset($this->spec->l10n->$language->view)) return false;
    return $this->factory->getAsset($this, $this->spec->l10n->$language->view);
  }

  /**
   * Takes a relative or absolut URL and returns an absolute URL (by adding base_url).
   *
   * @param string $url  A relative or absolut URL.
   *
   * @return string  The absolute URL.
   */
  private function makeUrl($url) {
    if (strpos($url, '//') !== false) return $url; // Absolute URL already.
    return $this->base_url . $url; // base_url always ends with a '/'.
  }


  public function getTemplate() {
    if (!isset($this->spec->template)) return false;

    return $this->factory->getAsset($this, $this->spec->template);
  }

  public function render($params = array()) {
    if (!($template_html = $this->getTemplate())) return '';

    /// @todo Strip out all defaults from settings schema, merge with chosen settings.
    $mustache      = new \Mustache_Engine;
    $settings      = isset($params['settings'     ]) ? $params['settings'     ] : array();
    $prerequisites = isset($params['prerequisites']) ? $params['prerequisites'] : array();
    $language      = isset($params['language'     ]) ? $params['language'     ] : 'en';

    $template_data = new \stdClass;
    $template_data->language     = $language;
    $template_data->settings     = $settings;
    $template_data->settingsJSON = json_encode($settings);

    if (!empty($this->base_url)) $template_data->baseUrl = $this->base_url;

    // Handle lang to be able to use e.g. {{#lang}}{{name.lang}}{{/lang}}
    $template_data->lang = function($text, $mustache) use ($language) {
      // HACK! It seems PHP mustache FORGETS that the delimiter is switched.
      $text = str_replace('[[', '{{', $text);
      $text = str_replace(']]', '}}', $text);
      return $mustache->render(str_replace('lang', $language, $text));
    };

    /// @todo Handle dynamic context.

    if (isset($this->spec->context)) {
      $template_data->context = new \stdClass;

      foreach ($this->spec->context as $key => $context_spec) {
        switch (isset($context_spec->type) ? $context_spec->type : 'prerequisites') {
          case 'prerequisite': {
            if (!array_key_exists($key, $prerequisites)) {
              trigger_error("Component $this needs prerequisite: $key", E_USER_WARNING);
            }
            $data = isset($prerequisites[$key]) ? $prerequisites[$key] : null;

            $template_data->context->$key = $data;
            break;
          }
          case 'rendered': {
            if (!isset($collection)) {
              $collection = new Collection();
              if (isset($params['components'])) {
                foreach ($params['components'] as $component) $collection->add($component);
              }
              else $collection->add($this);
            }
            // The rendering Engine could provide three things:
            switch ($key) {
              case 'angularBootstrap': {
                $template_data->context->angularBootstrap = $collection->renderAngularBootstrap();
                break;
              }
              case 'scripts': {
                $template_data->context->scripts = $collection->getScripts();
                break;
              }
              case 'styles': {
                $template_data->context->styles = $collection->getStyles();
                break;
              }
              case 'l10n': {
                $template_data->context->l10n = $collection->getViewL10ns($language);
                break;
              }
              default: trigger_error("Unknown key for renderer type context in $this: $key",
                                     E_USER_WARNING);
            }
            break;
          }
          default: trigger_error("Unhandled context type in $this: "
                                 . $context_spec->type, E_USER_WARNING);
        }

        if (isset($context_spec->json) && $context_spec->json === true) {
          $jsonkey = $key . '_json';
          $template_data->context->$jsonkey = json_encode($template_data->context->$key);
        }
      }
    }

    $html = $mustache->render($template_html, $template_data);

    return $html;
  }

  public function __toString() {
    return $this->name . ':' . $this->version;
  }
}
