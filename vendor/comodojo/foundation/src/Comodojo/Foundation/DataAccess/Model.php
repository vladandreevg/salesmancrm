<?php namespace Comodojo\Foundation\DataAccess;

use \UnexpectedValueException;
use \BadMethodCallException;

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


abstract class Model {

    const READWRITE = 1;

    const PROTECTDATA = 2;

    const READONLY = 3;

    protected $mode;

    protected $data = [];

    public function __get($name) {

        if ( array_key_exists($name, $this->data) ) {

            return $this->data[$name];

        }

        return null;

    }

    public function __set($name, $value) {

        if ( $this->mode === self::READONLY ) throw new BadMethodCallException("Cannot set item $name in readonly data mode");

        if ( $this->mode === self::PROTECTDATA && !array_key_exists($name, $this->data) ) throw new UnexpectedValueException("Cannot add item $name in protected data mode");

        $this->data[$name] = $value;

    }

    public function __unset($name) {

        if ( $this->mode === self::READONLY ) throw new BadMethodCallException("Cannot unset item $name in readonly data mode");

        if ( $this->mode === self::PROTECTDATA ) throw new BadMethodCallException("Cannot unset item $name in protected data mode");

        if ( isset($this->$name) ) unset($this->data[$name]);

    }

    public function __isset($name) {

        return array_key_exists($name, $this->data);

    }

    public function merge($data) {

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return $this;

    }

    public function export() {

        return $this->data;

    }

    public function import($data) {

        if ( $this->mode === self::READONLY ) throw new BadMethodCallException("Cannot import items in readonly data mode");

        $diff = array_diff_key($this->data, $data);

        if ( $this->mode === self::PROTECTDATA && !empty($diff) ) throw new UnexpectedValueException("Cannot import new items [".implode(", ", $diff)."] in protected data mode");

        $this->data = $data;

        return $this;

    }

    protected function setRaw($name, $value) {

        $this->data[$name] = $value;

        return $this;

    }

}
