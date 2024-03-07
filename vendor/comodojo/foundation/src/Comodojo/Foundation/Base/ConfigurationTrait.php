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
 
trait ConfigurationTrait {

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Get current configuration
     *
     * @return Configuration
     */
    public function getConfiguration() {

        return $this->configuration;

    }

    /**
     * Set current configuration
     *
     * @param Configuration $configuration
     * @return self
     */
    public function setConfiguration(Configuration $configuration) {

        $this->configuration = $configuration;

        return $this;

    }

}
