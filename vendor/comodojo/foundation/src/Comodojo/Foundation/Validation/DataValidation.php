<?php namespace Comodojo\Foundation\Validation;

use \DateTime;
use \UnexpectedValueException;

/**
 * @package     Comodojo Foundation
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     MIT
 *
 * LICENSE:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class DataValidation {

    const STRING = 'STRING';
    const BOOL = 'BOOL';
    const BOOLEAN = 'BOOL';
    const INT = 'INT';
    const INTEGER = 'INT';
    const NUMBER = 'NUMBER';
    const DOUBLE = 'FLOAT';
    const FLOAT = 'FLOAT';
    const JSON = 'JSON';
    const SERIALIZED = 'SERIALIZED';
    const ARRAYSTRICT = 'ARRAY';
    const STRUCT = 'STRUCT';
    const DATETIMEISO8601 = 'DATETIMEISO8601';
    const BASE64 = 'BASE64';
    const NULLVALUE = 'NULL';
    const TIMESTAMP = 'TIMESTAMP';

    private static $supported_types = array (
        "STRING" => 'self::validateString',
        "BOOL" => 'self::validateBoolean',
        "BOOLEAN" => 'self::validateBoolean',
        "INT" => 'self::validateInteger',
        "INTEGER" => 'self::validateInteger',
        "NUMBER" => 'self::validateNumeric',
        "FLOAT" => 'self::validateFloat',
        "DOUBLE" => 'self::validateFloat',
        "JSON" => 'self::validateJson',
        "SERIALIZED" => 'self::validateSerialized',
        "ARRAY" => 'self::validateArray',
        "STRUCT" => 'self::validateStruct',
        "DATETIMEISO8601" => 'self::validateDatetimeIso8601',
        "BASE64" => 'self::validateBase64',
        "NULL" => 'self::validateNull',
        "TIMESTAMP" => 'self::validateTimestamp'
    );

    /**
     * Generic validator.
     *
     * @param mixed $data Data to validate
     * @param string $type Type (one of self::$supported_types)
     * @param callable $filter Custom filter
     * @return bool
     * @throws UnexpectedValueException
     */
    public static function validate($data, $type, callable $filter=null) {

        $type = strtoupper($type);

        if ( !array_key_exists($type, self::$supported_types) ) {
            throw new UnexpectedValueException("Bad validation type");
        }

        return call_user_func(self::$supported_types[$type], $data, $filter);

    }

    /**
     * String validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateString($data, callable $filter=null) {
        if ( is_string($data) === false ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Bool validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateBoolean($data, callable $filter=null) {
        if ( is_bool($data) === false ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Int validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateInteger($data, callable $filter=null) {
        if ( is_int($data) === false ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Numeric validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateNumeric($data, callable $filter=null) {
        if ( is_numeric($data) === false ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Float validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateFloat($data, callable $filter=null) {
        if ( is_float($data) === false ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Json validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateJson($data, callable $filter=null) {
        $decoded = json_decode($data);
        if ( is_null($decoded) ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Serialized values validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateSerialized($data, callable $filter=null) {
        $decoded = @unserialize($data);
        if ( $decoded === false && $data != @serialize(false) ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Array (strict) validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateArray($data, callable $filter=null) {
        if ( is_array($data) === false ) return false;
        if ( self::validateStruct($data) === true && array() !== $data  ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Struct (strict) validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateStruct($data, callable $filter=null) {
        if ( is_array($data) === false && is_object($data) === false ) return false;
        $array = (array) $data;
        $valid = array_keys($array) !== range(0, count($array) - 1);
        return $valid === false ? false : self::applyFilter($data, $filter);
    }

    /**
     * Iso8601-datetime validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateDatetimeIso8601($data, callable $filter=null) {
        if ( DateTime::createFromFormat(DateTime::ATOM, $data) === false ) return false;
        return self::applyFilter($data, $filter);
    }

    /**
     * Base64 validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateBase64($data, callable $filter=null) {
        return base64_encode(base64_decode($data, true)) === $data ?
            self::applyFilter($data, $filter)
            : false;
    }

    /**
     * Null value validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateNull($data, callable $filter=null) {
        return is_null($data);
    }

    /**
     * Timestamp (epoch) validator.
     *
     * @param mixed $data Data to validate
     * @param callable $filter Custom filter
     * @return bool
     */
    public static function validateTimestamp($data, callable $filter=null) {

        return (
            (string) (int) $data === (string) $data
            && ($data <= PHP_INT_MAX)
            && ($data >= ~PHP_INT_MAX)
        ) ? self::applyFilter($data, $filter) : false;

    }

    /**
     * @param mixed $data
     * @param callable $filter
     * @return bool
     */
    private static function applyFilter($data, callable $filter=null) {
        return $filter === null ? true : (bool) call_user_func($filter, $data);
    }

}
