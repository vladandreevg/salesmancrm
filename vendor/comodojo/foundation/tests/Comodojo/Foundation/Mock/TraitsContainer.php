<?php namespace Comodojo\Foundation\Tests\Mock;

use \Comodojo\Foundation\Base\ConfigurationTrait;
use \Comodojo\Foundation\Events\EventsTrait;
use \Comodojo\Foundation\Logging\LoggerTrait;

class TraitsContainer extends \PHPUnit_Framework_TestCase {

    use ConfigurationTrait;
    use EventsTrait;
    use LoggerTrait;

}
