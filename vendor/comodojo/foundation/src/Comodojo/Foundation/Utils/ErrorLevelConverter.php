<?php namespace Comodojo\Foundation\Utils;

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

class ErrorLevelConverter {

    protected static $levels = [
        1 => "E_ERROR",
        2 => "E_WARNING",
        4 => "E_PARSE",
        8 => "E_NOTICE",
        16 => "E_CORE_ERROR",
        32 => "E_CORE_WARNING",
        64 => "E_COMPILE_ERROR",
        128 => "E_COMPILE_WARNING",
        256 => "E_USER_ERROR",
        512 => "E_USER_WARNING",
        1024 => "E_USER_NOTICE",
        2048 => "E_STRICT",
        4096 => "E_RECOVERABLE_ERROR",
        8192 => "E_DEPRECATED",
        16384 => "E_USER_DEPRECATED",
        // 32767 => "E_ALL"
    ];

    /**
     * Convert a PHP error level to its name
     *
     * @param int $error_code
     * @return string
     */
    public static function convert(int $error_code) {

        return isset(self::$levels[$error_code]) ? self::$levels[$error_code] : "GENERIC_ERROR";

    }

}
