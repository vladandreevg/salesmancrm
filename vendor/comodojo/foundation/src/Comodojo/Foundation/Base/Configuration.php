<?php namespace Comodojo\Foundation\Base;

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

class Configuration {

    use ConfigurationParametersTrait;

    /**
     * Create a configuration object from array of values
     *
     * @param array $configuration
     * @return self
     */
    public function __construct(array $configuration = []) {

        $this->parameters = array_merge($this->parameters, $configuration);

    }

    /**
     * Check if a property is defined
     *
     * @deprecated
     * @see Configuration::has()
     *
     * @param string $property
     * @return bool
     */
    public function isDefined($property) {

        return $this->has($property);

    }

    /**
     * Merge a bundle of properties into actual configuration model
     *
     * @param array $properties
     * @return self
     */
    public function merge(array $properties) {

        $this->parameters = array_replace($this->parameters, $properties);

        return $this;

    }

    public static function create(array $configuration = []) {

        return new Configuration($configuration);

    }

}
