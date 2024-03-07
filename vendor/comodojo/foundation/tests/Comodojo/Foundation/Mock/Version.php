<?php namespace Comodojo\Foundation\Tests\Mock;

use \Comodojo\Foundation\Base\AbstractVersion;

class Version extends AbstractVersion {

    protected $name = 'Heart of Gold';
    protected $description = 'The first spacecraft to make use of the Infinite Improbability Drive';
    protected $version = '42';
    protected $ascii = "/r/n                       _    __         /r/n".
                       "|_| _  _  ___|_    _ _|_   /__ _  |  _|/r/n".
                       "| |(/_(_| |  |_   (_) |    \_|(_) | (_|/r/n";

    protected $template = "{name} + {description} + {version}";
    protected $prefix = 'mock-';

}
