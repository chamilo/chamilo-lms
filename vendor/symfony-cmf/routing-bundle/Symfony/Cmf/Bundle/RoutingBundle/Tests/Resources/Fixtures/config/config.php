<?php

$container->loadFromExtension('cmf_routing', array(
    'chain' => array(
        'routers_by_id' => array(
            'cmf_routing.router' => 300,
            'router.default' => 100,
        ),
        'replace_symfony_router' => true,
    ),
    'dynamic' => array(
        'generic_controller' => 'acme_main.controller:mainAction',
        'controllers_by_type' => array(
            'editable' => 'acme_main.some_controller:editableAction',
        ),
        'controllers_by_class' => array(
            'Symfony\Cmf\Bundle\ContentBundle\Document\StaticContent' => 'cmf_content.controller:indexAction',
        ),
        'templates_by_class' => array(
            'Symfony\Cmf\Bundle\ContentBundle\Document\StaticContent' => 'CmfContentBundle:StaticContent:index.html.twig',
        ),
        'persistence' => array(
            'phpcr' => array(
                'route_basepaths' => array(
                    '/cms/routes',
                    '/simple',
                ),
                'content_basepath' => '/cms/content',
                'use_sonata_admin' => 'false',
            ),
        ),
        'locales' => array('en', 'fr'),
        'auto_locale_pattern' => true,
        'match_implicit_locale' => true,
    ),
));
