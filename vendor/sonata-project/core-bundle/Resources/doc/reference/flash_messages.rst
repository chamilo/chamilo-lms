Flash Messages
==============

The bundle comes with a ``FlashManager`` to handle some *session flash messages types* that you can specify in the configuration
to be returned as a ``success``, ``warning`` or ``error`` type (or even all your custom types you want to add).

Additionally, you can also add a ``css_class`` section for each flash messages that will be displayed on rendering.

Configuration
^^^^^^^^^^^^^

.. code-block:: yaml

    sonata_core:
        flashmessage:
            success:
                types:
                    - { type: my_custom_bundle_success, domain: MyCustomBundle }
                    - { type: my_other_bundle_success, domain: MyOtherBundle }
            warning:
                types:
                    - { type: my_custom_bundle_warning, domain: MyCustomBundle }
                    - { type: my_other_bundle_warning } # if nothing is specified, sets SonataCoreBundle by default
            error:
                css_class: danger # optionally, a CSS class can be defined
                types:
                    - { type: my_custom_bundle, domain: MyCustomBundle }
            custom_type: # You can add custom types too
                types:
                    - { type: custom_bundle_type, domain: MyCustomBundle }

You can specify multiple *flash messages types* you want to manage here.

How-to use
^^^^^^^^^^

To use this feature in your PHP classes/controllers, you can use for example:

.. code-block:: php

    <?php

    $this->get('sonata.core.flashmessage.manager')
    $messages = $flashManager->get('success');

To use this feature in your templates, simply include the following template (with an optional domain parameter):

.. code-block:: twig

    {% include 'SonataCoreBundle:FlashMessage:render.html.twig' %}

Please note that if necessary, you can also specify a translation domain to override configuration here:

.. code-block:: twig

    {% include 'SonataCoreBundle:FlashMessage:render.html.twig' with {domain: 'MyCustomBundle'} %}