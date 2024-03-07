<?php namespace Comodojo\Foundation\Tests\Validation;

use \Comodojo\Foundation\Validation\DataValidation as Validator;

class DataValidationTest extends \PHPUnit_Framework_TestCase {

    /**
     * Provides a set of value for plain validation.
     *
     * @return bool
     *
     * @dataProvider providerPlainValidUnits
     */
    public function testValidPlain($data, $format) {

        $this->assertTrue(Validator::validate($data, $format));

    }

    /**
     * Provides a set of value for plain validation.
     *
     * @return bool
     *
     * @dataProvider providerPlainInvalidUnits
     */
    public function testInvalidPlain($data, $format) {

        $this->assertFalse(Validator::validate($data, $format));

    }

    /**
     * Provides a set of value for filtered validation.
     *
     * @return bool
     *
     * @dataProvider providerFilteredValidUnits
     */
    public function testValidFiltered($data, $format, $filter) {

        $this->assertTrue(Validator::validate($data, $format, $filter));

    }

    /**
     * Provides a set of value for filtered validation.
     *
     * @return bool
     *
     * @dataProvider providerFilteredInvalidUnits
     */
    public function testInvalidFiltered($data, $format, $filter) {

        $this->assertFalse(Validator::validate($data, $format, $filter));

    }

    /**
     * Provides a set of value for plain validation.
     *
     * @return array
     */
    public function providerPlainValidUnits() {
        return [
            ['Marvin the sad robot',Validator::STRING],
            [true,Validator::BOOL],
            [42,Validator::INT],
            [42,Validator::NUMBER],
            [27.25,Validator::FLOAT],
            ['{"this":"is","a":"test","0":true}',Validator::JSON],
            ['a:3:{s:4:"this";s:2:"is";s:1:"a";s:4:"test";i:0;b:1;}',Validator::SERIALIZED],
            [[0=>"this",1=>"is",2=>"a",3=>"test"],Validator::ARRAYSTRICT],
            [["this"=>"is","a"=>"test"],Validator::STRUCT],
            ['2016-12-19T11:20:26+01:00',Validator::DATETIMEISO8601],
            [base64_encode("this is a test"),Validator::BASE64],
            [null,Validator::NULLVALUE],
            ['1482772894',Validator::TIMESTAMP]
        ];
    }

    /**
     * Provides a set of invalid value for plain validation.
     *
     * @return array
     */
    public function providerPlainInvalidUnits() {
        return [
            [42,Validator::STRING],
            [10,Validator::BOOL],
            ['test',Validator::INT],
            ['string',Validator::NUMBER],
            [1,Validator::FLOAT],
            ['{"this"=>"is","a":"test",true}',Validator::JSON],
            ['{s:4:"this";s:2:"is";s:1:"a";s:4:"test";i:0;b:1;}',Validator::SERIALIZED],
            [["this"=>"is","a","test",true],Validator::ARRAYSTRICT],
            [["this","is","a","test",true],Validator::STRUCT],
            ['2016-12-19T11:20',Validator::DATETIMEISO8601],
            ["this is a test",Validator::BASE64],
            [0,Validator::NULLVALUE],
            ['14827FC894',Validator::TIMESTAMP]
        ];
    }

    /**
     * Provides a set of value for filtered validation.
     *
     * @return array
     */
    public function providerFilteredValidUnits() {
        return [
            ['Marvin the sad robot',Validator::STRING, function($data) {
                return preg_match('/sad/',$data);
            }],
            [true,Validator::BOOL, function($data) {
                return $data === true;
            }],
            [42,Validator::INT, function($data) {
                return $data < 100;
            }],
            [42,Validator::NUMBER, function($data) {
                return $data < 100;
            }],
            [27.25,Validator::FLOAT, function($data) {
                return $data < 100;
            }],
            ['{"this":"is","a":"test","0":true}',Validator::JSON, function($data) {
                    $a = json_decode($data, true);
                return $a['this'] == 'is';
            }],
            ['a:3:{s:4:"this";s:2:"is";s:1:"a";s:4:"test";i:0;b:1;}',Validator::SERIALIZED, function($data) {
                return $data[0] == 'a';
            }],
            [[0=>"this",1=>"is",2=>"a",3=>"test"],Validator::ARRAYSTRICT, function($data) {
                return $data[2] == 'a';
            }],
            [["this"=>"is","a"=>"test"],Validator::STRUCT, function($data) {
                return $data['this'] == 'is';
            }],
            ['2016-12-19T11:20:26+01:00',Validator::DATETIMEISO8601, function($data) {
                $dc = new \DateTime('now');
                return \DateTime::createFromFormat(\DateTime::ATOM, $data)->diff($dc)->format('%s') > 0;
            }],
            ['1482772894',Validator::TIMESTAMP, function($data) {
                $dc = new \DateTime('now');
                return \DateTime::createFromFormat('U', $data)->diff($dc)->format('%s') > 0;
            }]
        ];
    }

    /**
     * Provides a set of value for filtered validation.
     *
     * @return array
     */
    public function providerFilteredInvalidUnits() {
        return [
            ['Marvin the sad robot',Validator::STRING, function($data) {
                return preg_match('/add/',$data);
            }],
            [true,Validator::BOOL, function($data) {
                return $data === false;
            }],
            [42,Validator::INT, function($data) {
                return $data > 100;
            }],
            [42,Validator::NUMBER, function($data) {
                return $data > 100;
            }],
            [27.25,Validator::FLOAT, function($data) {
                return $data > 100;
            }],
            ['{"this":"is","a":"test","0":true}',Validator::JSON, function($data) {
                    $a = json_decode($data, true);
                return $a['this'] == 'are';
            }],
            ['a:3:{s:4:"this";s:2:"is";s:1:"a";s:4:"test";i:0;b:1;}',Validator::SERIALIZED, function($data) {
                return $data[0] == 'b';
            }],
            [[0=>"this",1=>"is",2=>"a",3=>"test"],Validator::ARRAYSTRICT, function($data) {
                return $data[2] == 'b';
            }],
            [["this"=>"is","a"=>"test"],Validator::STRUCT, function($data) {
                return $data['this'] == 'a';
            }],
            ['2016-12-19T11:20:26+01:00',Validator::DATETIMEISO8601, function($data) {
                $dc = new \DateTime('now');
                return \DateTime::createFromFormat(\DateTime::ATOM, $data)->diff($dc)->format('%s') == 0;
            }],
            ['1482772894',Validator::TIMESTAMP, function($data) {
                $dc = new \DateTime('now');
                return \DateTime::createFromFormat('U', $data)->diff($dc)->format('%s') == 0;
            }]
        ];
    }

}
