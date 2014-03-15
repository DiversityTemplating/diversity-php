<?php

namespace Diversity;

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

/**
 * A factory for constructing Component instances.
 */
class Factory {
  /// Array of Component instances, keyed by spec (uri/name)
  protected $components = array();
  protected $components_by_type = null;

  private $settings = array(
    'archive'       => null,
    'archive_url'   => null,
    'spec_filename' => 'diversity.json',
  );

  public function __construct($settings = array()) {
    $this->settings = array_merge($this->settings, $settings);
  }

  /**
   * Get a Component instance from a URL or NAME.
   */
  public function get($spec) {
    if (array_key_exists($spec, $this->components)) return $this->components[$spec];

    // if NAME
    if ($component_data = $this->parseName($spec)) {

    }
    /// @todo if URL

    if (!isset($component_data['spec'])) {
      /// @todo More verbose error message?  More specific exception type.
      throw new NotFoundException('Couldn\'t find component: ' . $spec);
    }

    $component = new Component($this, $component_data);
    $this->components[$spec] = $component;


    return $component;
  }

  public function getArchiveUrl() {
    if ($this->settings['archive_url'] === null) {
      throw new ConfigurationException("Can't get URL without archive_url in factory.");
    }
    return $this->settings['archive_url'];
  }

  public function getAllByType($type) {
    if ($this->components_by_type !== null) {
      return array_key_exists($type, $this->components_by_type)
        ? $this->components_by_type[$type] : array();
    }

    if ($this->settings['archive'] === null) {
      throw new ConfigurationException('Cannot get by type without archive configured.');
    }
    if (!is_dir($this->settings['archive'])) {
      throw new ConfigurationException("Directory '{$this->settings['archive']}' does not exist.");
    }

    $component_dirs = scandir($this->settings['archive']);

    $this->components_by_type = array();
    foreach ($component_dirs as $component_dir) {
      if (in_array($component_dir, array('.', '..'))) continue;
      $version_dirs = scandir($this->settings['archive'] . $component_dir . '/');

      foreach ($version_dirs as $version_dir) {
        if (in_array($version_dir, array('.', '..'))) continue;
        try {
          $version = new version($version_dir);
          $component = $this->get("$component_dir:$version_dir");
          if (!array_key_exists($component->type, $this->components_by_type)) {
            $this->components_by_type[$component->type] = array();
          }
          $this->components_by_type[$component->type][$component->name] = $component;
        }
        catch (\RuntimeException $e) { continue; } // Not a version dir, perhaps "..".
      }
    }

    return array_key_exists($type, $this->components_by_type)
      ? $this->components_by_type[$type] : array();
  }

  /**
   *
   * @todo Safe this up for missing dir, missing diversity.json, bad json
   */
  public function parseName($name) {
    if (!preg_match('/^(?P<name>[a-zA-Z0-9-_]+)(:(?P<version_spec>[:0-9\.\^\~\=\>]*))?$/',
                    $name, $component_data)) {
      return false;
    }

    $component_data['location'] = $this->settings['archive'] . $component_data['name'] . '/';
    $version_dirs = scandir($component_data['location']);

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
        $component_data['location'] .= $version_dir . '/';
        break;
      }
      catch (\RuntimeException $e) { continue; } // Obviously not a match…
    }

    // Get diversity.json.
    $spec_file = $component_data['location'] . $this->settings['spec_filename'];
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

  /**
   * Install components from a git multi-archive into a local directory.
   *
   * This will change a lot when we're on e.g. gitorious, moving each component to separate repo.
   */
  public function installComponentsFromGit($repo, $subdir = '', $branch = 'master') {
    // Setup git cloning dir
    $tempdir = tempnam(sys_get_temp_dir(), '');
    `unlink $tempdir && mkdir $tempdir`;
    $tempdir .= DIRECTORY_SEPARATOR;

    // Clone repo
    `cd $tempdir && git clone $repo -q -b $branch .`;

    // Iterate over all components and copy them into deployed dirs.
    $component_dirs = scandir($tempdir . $subdir);
    foreach ($component_dirs as $component_dir) {
      if (in_array($component_dir, array('.', '..'))) continue;
      $this->installComponent($tempdir . $subdir . $component_dir . DIRECTORY_SEPARATOR);
    }
  }

  public function installComponent($source_dir) {
    if ($this->settings['archive'] === null) {
      throw new ConfigurationException('Cannot install without "archive" configured.');
    }

    $spec_file = file_get_contents($source_dir . $this->settings['spec_filename']);
    $spec = json_decode($spec_file);

    $name = $spec->name;
    $component_deployed_dir = $this->settings['archive'] . $name . DIRECTORY_SEPARATOR;

    if (!is_dir($component_deployed_dir)) mkdir($component_deployed_dir, 0777, true);

    $version = $spec->version;
    $version_dir = $component_deployed_dir . $version;

    if (!file_exists($version_dir)) {
      mkdir($version_dir, 0777, true);
      `cp -r {$source_dir}* $version_dir`;
    }

    return "$name:$version";
  }
}
