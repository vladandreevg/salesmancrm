<?php namespace Comodojo\Foundation\Validation;

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

class DataFilter {

    /**
     * Filter an integer.
     *
     * This method is a shortcut to filter_var using FILTER_VALIDATE_INT
     *
     * @param array $int Int to filter
     * @param array $min Min value (default to ~PHP_INT_MAX)
     * @param array $max Max value (default to PHP_INT_MAX)
     * @param array $default Default value
     * @return int
     */
    public static function filterInteger($int, $min=~PHP_INT_MAX, $max=PHP_INT_MAX, $default=0) {

        return filter_var($int, FILTER_VALIDATE_INT, array(
            'options' => array(
                'default' => $default,
                'min_range' => $min,
                'max_range' => $max
            )
        ));

    }

    /**
     * Filter a TCP/UDP port
     *
     * @param array $port
     * @param array $default
     * @return int
     */
    public static function filterPort($port, $default = 80) {

        return self::filterInteger($port, 1, 65535, $default);

    }

    /**
     * filter a bool.
     *
     * This method is a shortcut to filter_var using FILTER_VALIDATE_BOOLEAN
     *
     * @param array $bool
     * @param array $default
     * @return array
     */
    public static function filterBoolean($bool, $default = false) {

        return filter_var($bool, FILTER_VALIDATE_BOOLEAN, array(
            'options' => array(
                'default' => $default
            )
        ));

    }

}
