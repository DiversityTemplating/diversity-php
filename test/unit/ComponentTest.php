<?php

use Diversity\Component;
use Diversity\Factory\Local;

class ComponentTest extends PHPUnit_Framework_TestCase {
  static private $factory;

  static public function setUpBeforeClass() {
    self::$factory = new Local(
      array(
        'archive'       => FIXTURES . 'component_archive_1' . DS,
        'archive_url'   => 'http://foo.bar/'
      )
    );
  }

  public function testDependencies() {
    $component = self::$factory->get('test_3');
    $dependencies = $component->getDependencies();

    $this->assertEquals('test_1', $dependencies['test_1']->name);
  }

  public function testGetStyles() {
    $component = self::$factory->get('test_3');

    $styles = $component->getStyles();

    $this->assertCount(1, $styles);
    $this->assertEquals('http://foo.bar/test_3/0.0.1/main.css', $styles[0]);
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Can't get URL without base_url.
   */
  public function testGetStylesException() {
    $factory = new Local(array('archive' => FIXTURES . 'component_archive_1' . DS));
    $component = $factory->get('test_3');

    $styles = $component->getStyles();
    $this->fail("Got style urls: " . json_encode($styles));
  }

  /**
   * @expectedException Diversity\ConfigurationException
   * @expectedExceptionMessage Can't get URL without base_url.
   */
  public function testGetScriptsException() {
    $factory = new Local(array('archive' => FIXTURES . 'component_archive_1' . DS));
    $component = $factory->get('test_3');

    $styles = $component->getScripts();
    $this->fail("Got script urls: " . json_encode($styles));
  }

  /**
   * @expectedException PHPUnit_Framework_Error_Warning
   * @expectedExceptionMessage Component test1:1.0.0 needs prerequisite: value
   */
  public function testRenderWarningOnPrerequisite() {
    $factory = new Local(
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

  /**
   * @expectedException PHPUnit_Framework_Error_Warning
   * @expectedExceptionMessage Unhandled context type in test_7:0.1.2: unknown
   */
  public function testRenderWithBadContextType() {
    $component = self::$factory->get('test_7');
    $rendered = $component->render();
  }

  public function testGetSettingsSchemaFromInline() {
    $component = self::$factory->get('test_1', '0.1.0');
    $schema = $component->getSettingsSchema();

    $this->assertEquals("Simple option", $schema->title);
    $this->assertEquals("string", $schema->type);
  }

  public function testGetSettingsSchemaFromFile() {
    $component = self::$factory->get('test_3');
    $schema = $component->getSettingsSchema();

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

    $this->assertEquals("String: \"english string\"\n", $rendered_en);

    $rendered_sv = $component->render(
      array(
        'language'      => 'sv',
        'prerequisites' => $prerequisites,
      )
    );

    $this->assertEquals("String: \"svensk sträng\"\n", $rendered_sv);
  }

  /**
   * @expectedException LogicException
   * @expectedExceptionMessage base_url must end with a slash
   */
  public function testBadBaseUrl() {
    $spec = new StdClass;
    $spec->name    = 'dummy';
    $spec->version = '1.2.3';

    $component = new Component(
      self::$factory, array('spec' => $spec, 'base_url' => 'bad_base_url_with_no_trailing_slash')
    );
  }

  /// @todo Test features:

  // When there are several matching versions, you should get the highest.

}
