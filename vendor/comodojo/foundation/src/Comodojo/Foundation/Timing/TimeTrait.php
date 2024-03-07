<?php namespace Comodojo\Foundation\Timing;

use \Comodojo\Foundation\Validation\DataValidation;
use \DateTime;
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

trait TimeTrait {

    protected $time;

    public function setTime($reference = 'now') {

        $this->time = new DateTime($reference);

        return $this;

    }

    public function getTime() {

        return $this->time;

    }

    public function getTimestamp() {

        return (int) $this->time->format('U');

    }

    public function setTimestamp($timestamp = null) {

        if ( empty($timestamp) ) {
            return $this->setTime();
        } else if ( DataValidation::validateTimestamp($timestamp) === false ) {
            throw new InvalidArgumentException("Invalid timestamp provided");
        } else {
            $this->time = DateTime::createFromFormat('U', $timestamp);
            return $this;
        }

    }

}
