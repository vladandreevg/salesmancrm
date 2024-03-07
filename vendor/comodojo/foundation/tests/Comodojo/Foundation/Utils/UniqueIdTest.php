<?php namespace Comodojo\Foundation\Tests\Utils;

use \Comodojo\Foundation\Utils\UniqueId;

class UniqueIdTest extends \PHPUnit_Framework_TestCase {

    const GENERATED_UIDS = 10000;

    public function testUniqueIds() {

        $uids = [];

        for ($i=0; $i <= self::GENERATED_UIDS; $i++) {
            $uids[$i] = UniqueId::generate();
        }

        $this->assertTrue( count($uids) === count(array_unique($uids)) );

    }

    public function testVariableIds() {

        $uids = [];

        for ($i=1; $i <= 512; $i++) {
            $uids[] = [$i, UniqueId::generate($i)];
        }

        foreach ($uids as $uid) {
            $this->assertEquals($uid[0], strlen($uid[1]));
        }

    }

    public function testCustomUid() {

        $pattern = "Marvin";

        $uid = UniqueId::generateCustom($pattern);

        $this->assertStringStartsWith("$pattern-", $uid);

    }


}
