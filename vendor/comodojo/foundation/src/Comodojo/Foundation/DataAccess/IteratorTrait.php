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

trait IteratorTrait {

    /**
     * Reset the iterator
     */
    public function rewind() {

        reset($this->data);

    }

    /**
     * Get the current element
     *
     * @return mixed
     */
    public function current() {

        return current($this->data);

    }

    /**
     * Return the current key
     *
     * @return string|int
     */
    public function key() {

        return key($this->data);

    }

    /**
     * Move to next element
     */
    public function next() {

        return next($this->data);

    }

    /**
     * Check if element is valid (isset)
     *
     * @return boolean
     */
    public function valid() {

        return isset($this->data[$this->key()]);

    }

}
