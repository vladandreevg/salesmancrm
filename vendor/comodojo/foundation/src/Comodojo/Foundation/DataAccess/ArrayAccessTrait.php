<?php namespace Comodojo\Foundation\DataAccess;

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

trait ArrayAccessTrait {

    /**
     * Return the value at index
     *
     * @return string $index The offset
     */
     public function offsetGet($index) {

         return $this->data[$index];

     }

     /**
     * Assigns a value to index offset
     *
     * @param string $index The offset to assign the value to
     * @param mixed  $value The value to set
     */
     public function offsetSet($index, $value) {

         $this->data[$index] = $value;

     }

     /**
     * Unsets an index
     *
     * @param string $index The offset to unset
     */
     public function offsetUnset($index) {

         unset($this->data[$index]);

     }

     /**
     * Check if an index exists
     *
     * @param string $index Offset
     * @return boolean
     */
     public function offsetExists($index) {

         return $this->offsetGet($index) !== null;

     }

}
