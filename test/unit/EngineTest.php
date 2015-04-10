<?php

use Diversity\Engine;
use Diversity\Factory\Local;

class EngineTest extends PHPUnit_Framework_TestCase {
  static private $factory, $engine;

  static public function setUpBeforeClass() {
    self::$factory = new Local(
      array(
        'archive'       => FIXTURES . 'component_archive_1' . DS,
        'archive_url'   => 'http://foo.bar/'
      )
    );

    self::$engine = new Engine(self::$factory);
  }

  public function testInstance() {
    $engine = new Engine(self::$factory);
    $this->assertInstanceOf('Diversity\Engine', $engine);
  }

  public function testRenderSingleComponent() {
    $settings = new StdClass;
    $settings->component = 'test_5';

    $rendered = self::$engine->render(
      $settings,
      array('prerequisites' => array('some_data' => array('title' => 'Some Data')))
    );

    $this->assertEquals(
      'Here we can display Some Data.  JSON: {"title":"Some Data"}.', $rendered);
  }

  public function testRenderSubComponents() {
    $params = array(
      'prerequisites' => array(
        'some_data' => array('title' => 'Other data'),
        'localized' => array('sv' => 'svensk sträng', 'en' => 'english string')
      ),
      'language' => 'sv'
    );

    $settings = json_decode(file_get_contents(FIXTURES . 'engine_settings_1.json'));
    $expected_output = file_get_contents(FIXTURES . 'engine_rendered_1_sv.html');

    $rendered = self::$engine->render($settings, $params);

    $this->assertEquals($expected_output, $rendered);
  }

  /**
   * @expectedException LogicException
   */
  public function testNoComponent() {
    $settings = new StdClass;

    self::$engine->render($settings, array());
  }
}
