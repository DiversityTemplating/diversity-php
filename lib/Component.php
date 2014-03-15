<?php

namespace Diversity;

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

/**
 * Representing one actual component (in one specific version).
 *
 * For something to be rendered, you will need TemplateComponent.
 */
class Component {

  public function __construct($factory, $component_data) {
    $this->factory  = $factory;
    $this->spec     = $component_data['spec'];
    $this->name     = $this->spec->name;
    $this->version  = $this->spec->version;
    $this->location = $component_data['location'];
    $this->type     = isset($this->spec->type) ? $this->spec->type : 'backend';
  }

  public function getDependencies() {
    $dependencies = array();

    if (!isset($this->spec->dependencies)) return array();

    foreach ($this->spec->dependencies as $name => $spec) {
      /// @todo Accept any URI
      //if (strpos($spec, 'http') === 0) {
      //}
      //else
      {
        // Assume $spec is a version.
        $component = $this->factory->get("$name:$spec");
        $dependencies[$name] = $component;

        // Add the dependent components dependencies.
        $dependencies = array_merge($dependencies, $component->getDependencies());
      }
    }

    return $dependencies;
  }

  /**
   * @return string|boolean The base-url for publically accessible assets, or false.
   *
   * @todo Add false for proprietary components.
   */
  public function getAssetUrl() {
    if (strpos($this->location, 'http') === 0) return $this->location;

    return $this->factory->getArchiveUrl()
      . $this->spec->name . '/' . $this->spec->version . '/';
  }

  public function getScripts() {
    $asset_url = $this->getAssetUrl();
    $scripts   = array();

    if (!isset($this->spec->script)) return $scripts;

    foreach ((array)$this->spec->script as $script) {
      if (strpos($script, 'http') === 0) {
        $scripts[] = $script;
        continue;
      }

      $scripts[] = $asset_url . $script;
    }

    return $scripts;
  }

  public function getOptionsSchema() {
    if (is_string($this->spec->options)) {
      return json_decode(file_get_contents($this->location . $this->spec->options));
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
    $asset_url  = $this->getAssetUrl();
    $style_urls = array();

    if (isset($this->spec->style)) {
      $style_urls[] = $asset_url . $this->spec->style;
    }

    /// @todo Add theme.  ...which one?

    return $style_urls;
  }

  public function getTemplate() {
    if (!isset($this->spec->template)) return false;

    if (strpos($this->spec->template, 'http') === 0) {
      return curl_get_file($this->spec->template);
    }

    return file_get_contents($this->location . $this->spec->template);
  }

  public function render($params = array()) {
    if (!($template_html = $this->getTemplate())) return '';

    /// @todo Strip out all defaults from option schema, merge with chosen options.
    $mustache      = new \Mustache_Engine;
    $options       = isset($params['options'      ]) ? $params['options']       : array();
    $prerequisites = isset($params['prerequisites']) ? $params['prerequisites'] : new \stdClass;
    $theme_index   = isset($params['theme'        ]) ? intval($params['theme']) : 0;
    $language      = isset($params['language'     ]) ? $params['language']      : 'en';

    $template_data = new \stdClass;
    $template_data->language     = $language;
    $template_data->options      = $options;
    $template_data->options_json = json_encode($options);
    $template_data->assetUrl     = $this->getAssetUrl();

    $template_data->testlist = array(
      array('name' => array('sv' => 'apa', 'en' => 'foo')),
      array('name' => array('sv' => 'bepa', 'en' => 'bar')),
    );

    /// @todo Handle fields

    // Handle lang to be able to use e.g. {{#lang}}{{name.{{lang}}}}{{/lang}}
    $template_data->lang = function($text, $mustache) use ($language) {
      return $mustache->render(str_replace('{{lang}}', $language, $text));
    };

    /// @todo Handle gettext
    //if (isset($this->spec->i18n->$language->backend)) {
    //  $po_dir = $this->getAssetUrl() .
    //    substr($this->spec->i18n->$language->backend, 0, -strlen($language . '.po'));
    //  $gettext_domain = 'apa';//$this->name . ':' . $this->version;
    //  bindtextdomain($gettext_domain, $this->location . 'locale');
    //  //trigger_error("Set $gettext_domain to " . $this->location . 'locale');
    //
    //  $template_data->gettext = function($text, $mustache) use ($gettext_domain) {
    //    //trigger_error("domain: '$gettext_domain': " . dgettext($gettext_domain, trim($text)));
    //    return $mustache->render(dgettext($gettext_domain, trim($text)));
    //  };
    //}

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
          case 'jsonrpc': {
            $endpoint = $mustache->render($context_spec->endpoint, $template_data);
            $params = $context_spec->params;
            self::recursiveMustache($params, $template_data);

            $data = $this->getJsonrpc($endpoint, $context_spec->method, $params);
            $template_data->context->$key = $data;
            break;
          }
          //case 'rest': {}
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

  static private function recursiveMustache(&$obj, $data) {
    static $mustache;

    if (!isset($mustache)) $mustache = new \Mustache_Engine;

    switch (gettype($obj)) {
      case 'string': $obj = $mustache->render($obj, $data); break;
      case 'object':
      case 'array':  foreach ($obj as $key => &$value) self::recursiveMustache($value, $data); break;
    }
  }

  static private function getJsonrpc($endpoint, $method, $params) {
    $request_json = json_encode(
      array(
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => $method,
        'params'  => $params,
      )
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_json);

    $response_json = curl_exec($ch);
    $response = json_decode($response_json);

    return $response->result;
  }
}
