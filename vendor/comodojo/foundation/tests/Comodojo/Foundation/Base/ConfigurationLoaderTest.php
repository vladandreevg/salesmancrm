<?php namespace Comodojo\Foundation\Tests\Base;

use \Comodojo\Foundation\Base\ConfigurationLoader;

class ConfigurationLoaderTest extends \PHPUnit_Framework_TestCase {

    protected $config;

    protected function setUp() {

        $basepath = realpath(dirname(__FILE__)."/../../../root/");
        $config_file = "$basepath/config/config.yml";

        $this->config = ConfigurationLoader::load($config_file, [
            'base-path' => $basepath
        ]);

    }

    protected function tearDown() {

        unset($this->config);

    }

    public function testConfiguration() {

        $this->assertEquals(300, $this->config->get("routing-table-ttl"));

    }

}
