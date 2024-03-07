<?php namespace Comodojo\Foundation\Console;

use \Comodojo\Foundation\Base\Configuration;
use \Comodojo\Foundation\Base\ConfigurationTrait;
use \Symfony\Component\Console\Application;
use \Symfony\Component\EventDispatcher\EventDispatcher;
use \Symfony\Component\Console\ConsoleEvents;


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

class Processor {

    use ConfigurationTrait;

    protected $application_header;

    protected $application_version;

    protected $application;

    // $commands should be an array of classes

    public function __construct(
        Configuration $configuration,
        array $commands,
        $application_header = null,
        $application_version = null
    ) {

        $events = new EventDispatcher();
        $application = new Application();
        $application->setDispatcher($events);
        $this->setApplication($application);
        $this->setConfiguration($configuration);

        $events->addListener(ConsoleEvents::COMMAND, '\Comodojo\Foundation\Console\StartEventListener::listen');
        $events->addListener(ConsoleEvents::TERMINATE, '\Comodojo\Foundation\Console\StopEventListener::listen');

        if ( !empty($application_header) ) $this->setApplicationHeader($application_header);
        if ( !empty($application_version) ) $this->application->setVersion($application_version);

        foreach ($commands as $command) {

            $this->application->add(
                $command::init($configuration)
            );

        }

    }

    public function setApplicationHeader($header) {
        $this->application_header = $header;
        $this->application->setName($header);
        return $this;
    }

    public function getApplicationHeader() {
        return $this->application_header;
    }

    public function setApplicationVersion($version) {
        $this->application_version = $version;
        $this->application->setVersion($version);
        return $this;
    }

    public function getApplicationVersion() {
        return $this->application_version;
    }

    public function run() {

        $this->application->run();

    }

    public function setApplication(Application $application) {

        $this->application = $application;
        return $this;

    }

    public function getApplication() {

        return $this->application;

    }

    public static function init(
        Configuration $configuration,
        array $commands,
        $application_header = null,
        $application_version = null
    ) {

        return new Processor(
            $configuration,
            $commands,
            $application_header,
            $application_version
        );

    }

}
