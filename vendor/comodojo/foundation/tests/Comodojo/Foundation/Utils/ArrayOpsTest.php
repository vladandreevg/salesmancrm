<?php namespace Comodojo\Foundation\Tests\Utils;

use \Comodojo\Foundation\Utils\ArrayOps;

class ArrayOpsTest extends \PHPUnit_Framework_TestCase {

    protected $source = [
        "Marvin" => "Sad Robot",
        "Ford" => "Perfect"
    ];

    protected $first_replace = [
        "Marvin" => "A Sad Robot",
        "Arthur" => "Dent",
        "Test" => true
    ];

    protected $second_replace = [
        "Ford" => "I'm Perfect!",
        "answer" => 42
    ];

    protected $empty_replace = [];

    public function testSingleReplaceStrict() {

        $result = ArrayOps::replaceStrict($this->source, $this->first_replace);
        $this->assertCount(2, $result);
        $this->assertEquals($this->first_replace["Marvin"], $result["Marvin"]);
        $this->assertEquals($this->source["Ford"], $result["Ford"]);

    }

    public function testMultipleReplaceStrict() {

        $result = ArrayOps::replaceStrict($this->source, $this->first_replace, $this->second_replace);
        $this->assertCount(2, $result);
        $this->assertEquals($this->first_replace["Marvin"], $result["Marvin"]);
        $this->assertEquals($this->second_replace["Ford"], $result["Ford"]);

    }

    public function testEmptyReplaceSrict() {

        $result = ArrayOps::replaceStrict($this->source, $this->empty_replace);
        $this->assertCount(2, $result);
        $this->assertEquals($this->source["Marvin"], $result["Marvin"]);
        $this->assertEquals($this->source["Ford"], $result["Ford"]);

        $result = ArrayOps::replaceStrict($this->source, $this->empty_replace, $this->empty_replace);
        $this->assertCount(2, $result);
        $this->assertEquals($this->source["Marvin"], $result["Marvin"]);
        $this->assertEquals($this->source["Ford"], $result["Ford"]);

    }

    public function testFilterByKeys() {

        $result = ArrayOps::filterByKeys(["Marvin","Test"], $this->first_replace);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey("Marvin", $result);
        $this->assertEquals("A Sad Robot", $result['Marvin']);
        $this->assertArrayHasKey("Test", $result);
        $this->assertTrue($result['Test']);

    }


}
