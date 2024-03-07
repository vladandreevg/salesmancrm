<?php namespace Comodojo\Foundation\Console;

use \Comodojo\Foundation\Base\Configuration;
use \Comodojo\Foundation\Base\ConfigurationTrait;
use \Symfony\Component\Console\Command\Command;
use \Comodojo\Foundation\Logging\LoggerTrait;
use \DateTime;

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

abstract class AbstractCommand extends Command {

    use ConfigurationTrait;
    use LoggerTrait;

    /**
     * @var DateTime
     */
    protected $start_time;

    public function setStartTime() {
        $this->start_time = microtime(true);
    }

    public function getEndTime() {
        $time = microtime(true);
        return $time - $this->start_time;
    }

    static public function init(Configuration $configuration) {

        $me = get_called_class();
        $myself = new $me();

        return $myself->setConfiguration($configuration);

    }

}
