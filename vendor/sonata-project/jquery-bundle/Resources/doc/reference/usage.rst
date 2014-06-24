Usage
=====

Include the JavaScript files in you template file:

.. code-block:: html+jinja

    <script src="{{ asset('bundles/sonatajquery/jquery-1.8.0.js') }}" type="text/javascript"></script>
    
CSS files are also available:

.. code-block:: html+jinja

    <link rel="stylesheet" href="{{ asset('bundles/sonatajquery/themes/flick/jquery-ui-1.8.16.custom.css') }}" type="text/css" media="all" />
     
Check `SonataAdminBundle <https://github.com/sonata-project/SonataAdminBundle/blob/master/Resources/views/standard_layout.html.twig>`_ for an example.
