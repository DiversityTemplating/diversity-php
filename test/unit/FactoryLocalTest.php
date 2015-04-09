<?php

use Diversity\Component;
use Diversity\Factory\Local;

class FactoryLocalTest extends PHPUnit_Framework_TestCase {
  static private $factory;

  static public function setUpBeforeClass() {
    self::$factory = new Local(
      array(
        'archive'       => FIXTURES . 'component_archive_1' . DS,
        'archive_url'   => 'http://foo.bar/'
      )
    );
  }

  public function testInstantiate() {
    $factory = new Local(array('archive' => FIXTURES . 'component_archive_1' . DS));
    $this->assertInstanceOf('Diversity\Factory', $factory);
    $this->assertInstanceOf('Diversity\Factory\Local', $factory);
  }

  /**
   * @expectedException Diversity\NotFoundException
   */
  public function testGetByBadName() {
    $component = self::$factory->get('test_1-1.2.3');
  }

  public function testGetByName() {
    $component = self::$factory->get('test_1', '0.1.0');
    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('0.1.0', $component->spec->version);
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
    $component = self::$factory->get('test_4', '1.0.0');
  }

  public function testGetByNameVersionGreaterThan() {
    $component = self::$factory->get('test_1', '>=0.1.0');
    $this->assertEquals('0.1.0', $component->spec->version);
  }
}
