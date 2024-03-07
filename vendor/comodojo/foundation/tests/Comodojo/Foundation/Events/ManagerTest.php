<?php namespace Comodojo\Foundation\Tests\Events;

use \Comodojo\Foundation\Events\Manager;
use \League\Event\Emitter;
use \Monolog\Logger;

class ManagerTest extends \PHPUnit_Framework_TestCase {

    private static $events;

    public static function setupBeforeClass() {

        $logger = new Logger('test');

        self::$events = new Manager($logger);

    }

    public function testEmitter() {

        $this->assertInstanceOf('\League\Event\Emitter', self::$events);
        $this->assertInstanceOf('\Comodojo\Foundation\Events\Manager', self::$events);

    }

}
