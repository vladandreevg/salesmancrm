Data filtering and validation
=============================

Data filtering
--------------

Class ``\Comodojo\Foundation\Validation\DataFilter`` provides some useful methods to filter data extending (or shortcutting) php funcs.

Included methods are:

- ``filterInteger``: conditional int filter from ($min, $max, $default)

- ``filterPort``: TCP/UDP port filtering

- ``filterBoolean``: boolean filter

Usage example:

.. code-block:: php

    <?php

    $https = 443;
    $invalid_port = 10000000;
    $default = 8080;

    var_dump(\Comodojo\Foundation\Validation\DataFilter::filterPort($https, $default));

    var_dump(\Comodojo\Foundation\Validation\DataFilter::filterPort($invalid_port, $default));

It returns:

.. code::

    int(443)

    int(8080)

Data validation
---------------

Class ``\Comodojo\Foundation\Validation\DataValidation`` provides methods to validate data types, optionally applying a custom filter on value itself.

Validation can be invoked via ``validate`` methods, that accepts input data, data type and filter, or using specific validation methods:

- ``validateString``
- ``validateBoolean``
- ``validateInteger``
- ``validateNumeric``
- ``validateFloat``
- ``validateJson``
- ``validateSerialized``
- ``validateArray``
- ``validateStruct``
- ``validateDatetimeIso8601``
- ``validateBase64``
- ``validateNull``
- ``validateTimestamp``

Usage example:

.. code-block:: php

    <?php

    $http = 80;
    $https = 443;

    $filter = function(int $data) {
        // check if port 80
        return $data === 80;
    };

    var_dump(\Comodojo\Foundation\Validation\DataValidation::validateInteger($http, $filter));

    var_dump(\Comodojo\Foundation\Validation\DataValidation::validateInteger($https, $filter));

It returns:

.. code::

    bool(true)

    bool(false)
