<?php

use Diversity\Component;
use Diversity\Factory;

class FactoryTest extends PHPUnit_Framework_TestCase {
  public function testInstantiate() {
    $factory = new Factory(array('archive' => FIXTURES . 'component_archive_1' . DS));
    $this->assertInstanceOf('Diversity\Factory', $factory);
  }

  public function testComponentSpecParsing() {
    $factory = new Factory(array('archive' => FIXTURES . 'component_archive_1' . DS));

    $component_data = $factory->parseName("test_1:>=1.0");
    $this->assertEquals("test_1", $component_data['name']);
    $this->assertEquals(">=1.0", $component_data['version_spec']);

    $component_data = $factory->parseName("test_3");
    $this->assertEquals("test_3", $component_data['name']);
    $this->assertArrayNotHasKey('version_spec', $component_data);

    $component_data = $factory->parseName("http://some.url/");
    $this->assertFalse($component_data);
  }

  public function testSpec() {
    $factory = new Factory(array('archive' => FIXTURES . 'component_archive_1' . DS));

    $component_data = $factory->parseName("test_1:0.1.0");
    $this->assertEquals('0.1.0', $component_data['spec']->version);
  }

  public function testGetArchiveUrl() {
    $factory = new Factory(
      array(
        'archive_url' => 'http://foo.bar/',
      )
    );

    $this->assertEquals('http://foo.bar/', $factory->getArchiveUrl());
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Can't get URL without archive_url in factory.
   */
  public function testGetArchiveUrlException() {
    $factory = new Factory(array());
    $factory->getArchiveUrl();
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Cannot get by type without archive configured.
   */
  public function testGetAllByTypeUnconfigured() {
    $factory = new Factory();
    $components = $factory->getAllByType('object');
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Directory 'wontexist/' does not exist.
   */
  public function testGetAllByTypeBadlyCconfigured() {
    $factory = new Factory(array('archive' => 'wontexist/'));
    $components = $factory->getAllByType('object');
  }

  public function testGetAllByTypeCached() {
    $factory = new Factory(array('archive' => FIXTURES . 'component_archive_1' . DS));
    $components = $factory->getAllByType('object');
    $components = $factory->getAllByType('object');

    /// @todo Try mocking filesystem to see that the second call just uses cache.
    /// We see it currently by looking at the code coverade :P
  }

  public function testInstallComponent() {
    $archive = FIXTURES . 'component_archive_new' . DS;
    $factory = new Factory(array('archive' => $archive));

    $name = $factory->installComponent(FIXTURES . 'source_1' . DS);

    $installed = $archive . 'test1' . DS;
    $this->assertFileExists($installed . '1.2.3' . DS . 'diversity.json');
    $this->assertEquals('test1:1.2.3', $name);

    `rm -fr $archive`;
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Cannot install without "archive" configured.
   */
  public function testInstallComponentUnconfigured() {
    $factory = new Factory();
    $factory->installComponent(FIXTURES . 'source_1' . DS);
  }

  public function testInstallComponentsFromGit() {
    $archive = FIXTURES . 'component_archive_new_2' . DS;
    $factory = new Factory(
      array(
        'archive'       => $archive,
      )
    );

    /// @todo Setup a fixture git repo!
    $factory->installComponentsFromGit(
      FIXTURES . 'component_archive.git', 'components/', 'mybranch'
    );

    $this->assertFileExists($archive . 'other_test_name' . DS . '1.0.0');

    `rm -fr $archive`;
  }

}
