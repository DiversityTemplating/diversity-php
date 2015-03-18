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

}
