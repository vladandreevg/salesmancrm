<?php namespace Comodojo\Foundation\Tests\Timing;

use \Comodojo\Foundation\Timing\TimeTrait;

class TimeTraitTest extends \PHPUnit_Framework_TestCase {

    use TimeTrait;

    public function testTime() {

        $time = time();

        $this->setTimestamp($time);

        $this->assertEquals($time, $this->getTimestamp());

        $datetime = $this->getTime();

        $this->assertEquals(0, $datetime->diff(date_create_from_format('U',$time))->format('%s'));

    }

}
