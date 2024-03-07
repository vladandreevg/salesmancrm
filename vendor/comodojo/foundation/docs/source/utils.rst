Generic utilities
=================

Array Operations
----------------

ArrayOps::circularDiffKeys
..........................

Perform a circular diff between two arrays using keys.

This method is useful to compute the actual differences between two arrays.

Usage:

.. code-block:: php

    <?php

    $left = [
        "ford" => "perfect",
        "marvin" => "android",
        "arthur" => "dent"
    ];

    $right = [
        "marvin" => "android",
        "tricia" => "mcmillan"
    ];

    var_dump(\Comodojo\Foundation\Utils\ArrayOps::circularDiffKeys($left, $right));

It returns:

.. code::

    array(3) {
      [0] =>
      array(2) {
        'ford' =>
        string(7) "perfect"
        'arthur' =>
        string(4) "dent"
      }
      [1] =>
      array(1) {
        'marvin' =>
        string(7) "android"
      }
      [2] =>
      array(1) {
        'tricia' =>
        string(8) "mcmillan"
      }
    }

ArrayOps::filterByKeys
......................

Filter an array by an array of keys.

Usage:

.. code-block:: php

    <?php

    $stack = [
        "ford" => "perfect",
        "marvin" => "android",
        "arthur" => "dent"
    ];

    $keys = [
        "ford",
        "arthur"
    ];

    var_dump(\Comodojo\Foundation\Utils\ArrayOps::filterByKeys($keys, $stack));

It returns:

.. code::

    array(2) {
      'ford' =>
      string(7) "perfect"
      'arthur' =>
      string(4) "dent"
    }

ArrayOps::replaceStrict
.......................

Perform a selective replace of items only if relative keys are actually defined in source array.

Usage:

.. code-block:: php

    <?php

    $stack = [
        "ford" => "perfect",
        "marvin" => "android",
        "arthur" => "dent"
    ];

    $replace = [
        "marvin" => "robot",
        "tricia" => "mcmillan"
    ];

    var_dump(\Comodojo\Foundation\Utils\ArrayOps::replaceStrict($stack, $replace));

It returns:

.. code::

    array(3) {
      'ford' =>
      string(7) "perfect"
      'marvin' =>
      string(5) "robot"
      'arthur' =>
      string(4) "dent"
    }

Uid generator
-------------

Class ``\Comodojo\Foundation\Utils\UniqueId`` provides 2 different methods to generate an UID (string).

- ``UniqueId::generate`` generate a random uid, variable length (default 32)

- ``UniqueId::generateCustom`` generate a random uid that includes provided prefix, , variable length (default 32)

Usage example:

.. code-block:: php

    <?php

    var_dump(\Comodojo\Foundation\Utils\UniqueId::generate(40));

    var_dump(\Comodojo\Foundation\Utils\UniqueId::generateCustom('ford', 32));

It returns:

.. code::

    string(40) "0c7687119b3772a69691b838303f33bdb2c00bcd"

    string(32) "ford-47ee5e94f6550d811ab1d007f6f"
