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

  public function getOptionsSchema() {
    if (is_string($this->spec->options)) {
      return json_decode(file_get_contents($this->base_dir . $this->spec->options));
    }
    else {
      return $this->spec->options;
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

    /// @todo Strip out all defaults from option schema, merge with chosen options.
    $mustache      = new \Mustache_Engine;
    $options       = isset($params['options'      ]) ? $params['options']       : array();
    $prerequisites = isset($params['prerequisites']) ? $params['prerequisites'] : array();
    $theme_index   = isset($params['theme'        ]) ? intval($params['theme']) : 0;
    $language      = isset($params['language'     ]) ? $params['language']      : 'en';

    $template_data = new \stdClass;
    $template_data->language     = $language;
    $template_data->options      = $options;
    $template_data->options_json = json_encode($options);

    if (!empty($this->base_url)) $template_data->baseUrl = $this->base_url;

    $template_data->testlist = array(
      array('name' => array('sv' => 'apa', 'en' => 'foo')),
      array('name' => array('sv' => 'bepa', 'en' => 'bar')),
    );

    // Handle lang to be able to use e.g. {{#lang}}{{name.{{lang}}}}{{/lang}}
    $template_data->lang = function($text, $mustache) use ($language) {
      return $mustache->render(str_replace('{{lang}}', $language, $text));
    };

    /// @todo Handle dynamic context.

    if (isset($this->spec->context)) {
      $template_data->context = new \stdClass;

      foreach ($this->spec->context as $key => $context_spec) {
        switch (isset($context_spec->type) ? $context_spec->type : 'prerequisites') {
          case 'prerequisite': {
            if (!array_key_exists($key, $prerequisites)) {
              trigger_error("Component needs prerequisite: $key", E_USER_WARNING);
            }
            $data = isset($prerequisites[$key]) ? $prerequisites[$key] : null;

            $template_data->context->$key = $data;
            break;
          }
          default: trigger_error("Unhandled context type: " . $context_spec->type);
        }

        if (isset($context_spec->json) && $context_spec->json === true) {
          $jsonkey = $key . '_json';
          $template_data->context->$jsonkey = json_encode($template_data->context->$key);
        }
      }
    }

    return $mustache->render($template_html, $template_data);
  }
}
