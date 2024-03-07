<?php namespace Comodojo\Foundation\Tests\Base;

use Comodojo\Foundation\Tests\Mock\Version;
use \Comodojo\Foundation\Base\ConfigurationLoader;

class VersionTest extends \PHPUnit_Framework_TestCase {

    protected $config;

    protected $name = 'Heart of Gold';
    protected $description = 'The first spacecraft to make use of the Infinite Improbability Drive';
    protected $version = '42';
    protected $ascii = "/r/n                       _    __         /r/n".
                       "|_| _  _  ___|_    _ _|_   /__ _  |  _|/r/n".
                       "| |(/_(_| |  |_   (_) |    \_|(_) | (_|/r/n";

    protected $template = "{name} + {description} + {version}";

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

    public function testVersion() {

        $version = new Version();

        $this->assertEquals($this->name, $version->getName());
        $this->assertEquals($this->description, $version->getDescription());
        $this->assertEquals($this->version, $version->getVersion());
        $this->assertEquals($this->ascii, $version->getAscii());

    }

    public function testVersionConfigurationOverride() {

        $version = new Version($this->config);

        $this->assertEquals('42 is the new black', $version->getName());
        $this->assertEquals($this->description, $version->getDescription());

    }

}
