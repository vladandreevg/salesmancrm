<?php namespace Comodojo\Foundation\Timing;

use \DateTime;
use \DateTimeZone;
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

trait TimingTrait {

    protected $timing;

    public function setTiming($time = null) {

        if ( empty($time) ) {
            $this->timing = microtime(true);
            return $this;
        } else if ( is_float($time) === false ) {
            throw new InvalidArgumentException("Timing reference should be a float");
        } else {
            $this->timing = $time;
            return $this;
        }

    }

    public function getTiming() {

        return $this->timing;

    }

    public function getTime() {

        $timezone = new DateTimeZone(date_default_timezone_get());

        return DateTime::createFromFormat('U.u', $this->timing)->setTimezone($timezone);

    }

}
