Secure Random Number Generator
------------------------------

.. versionadded :: 1.2
    The Secure Random Number Generator was added.

Introduction
------------
In almost all applications, you need to generate random numbers that cannot be
guessed by a possible attacker. Unfortunately, PHP does not provide capabilities
to do this consistently on all platforms. 

This bundle ships with several seed provider implementations, and will choose
the best provider possible depending on your PHP setup.

Configuration
-------------
You can enable the "security.secure_random" service with the following config:

.. configuration-block ::

    .. code-block :: yaml

        jms_security_extra:
            util:
                secure_random: ~
                
    .. code-block :: xml
    
        <jms-security-extra>
            <util>
                <secure-random />
            </util>
        </jms-security-extra>

Also make sure to run ``php app/console doctrine:schema:update``, or create an
equivalent migration to import the seed table.

Usage
-----
The generator is made available with the service id ``security.secure_random``.

.. code-block :: php

    <?php
    
    $generator = $this->container->get('security.secure_random');
    $bytes = $generator->nextBytes(16); // 128-bit random number

``$bytes`` in the example above contains binary data. You can then convert this
data to a format that you can print out using one of these functions:

.. code-block :: php

    <?php

    $base64Encoded = base64_encode($bytes); // number in base 64
    $hexEncoded = bin2hex($bytes); // number in base 16
    $decEncoded = bindec($bytes); // number in base 10