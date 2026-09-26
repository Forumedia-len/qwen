<?php
namespace AC\core\system\object;

use Traversable;

class StdObject extends Entity implements \IteratorAggregate
{
  /**
   * Retrieve an external iterator
   * @link  https://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return Traversable|\ArrayIterator An instance of an object implementing <b>Iterator</b> or
   * <b>Traversable</b>
   * @since 5.0.0
   */
  public function getIterator(): Traversable|\ArrayIterator
  {
    return new \ArrayIterator($this->_params);
  }
}