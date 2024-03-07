<?php namespace Comodojo\Foundation\Base;

use \InvalidArgumentException;

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

trait ConfigurationParametersTrait {

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Get parameter (property) from stack
     *
     * This method supports nesting of properties using dot notation
     *
     * @example Configuration::get("authentication.ldap.server")
     * @param string $parameter
     * @return mixed|null
     */
    public function get($parameter=null) {

        if ( is_null($parameter) ) return $this->parameters;

        return $this->getFromParts(self::splitParts($parameter));

    }

    /**
     * Set a parameter (property)
     *
     * This method supports nesting of properties using dot notation
     *
     * @example Configuration::set("authentication.ldap.server", "192.168.1.1")
     * @param string $parameter
     * @return self
     */
    public function set($parameter, $value) {

        $parts = self::splitParts($parameter);

        if ( empty($parts) ) throw new InvalidArgumentException("Invalid parameter $parameter");

        $this->setFromParts($parts, $value);

        return $this;

    }

    /**
     * Check if parameter (property) is defined in current stack
     *
     * This method supports nesting of properties using dot notation
     *
     * @example Configuration::has("authentication.ldap.server")
     * @param string $parameter
     * @return bool
     */
    public function has($parameter) {

        return is_null($this->getFromParts(self::splitParts($parameter))) ? false : true;

    }

    /**
     * Remove (delete) parameter (property) from stack
     *
     * This method supports nesting of properties using dot notation
     *
     * @example Configuration::delete("authentication.ldap.server")
     * @param string $parameter
     * @return bool
     */
    public function delete($parameter = null) {

        if ( is_null($parameter) ) {

            $this->parameters = [];
            return true;

        }

        $parts = self::splitParts($parameter);

        if ( empty($parts) ) throw new InvalidArgumentException("Invalid parameter $parameter");

        return $this->deleteFromParts($parts);

    }

    protected function getFromParts(array $parts) {

        if ( empty($parts) ) return null;

        $reference = &$this->parameters;

        foreach ($parts as $part) {

            if ( !isset($reference[$part]) ) {
                return null;
            }

            $reference = &$reference[$part];

        }

        $data = $reference;

        return $data;

    }

    protected function setFromParts(array $parts, $value) {

        $reference = &$this->parameters;

        $plength = count($parts);

        for ($i=0; $i < $plength; $i++) {

            if ( !isset($reference[$parts[$i]]) ) {
                $reference[$parts[$i]] = [];
            }

            if ( ($i < $plength-1) && !is_array($reference[$parts[$i]]) ) {
                $reference[$parts[$i]] = [$reference[$parts[$i]]];
            }

            $reference = &$reference[$parts[$i]];

        }

        $reference = $value;

        return true;

    }

    protected function deleteFromParts(array $parts) {

        $reference = &$this->parameters;
        $l = count($parts);

        for ($i=0; $i < $l; $i++) {
            if ( !isset($reference[$parts[$i]]) ) {
                return false;
            }
            if ($i == $l-1) {
                unset($reference[$parts[$i]]);
            } else {
                $reference = &$reference[$parts[$i]];
            }
        }

        return true;

    }

    protected static function splitParts($parameter) {

        return preg_split('/(\s)?\.(\s)?/', $parameter, null, PREG_SPLIT_NO_EMPTY);

    }

}
