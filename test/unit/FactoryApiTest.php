<?php

use Diversity\Component;
use Diversity\Factory\Api;
use SAI\Mock\Curl;

class FactoryApiTest extends PHPUnit_Framework_TestCase {
  public function testGetSpecificVersion() {
    $curl_if = new Curl();

    $curl_if->setResponse(
      json_encode(array('name' => 'test', 'version' => '1.2.3')),
      array(CURLOPT_URL => 'https://api.diversity.io/components/test/1.2.3/')
    );

    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '1.2.3');

    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('test', $component->name);
    $this->assertEquals('1.2.3', $component->version);
  }

  public function testGetCaretMinor() {
    $curl_if = new Curl();
    $curl_if->setResponse(
      json_encode(array('name' => 'test', 'version' => '0.2.1')),
      array(CURLOPT_URL => 'https://api.diversity.io/components/test/0.2/')
    );

    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '^0.2.0');

    $this->assertEquals('0.2.1', $component->version);
  }

  public function testGetCaretMajor() {
    $curl_if = new Curl();
    $curl_if->setResponse(
      json_encode(array('name' => 'test', 'version' => '1.2.3')),
      array(CURLOPT_URL => 'https://api.diversity.io/components/test/1/')
    );

    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '^1.1.1');

    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('test', $component->name);
    $this->assertEquals('1.2.3', $component->version);
  }

  public function testGetTemplate() {
    $curl_if = new Curl();
    $curl_if->setResponse(
      json_encode(array('name' => 'test', 'version' => '1.2.3', 'template' => 'foo.html')),
      array(CURLOPT_URL => 'https://api.diversity.io/components/test/1.2.3/')
    );
    $curl_if->setResponse(
      'bar',
      array(CURLOPT_URL => 'https://api.diversity.io/components/test/1.2.3/files/foo.html')
    );

    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '1.2.3');

    $this->assertEquals('bar', $component->getTemplate());
  }

  /**
   * @expectedException Diversity\NotFoundException
   * @expectedExceptionMessage Didn't find a component at
   */
  public function testGetNonExistent() {
    $curl_if = new Curl();
    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '1.2.3');

    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('test', $component->name);
    $this->assertEquals('1.2.3', $component->version);
  }

  public function testGetStyles() {
    $curl_if = new Curl();

    $curl_if->setResponse(
      file_get_contents(FIXTURES . 'component_archive_1/test_3/0.0.1/diversity.json'),
      array(
        CURLOPT_URL => 'https://api.diversity.io/components/test_3/*/'
      )
    );

    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test_3');

    $styles = $component->getStyles();

    $this->assertCount(1, $styles);
    $this->assertEquals(
      'https://api.diversity.io/components/test_3/0.0.1/files/main.css', $styles[0]
    );
  }

}
