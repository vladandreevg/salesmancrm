<?php namespace Comodojo\Foundation\Utils;

use \Comodojo\Foundation\DataAccess\Model;
use \Comodojo\Foundation\DataAccess\IteratorTrait;
use \Comodojo\Foundation\DataAccess\CountableTrait;
use \Iterator;
use \Countable;

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

class ClassProperties extends Model implements Iterator, Countable {

    use IteratorTrait;
    use CountableTrait;

    protected $mode = self::PROTECTDATA;

    public function __construct($properties = []) {

        $this->data = $properties;

    }

    public static function create($properties = []) {

        $classProperties = new ClassProperties($properties);

        return $classProperties;

    }

}
