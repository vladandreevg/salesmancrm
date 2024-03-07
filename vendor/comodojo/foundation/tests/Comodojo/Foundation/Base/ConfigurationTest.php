<?php namespace Comodojo\Foundation\Tests\Base;

use \Comodojo\Foundation\Base\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase {

    protected function setUp() {

        $test_values = [
            "foo" => "boo",
            "a" => [
                "a" => "lorem",
                "b" => "ipsum",
                "c" => [
                    "dolor" => "sit"
                ]
            ],
            "b" => false,
            "c" => 42,
            "d" => (object) array("a" => "lorem", "b" => "ipsum"),
        ];

        $this->config = new Configuration( $test_values );

    }

    protected function tearDown() {

        unset($this->config);

    }

    public function testGetSetDelete() {

        $param = 'test';

        $return = $this->config->set("t", $param);

        $this->assertInstanceOf('\Comodojo\Foundation\Base\Configuration', $return);

        $this->assertEquals($this->config->get("t"), $param);

        $remove = $this->config->delete("t");

        $this->assertTrue($remove);

        $remove = $this->config->get("t");

        $this->assertNull($remove);

    }

    public function testGetAll() {

        $config = $this->config->get();

        $this->assertInternalType('array', $config);

        $this->assertEquals($config["c"], 42);
        $this->assertEquals($config["a"]["a"], 'lorem');

    }

    public function testHas() {

        $this->assertTrue($this->config->has('a'));

        $this->assertTrue($this->config->isDefined('a'));

        $this->assertFalse($this->config->has('z'));

    }

    public function testWholeDelete() {

        $this->assertTrue($result = $this->config->delete());

        $this->assertFalse($this->config->isDefined('a'));

    }

    public function testMerge() {

        $new_props = array("a" => false, "b" => true);

        $this->config->merge($new_props);

        $this->assertFalse($this->config->get('a'));
        $this->assertTrue($this->config->get('b'));

    }

    public function testSelectiveCrud() {

        $this->config->set("a.c.amet", true);

        $this->assertTrue($this->config->get("a.c.amet"));

        $ac_value = $this->config->get("a.c");

        $this->assertInternalType('array', $ac_value);
        $this->assertArrayHasKey('dolor', $ac_value);
        $this->assertArrayHasKey('amet', $ac_value);
        $this->assertEquals('sit', $ac_value['dolor']);

        $this->config->delete("a.c.amet");

        $ac_value = $this->config->get("a.c");

        $this->assertInternalType('array', $ac_value);
        $this->assertArrayHasKey('dolor', $ac_value);
        $this->assertArrayNotHasKey('amet', $ac_value);

        $this->assertFalse($this->config->has("a.c.amet"));

    }

    public function testStaticConstructor() {

        $config = Configuration::create(["this"=>"is","a"=>["config", "statement"]]);

        $this->assertInstanceOf('\\Comodojo\\Foundation\\Base\\Configuration', $config);
        $this->assertEquals("is", $config->get("this"));
        $this->assertInternalType('array', $config->get("a"));

    }

}
