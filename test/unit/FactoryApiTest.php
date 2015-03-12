<?php

use Diversity\Component;
use Diversity\Factory\Api;

class FactoryApiTest extends PHPUnit_Framework_TestCase {
  public function testGetSpecificVersion() {
    $curl_if = new SAI_CurlStub();

    $curl_if->setResponse(
      json_encode(
        array(
          'name' => 'test',
          'version' => '1.2.3'
        )
      ),
      array(
        CURLOPT_URL => 'https://api.diversity.io/components/test/1.2.3/'
      )
    );

    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '1.2.3');

    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('test', $component->name);
    $this->assertEquals('1.2.3', $component->version);
  }

  /**
   * @expectedException Diversity\NotFoundException
   * @expectedExceptionMessage Didn't find a component at
   */
  public function testGetNonExistent() {
    $curl_if = new SAI_CurlStub();
    $factory = new Api('https://api.diversity.io/', $curl_if);
    $component = $factory->get('test', '1.2.3');

    $this->assertInstanceOf('Diversity\Component', $component);
    $this->assertEquals('test', $component->name);
    $this->assertEquals('1.2.3', $component->version);
  }

  public function testGetStyles() {
    $curl_if = new SAI_CurlStub();

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
