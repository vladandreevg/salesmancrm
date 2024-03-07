<?php namespace Comodojo\Foundation\Console;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Logger\ConsoleLogger;

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

class StartEventListener {

    static public function listen(ConsoleCommandEvent $event) {

        $output = $event->getOutput();
        $command = $event->getCommand();
        $application = $command->getApplication();

        if ( $command instanceof AbstractCommand ) {

            $logger = new ConsoleLogger($output);
            $command->setLogger($logger);

            $command->setStartTime();

            $output->writeln([
                '',
                $application->getVersion(),
                '-----------------------------',
                ''
            ]);

        }

    }

}
