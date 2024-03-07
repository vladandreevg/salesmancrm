<?php namespace Comodojo\Foundation\Tests\Validation;

use \Comodojo\Foundation\Validation\DataFilter as Filter;

class DataFilterTest extends \PHPUnit_Framework_TestCase {

    public function testFilterInteger() {

        $this->assertEquals(42, Filter::filterInteger(42));
        $this->assertEquals(0, Filter::filterInteger('Marvin'));
        $this->assertEquals(80, Filter::filterInteger(10, 80, 1000, 80));

    }

    public function testFilterPort() {

        $this->assertEquals(80, Filter::filterPort(80));
        $this->assertEquals(80, Filter::filterPort(8000000));
        $this->assertEquals(42, Filter::filterPort(8000000, 42));

    }

    public function testFilterBoolean() {

        $this->assertTrue(Filter::filterBoolean(true));
        $this->assertTrue(Filter::filterBoolean(10, true));
        $this->assertFalse(Filter::filterBoolean(10));

    }

}
