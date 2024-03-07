Configuration handler
=====================

Classes in ``\Comodojo\Foundation\Base`` are designed to provide easy management of configuration statements across every comodojo lib.

The ``\Comodojo\Foundation\Base\Configuration`` class provides methods to set, update and delete statements using dot notation.

.. note:: Dot notation, as implemented here, is a handy way to navigate a tree of statements. Considering a yaml tree like:

    .. code-block:: yaml

        log:
            enable: true
            name: applog
            providers:
                local:
                    type: StreamHandler
                    level: debug
                    stream: logs/extenderd.log
        cache:
            enable: true
            providers:
                local:
                    type: Filesystem
                    cache_folder: cache

    Statement "log.providers.local.type" points directly to "StreamHandler" value

Using Configuration Class
-------------------------

A base configuration object can be created using standard constructor or static ``Configuration::create`` method. Constructor accepts an optional array of parameters that will be pushed to the properties' stack.

.. code-block:: php

    <?php

    $params = ["this"=>"is","a"=>["config", "statement"]];

    $configuration = new \Comodojo\Foundation\Base\Configuration($params)

    // or, alternatively:
    // $configuration = \Comodojo\Foundation\Base\Configuration::create($params)

Once created, a configuration object offers methods to manage statements:

- ``Configuration::set()``: set (or update) a statement

- ``Configuration::get()``: get value of statement

- ``Configuration::has()``: check if statement is defined

- ``Configuration::delete()``: remove a statement from stack

- ``Configuration::merge()``: merge a package of statements into current stack

Usage example:

.. code-block:: php

    <?php

    $params = ["this"=>"is","a"=>["config", "statement"]];

    $configuration = \Comodojo\Foundation\Base\Configuration::create($params);

    var_dump($configuration->get("a"));

    $configuration->set("that", "value");

    var_dump($configuration->get("that"));

Produces:

.. code::

    array(2) {
      [0] =>
      string(6) "config"
      [1] =>
      string(9) "statement"
    }

    string(5) "value"
