<?php namespace Comodojo\Foundation\Logging;

use \Comodojo\Foundation\Base\Configuration;
use \Monolog\Logger;
use \Monolog\Handler\HandlerInterface;

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

class Manager {

    const DEFAULT_LOGGER_NAME = 'comodojo';

    private $logger;

    private $base_path;

    public function __construct($name = null) {

        $this->logger = new Logger(empty($name) ? self::DEFAULT_LOGGER_NAME : $name);

    }

    public function setBasePath($path) {

        $this->base_path = $path;

    }

    public function getBasePath() {

        return $this->base_path;

    }

    public function getLogger() {

        return $this->logger;

    }

    public function init($enable = true, $providers = array()) {

        if ( !$enable ) {

            $this->logger->pushHandler( ProviderBuilder::NullHandler() );

            return $this;

        }

        if ( empty($providers) ) return $this;

        foreach ($providers as $provider => $parameters) {

            $handler = $this->createProvider($provider, $parameters);

            if ( $handler instanceof HandlerInterface ) $this->logger->pushHandler($handler);

        }

        return $this;

    }

    public static function createFromConfiguration(Configuration $configuration, $stanza = null) {

        $base_path = $configuration->get('base-path');

        $log = null;

        if ( $stanza !== null ) {
            $log = $configuration->get($stanza);
        }

        if ( $log === null ) {
            $log = $configuration->get('log');
        }

        $name = empty($log['name']) ? null : $log['name'];

        $enable = (empty($log['enable']) || $log['enable'] !== false) ? true : false;

        $providers = empty($log['providers']) ? [] : $log['providers'];

        $manager = new Manager($name);

        $manager->setBasePath($base_path);

        return $manager->init($enable, $providers);

    }

    public static function create($name = null, $enable = true, $providers = array()) {

        $manager = new Manager($name);

        return $manager->init($enable, $providers);

    }

    private function createProvider($provider, $parameters) {

        if ( empty($parameters['type']) ) return null;

        switch ( $parameters['type'] ) {

            case 'StreamHandler':
                $handler = ProviderBuilder::StreamHandler($provider, $this->base_path, $parameters);
                break;

            case 'SyslogHandler':
                $handler = ProviderBuilder::SyslogHandler($provider, $parameters);
                break;

            case 'ErrorLogHandler':
                $handler = ProviderBuilder::ErrorLogHandler($provider, $parameters);
                break;

            case 'NullHandler':
                $handler = ProviderBuilder::NullHandler($provider, $parameters);
                break;

            default:
                $handler = null;
                break;

        }

        return $handler;

    }

}
