<?php namespace Comodojo\Foundation\Tests\Timing;

use \Comodojo\Foundation\Timing\TimingTrait;

class TimingTest extends \PHPUnit_Framework_TestCase {

    use TimingTrait;

    public function testTiming() {

        $time = microtime(true);

        $this->setTiming($time);

        $this->assertEquals($time, $this->getTiming());

    }

}
