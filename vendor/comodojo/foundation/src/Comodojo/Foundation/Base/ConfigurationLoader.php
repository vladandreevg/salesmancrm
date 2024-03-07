<?php namespace Comodojo\Foundation\Base;

use \Exception;

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

class ConfigurationLoader extends AbstractYamlLoader {

    public static function load($file, array $attributes = []) {

        $conf = new Configuration($attributes);

        try {

            $conf->merge(static::importData($file));

            $base = $conf->get('base-path');
            $static = $conf->get('static-config');
            $env = $conf->get('env-config');

            if ( $env !== null ) {

                $env_path = substr($env, 0, 1) === '/' ? $env : "$base/$static/$env";

                $conf->merge(static::importData($env_path));

            }

        } catch (Exception $e) {

            throw $e;

        }

        return $conf;

    }

}
