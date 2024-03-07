<?php namespace Comodojo\Foundation\Logging;

use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\SyslogHandler;
use \Monolog\Handler\ErrorLogHandler;
use \Monolog\Handler\NullHandler;

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

class ProviderBuilder {

    const DEFAULT_STREAM = 'comodojo.log';

    const DEFAULT_SYSLOG_FACILITY = LOG_USER;

    const DEFAULT_SYSLOG_LOGOPTS = LOG_PID;

    public static function StreamHandler($name, $basepath, $parameters) {

        $stream = $basepath.'/'.(empty($parameters['stream']) ? DEFAULT_STREAM : $parameters['stream']);

        $level = Levels::getLevel( empty($parameters['level']) ? null : $parameters['level'] );

        $bubble = self::getBubble( empty($parameters['bubble']) ? true : $parameters['bubble'] );

        $filePermission = self::getFilePermission( empty($parameters['filePermission']) ? null : $parameters['filePermission'] );

        $useLocking = self::getLocking( empty($parameters['useLocking']) ? false : $parameters['useLocking'] );

        return new StreamHandler($stream, $level, $bubble, $filePermission, $useLocking);

    }

    public static function SyslogHandler($name, $parameters) {

        if ( empty($parameters['ident']) ) return null;

        $facility = empty($parameters['facility']) ? DEFAULT_SYSLOG_FACILITY : $parameters['facility'];

        $level = Levels::getLevel( empty($parameters['level']) ? null : $parameters['level'] );

        $bubble = self::getBubble( empty($parameters['bubble']) ? true : $parameters['bubble'] );

        $logopts = empty($parameters['logopts']) ? DEFAULT_SYSLOG_LOGOPTS : $parameters['logopts'];

        return new SyslogHandler($parameters['ident'], $facility, $level, $bubble, $logopts);

    }

    public static function ErrorLogHandler($name, $parameters) {

        $messageType = empty($parameters['messageType']) ? ErrorLogHandler::OPERATING_SYSTEM : $parameters['messageType'];

        $level = Levels::getLevel( empty($parameters['level']) ? null : $parameters['level'] );

        $bubble = self::getBubble( empty($parameters['bubble']) ? true : $parameters['bubble'] );

        $expandNewlines = self::getExpandNewlines( empty($parameters['expandNewlines']) ? false : $parameters['expandNewlines'] );

        return new ErrorLogHandler($messageType, $level, $bubble, $expandNewlines);

    }

    public static function NullHandler($name = null, $parameters = array()) {

        $level = Levels::getLevel( empty($parameters['level']) ? null : $parameters['level'] );

        return new NullHandler( Levels::getLevel() );

    }

    protected static function getBubble($bubble) {

        return filter_var($bubble, FILTER_VALIDATE_BOOLEAN, array(
            'options' => array(
                'default' => true
            )
        ));

    }

    protected static function getFilePermission($filepermission = null) {

        if ( is_null($filepermission) ) return null;

        return filter_var($filepermission, FILTER_VALIDATE_INT, array(
            'options' => array(
                'default' => 0644
            ),
            'flags' => FILTER_FLAG_ALLOW_OCTAL
        ));

    }

    protected static function getLocking($uselocking) {

        return filter_var($uselocking, FILTER_VALIDATE_BOOLEAN, array(
            'options' => array(
                'default' => false
            )
        ));

    }

    protected static function getExpandNewlines($expandNewlines) {

        return filter_var($expandNewlines, FILTER_VALIDATE_BOOLEAN, array(
            'options' => array(
                'default' => false
            )
        ));

    }

}
