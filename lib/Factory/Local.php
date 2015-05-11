<?php

namespace Diversity\Factory;

use Diversity\Component;
use Diversity\NotFoundException;

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

class Local extends \Diversity\Factory {
  /// Array of Component instances, keyed by spec (uri/name)
  protected $components = array();

  private $settings = array(
    'archive'     => null,
    'archive_url' => null,
  );

  public function __construct($settings = array()) {
    $this->settings = array_merge($this->settings, $settings);
  }

  public function get($component, $version = null) {
    // Supporting deprecated format of component:version
    $spec = $component . ($version ? ':' . $version : '');

    if (array_key_exists($spec, $this->components)) return $this->components[$spec];

    $component_data = $this->getComponentData($spec);

    if (!isset($component_data['spec'])) {
      /// @todo More verbose error message?  More specific exception type.
      throw new NotFoundException(
        'Couldn\'t find component: ' . $spec . " in {$this->settings['archive']}"
      );
    }

    if (isset($this->settings['archive_url'])) {
      $component_data['base_url'] = $this->settings['archive_url'] . $component_data['subpath'];
    }

    $component = new Component($this, $component_data);
    $this->components[$spec] = $component;

    return $component;
  }

  /**
   * Get the asset content.
   */
  public function getAsset(Component $component, $asset) {
    return file_get_contents($this->joinPaths($component->base_dir, $asset));
  }

  /**
   * Combine paths handling slashes.
   *
   * http://stackoverflow.com/questions/1091107/how-to-join-filesystem-path-strings-in-php/15575293#15575293
   */
  private function joinPaths() {
    $paths = array();

    foreach (func_get_args() as $arg) {
      if ($arg !== '') { $paths[] = $arg; }
    }

    return preg_replace('#/+#','/',join('/', $paths));
  }

  /**
   *
   * @todo Safe this up for missing dir, missing diversity.json, bad json
   */
  private function getComponentData($name) {
    if (!preg_match('/^(?P<name>[a-zA-Z0-9-_]+)(:(?P<version_spec>[:0-9\.\^\~\=\>]*))?$/',
                    $name, $component_data)) {
      return false;
    }

    $component_data['subpath'] = $component_data['name'] . '/';
    $version_dirs = scandir($this->settings['archive'] . $component_data['subpath']);

    $version_spec = new expression(
      isset($component_data['version_spec']) ? $component_data['version_spec'] : ''
    );

    // Sort the array reverse to try highest version first.
    arsort($version_dirs);
    foreach ($version_dirs as $version_dir) {
      if (in_array($version_dir, array('.', '..'))) continue;
      try {
        if (!$version_spec->satisfiedBy(new version($version_dir))) continue;

        $version = $version_dir;
        $component_data['subpath'] .= $version_dir . '/';
        break;
      }
      catch (\RuntimeException $e) { continue; } // Obviously not a match…
    }

    // Get diversity.json.
    $component_data['base_dir']
      = $this->joinPaths($this->settings['archive'], $component_data['subpath']);
    $spec_file = $this->joinPaths($component_data['base_dir'], 'diversity.json');

    if (file_exists($spec_file)) {
      $spec_json = file_get_contents($spec_file);
      if (empty($spec_json)) {
        throw new NotFoundException('Empty or missing diversity.json at: ' . $spec_file);
      }

      $spec = json_decode($spec_json);

      if (empty($spec)) throw new NotFoundException('Malformed json in ' . $spec_file);
      $component_data['spec'] = $spec;
    }

    return $component_data;
  }

}
