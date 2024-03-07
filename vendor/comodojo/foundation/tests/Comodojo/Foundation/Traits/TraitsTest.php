<?php namespace Comodojo\Foundation\Tests\Traits;

use \Comodojo\Foundation\Tests\Mock\TraitsContainer;
use \Comodojo\Foundation\Base\Configuration;
use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Comodojo\Foundation\Events\Manager as EventsManager;

class TraitsTest extends \PHPUnit_Framework_TestCase {

    protected static $config;
    protected static $logger;
    protected static $events;

    public static function setupBeforeClass() {

        self::$config = new Configuration([]);
        self::$logger = new LogManager('test');
        self::$events = new EventsManager(self::$logger->getLogger());

    }

    public function testTraits() {

        $container = new TraitsContainer;

        $this->assertNull($container->getConfiguration());
        $this->assertNull($container->getLogger());
        $this->assertNull($container->getEvents());

        $result = $container->setConfiguration(self::$config);
        $this->assertInstanceOf('\Comodojo\Foundation\Tests\Mock\TraitsContainer', $result);
        $result = $container->setLogger(self::$logger->getLogger());
        $this->assertInstanceOf('\Comodojo\Foundation\Tests\Mock\TraitsContainer', $result);
        $result = $container->setEvents(self::$events);
        $this->assertInstanceOf('\Comodojo\Foundation\Tests\Mock\TraitsContainer', $result);

        $result = $container->getConfiguration();
        $this->assertInstanceOf('\Comodojo\Foundation\Base\Configuration', $result);
        $result = $container->getLogger();
        $this->assertInstanceOf('\Psr\Log\LoggerInterface', $result);
        $result = $container->getEvents();
        $this->assertInstanceOf('\Comodojo\Foundation\Events\Manager', $result);

    }

}
