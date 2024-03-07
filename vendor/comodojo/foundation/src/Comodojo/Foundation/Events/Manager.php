<?php namespace Comodojo\Foundation\Events;

use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Psr\Log\LoggerInterface;
use \League\Event\Emitter;
use \League\Event\ListenerInterface;

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

class Manager extends Emitter {

    private $logger;

    public function __construct(LoggerInterface $logger = null) {

        $this->logger = is_null($logger) ? LogManager::create(null, false) : $logger;

    }

    public function getLogger() {

        return $this->logger;

    }

    public function subscribe($event, $class, $priority = 0) {

        $callable = $this->convertToListener($class, $event);

        if ( $callable === false ) return false;

        return $this->addListener($event, $callable, $priority);

    }

    public function subscribeOnce($event, $class, $priority = 0) {

        $callable = $this->convertToListener($class, $event);

        if ( $callable === false ) return false;

        return $this->addOneTimeListener($event, $callable, $priority);

    }

    public function load(array $events) {

        foreach($events as $event) {

            if ( !isset($event['class']) || !isset($event["event"]) ) {

                $this->logger->error("Invalid event definition", $event);
                continue;

            }

            $priority = isset($event['priority']) ? $event['priority'] : 0;
            $onetime = isset($event['onetime']) ? $event['onetime'] : false;

            if ( $onetime ) $this->subscribeOnce($event['event'], $event['class'], $priority);
            else $this->subscribe($event['event'], $event['class'], $priority);

        }

    }

    public static function create(LoggerInterface $logger = null) {

        return new Manager($logger);

    }

    protected function convertToListener($class, $event) {

        if ( !class_exists($class) ) {

            $this->logger->error("Cannot subscribe class $class to event $event: cannot find class");

            return false;

        }

        $callable = new $class();

        if ( $callable instanceof ListenerInterface ) {

            $this->logger->debug("Subscribing handler $class to event $event");

            return $callable;

        }

        $this->logger->error("Cannot subscribe class $class to event $event: class is not an instance of \League\Event\ListenerInterface");

        return false;

    }

}
