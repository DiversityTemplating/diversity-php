<?php

use Diversity\Component;
use Diversity\Factory;
use Diversity\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase {
  static private $factory;

  static public function setUpBeforeClass() {
    self::$factory = new Factory(
      array(
        'archive'       => FIXTURES . 'component_archive_3' . DS,
        'archive_url'   => 'http://foo.bar/',
      )
    );
  }

  public function testRenderScriptTags() {
    $test1 = self::$factory->get('test1');
    $collection = new Collection;

    $collection->add($test1);

    $scripts_html = $collection->renderScriptTags();
    $scripts = new DOMDocument;
    $scripts->loadXML($scripts_html);

    $this->assertTag(
      array(
        'tag' => 'script',
        'attributes' => array(
          'src' => 'http://foo.bar/test1/1.0.0/test.js'
        )
      ),
      $scripts, $scripts_html
    );
  }

  public function testRenderExternalScriptTags() {
    $test2 = self::$factory->get('test2');
    $collection = new Collection;

    $collection->add($test2);

    $scripts_html = $collection->renderScriptTags();
    $scripts = new DOMDocument;
    $scripts->loadXML($scripts_html);

    $this->assertTag(
      array(
        'tag' => 'script',
        'attributes' => array(
          'src' => 'http://external.site/my.js'
        )
      ),
      $scripts, $scripts_html
    );
  }

  public function testRenderStyleTags() {
    $test1 = self::$factory->get('test1');
    $collection = new Collection;

    $collection->add($test1);

    $styles_html = $collection->renderStyleTags();
    $styles = new DOMDocument;
    $styles->loadXML($styles_html);

    $this->assertTag(
      array(
        'tag' => 'link',
        'attributes' => array(
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => 'http://foo.bar/test1/1.0.0/test.css'
        )
      ),
      $styles, $styles_html
    );
  }
}
