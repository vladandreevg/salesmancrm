<?php namespace Comodojo\Foundation\Base;

use \Comodojo\Foundation\Base\Configuration;
use \Comodojo\Foundation\Base\ConfigurationTrait;

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

abstract class AbstractVersion {

    use ConfigurationTrait;

    /**
     * Component name
     *
     * @var string
     */
    protected $name;

    /**
     * Component brief description
     *
     * @var string
     */
    protected $description;

    /**
     * Current version
     *
     * @var     string
     */
    protected $version;

    /**
     * Ascii fancy logo
     *
     * @var     string
     */
    protected $ascii;

    /**
     * Prefix for configuration item names
     *
     * @var     string
     */
    protected $prefix;

    /**
     * Template for full-description;
     *
     * @var     string
     */
    protected $template = "\n{ascii}\n{description} ({version})\n";

    /**
     * Create a version identifier class
     *
     * @param Configuration|null $configuration
     * @param string $prefix
     */
    public function __construct(Configuration $configuration = null) {

        if ($configuration !== null) $this->setConfiguration($configuration);

    }

    /**
     * Get the version name
     *
     * @return string
     */
    public function getName() {

        $name_override = $this->getConfigurationOverride("name");

        return $name_override !== null ? $name_override : $this->name;

    }

    /**
     * Get the version description
     *
     * @return string
     */
    public function getDescription() {

        $desc_override = $this->getConfigurationOverride("description");

        return $desc_override !== null ? $desc_override : $this->description;

    }

    /**
     * Get the current version
     *
     * @return string
     */
    public function getVersion() {

        $release_override = $this->getConfigurationOverride("release");

        return $release_override !== null ? $release_override : $this->version;

    }

    /**
     * Get the version ascii
     *
     * @return string
     */
    public function getAscii() {

        $ascii_override = $this->getConfigurationOverride("ascii");

        return $ascii_override !== null ? $ascii_override : $this->ascii;

    }

    /**
     * Return a composed-version of nominal values
     *
     * @return  string
     */
    public function getFullDescription() {

        return strtr($this->template, [
            "{name}" => $this->getName(),
            "{description}" => $this->getDescription(),
            "{version}" => $this->getVersion(),
            "{ascii}" => $this->getAscii()
        ]);

    }

    private function getConfigurationOverride($item) {

        return $this->configuration !== null ?
            $this->configuration->get($this->prefix."version-$item") :
            null;

    }

}
