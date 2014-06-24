UPGRADE FROM 1.0 TO 1.1
=======================

Removed `RoutingBundle\Document\Route` and `RedirectRoute` in favor of
`RoutingBundle\Doctrine\Phpcr\Route` resp. `RedirectRoute`.

PHPCR specific configurations moved into

    dynamic:
        persistence:
            phpcr:
                enabled: true
                # the rest is optional, the defaults are
                route_basepath: ~
                manager_name: ~
                content_basepath: ~
                use_sonata_admin: auto

You need to at least set `persistence.phpcr.enabled: true` to have the PHPCR provider loaded.

Dropped redundant unused `routing_repositoryroot` configuration.

Removed the setRouteContent and getRouteContent methods. Use setContent and
getContent instead.