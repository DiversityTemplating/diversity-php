<?php

use Diversity\Component;
use Diversity\Factory;

class ComponentTest extends PHPUnit_Framework_TestCase {
  static private $factory;

  static public function setUpBeforeClass() {
    self::$factory = new Factory(
      array(
        'archive'       => FIXTURES . 'component_archive_1' . DS,
        'archive_url'   => 'http://foo.bar/'
      )
    );
  }

  public function testGetByName() {
    $component = self::$factory->get('test_1:0.1.0');
    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('0.1.0', $component->spec->version);
    $this->assertEquals('backend', $component->type);
  }

  /**
   * @expectedException Diversity\NotFoundException
   */
  public function testGetByBadName() {
    $component = self::$factory->get('test_1:1.2.3');
  }

  /**
   * @expectedException Diversity\NotFoundException
   */
  public function testGetComponentWithEmptyJson() {
    $component = self::$factory->get('test_2');
  }

  /**
   * @expectedException Diversity\NotFoundException
   */
  public function testGetComponentWithBaddirAndNoJson() {
    $component = self::$factory->get('test_4:1.0.0');
  }

  public function testGetByNameVersionGreaterThan() {
    $component = self::$factory->get('test_1:>=0.1.0');
    $this->assertEquals('0.1.0', $component->spec->version);
  }

  public function testDependencies() {
    $component = self::$factory->get('test_3');
    $dependencies = $component->getDependencies();

    $this->assertEquals('test_1', $dependencies['test_1']->name);
  }

  public function testGetAllByType() {
    $components = self::$factory->getAllByType('object');

    $this->assertEquals('test_3', $components['test_3']->name);
    $this->assertCount(1, $components);
  }

  public function testGetStyles() {
    $component = self::$factory->get('test_3');

    $styles = $component->getStyles();

    $this->assertCount(1, $styles);
    $this->assertEquals('http://foo.bar/test_3/0.0.1/main.css', $styles[0]);
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Can't get URL without archive_url in factory.
   */
  public function testGetStylesException() {
    $factory = new Factory(array('archive' => FIXTURES . 'component_archive_1' . DS));
    $component = $factory->get('test_3');

    $styles = $component->getStyles();
    $this->fail("Got style urls: " . json_encode($styles));
  }

  /**
   * @expectedException PHPUnit_Framework_Error_Warning
   * @expectedExceptionMessage Component needs prerequisite: value
   */
  public function testRenderWarningOnPrerequisite() {
    $factory = new Factory(
      array('archive' => FIXTURES . 'component_archive_3' . DS, 'archive_url' => 'dummy')
    );
    $component = $factory->get('test1');
    $component->render();
  }

  public function testRenderWithPrerequisite() {
    $component = self::$factory->get('test_5');

    $rendered = $component->render(
      array(
        'prerequisites' => array('some_data' => array('title' => 'Some Data'))
      )
    );

    $this->assertEquals(
      'Here we can display Some Data.  JSON: {"title":"Some Data"}.', $rendered);
  }

  public function testGetOptionsSchemaFromInline() {
    $component = self::$factory->get('test_1:0.1.0');
    $schema = $component->getOptionsSchema();

    $this->assertEquals("Simple option", $schema->title);
    $this->assertEquals("string", $schema->type);
  }

  public function testGetOptionsSchemaFromFile() {
    $component = self::$factory->get('test_3');
    $schema = $component->getOptionsSchema();

    $this->assertEquals("object", $schema->type);
    $this->assertEquals("Your name", $schema->properties->name->title);
  }

  public function testRenderLanguagePart() {
    $component = self::$factory->get('test_6');

    $prerequisites = array('localized' => array('sv' => 'svensk sträng', 'en' => 'english string'));

    $rendered_en = $component->render(
      array(
        'language'      => 'en',
        'prerequisites' => $prerequisites,
      )
    );

    $this->assertEquals('String: "english string"', $rendered_en);

    $rendered_sv = $component->render(
      array(
        'language'      => 'sv',
        'prerequisites' => $prerequisites,
      )
    );

    $this->assertEquals('String: "svensk sträng"', $rendered_sv);
  }

  /// @todo Test features:

  // When there are several matching versions, you should get the highest.

}
