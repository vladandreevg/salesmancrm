<?php namespace Comodojo\Foundation\Console;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;

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

class StopEventListener {

    static public function listen(ConsoleTerminateEvent $event) {

        $command = $event->getCommand();
        $name = $command->getName();
        $output = $event->getOutput();
        $output->writeln("");

        if ( $command instanceof AbstractCommand ) {

            $logger = $command->getLogger();
            $time = number_format($command->getEndTime(), 2);

            $logger->notice("Command $name tooks $time secs");

        }

    }

}
