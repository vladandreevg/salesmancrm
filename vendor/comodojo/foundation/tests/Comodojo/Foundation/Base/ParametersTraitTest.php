<?php namespace Comodojo\Dispatcher\Tests\Components;

use \Comodojo\Foundation\Base\ParametersTrait;

class ParametersTraitTest extends \PHPUnit_Framework_TestCase {

    use ParametersTrait;

    protected $param = 'text';

    protected $value = "lorem";

    protected function setUp() {

        $this->set($this->param, $this->value);

    }

    public function testParameters() {

        $this->assertEquals($this->value, $this->get($this->param));

        $params = $this->get();

        $this->assertInternalType('array', $params);

        $this->assertEquals(1, count($params));

        $this->assertTrue($this->delete($this->param));

        $params = $this->get();

        $this->assertEquals(0, count($params));

        $this->set($this->param, $this->value);

        $this->assertTrue($this->delete());

        $params = $this->get();

        $this->assertEquals(0, count($params));

    }

}
