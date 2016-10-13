.. index::
    single: Cache
    single: Behavior
    single: Backend

Cache
=====

``BlockBundle`` integrates the CacheBundle to handle block cache. The cache unit stored in the backend is a Response object from the ``HttpFoundation`` Component.
Why a `Response` object ? It is a simple element, which contains the data (the body) and some metadata (the headers).
As the block returns a `Response` object, it is possible to send it to the client, this use case can be quite useful for some cache backends (esi, ssi or js).

Cache Behavior
~~~~~~~~~~~~~~

The ``BlockBundle`` assumes that a block can be cached by default, so if a cache backend is configured, the block response will be cached.
The default `TTL` is `86400` seconds. Now, there are many ways to control this behavior:

* set a ``TTL`` setting inside the block, so if ``ttl`` equals 0, then no cache will be available for the block and its parents,
* set a ``use_cache`` setting to ``false`` or ``true``, if the variable is set to ``false`` then no cache will be available for the block and its parents,
* no cache backend by default! By default, there is no cache backend setup, you should focus on raw performance before adding cache layers.

If you are extending the ``BaseBlockService``, you can use the method ``renderPrivateResponse`` to return a private `Response`.

.. code-block:: php

    <?php

    // Private response as the block response depends on the connected user
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $user = false;
        if ($this->securityContext->getToken()) {
            $user = $this->securityContext->getToken()->getUser();
        }

        if (!$user instanceof UserInterface) {
            $user = false;
        }

        return $this->renderPrivateResponse($blockContext->getTemplate(), array(
            'user'    => $user,
            'block'   => $blockContext->getBlock(),
            'context' => $blockContext
        ));
    }

or you can use the `Response` object:

.. code-block:: php

    <?php

    // Private response as the block response depends on the connected user
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $user = false;
        if ($this->securityContext->getToken()) {
            $user = $this->securityContext->getToken()->getUser();
        }

        if (!$user instanceof UserInterface) {
            $user = false;
        }

        return Response::create(sprintf("your name is %s", $user->getUsername()))->setTtl(0)->setPrivate();
    }

Block TTL computation
~~~~~~~~~~~~~~~~~~~~~

The ``BlockBundle`` uses the `TTL` value from the `Response` object to compute the final `TTL` value. As blocks can have children, the smallest `TTL` need to be used in parent block responses.
This job is done by the ``BlockRenderer`` class, this service stores a state with the last response and compares the TTL with the current response.
The state is reset when the block does not have any parent.

The cache mechanism will use the `TTL` to set a valid value when the response is stored into the cache backend.

.. note::

    If a `TTL` is set into a block container, the `TTL` value is not applied to the final Response object sent to the client.
    This can be done by using a different mechanism.

Final Response TTL computation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``BlockRendered`` stores a global state for the smallest TTL available, there is another service used to store the smallest
TTL for the page: ``HttpCacheHandler``. Why two services? This has been done to add an extra layer of control.

The ``HttpCacheHandler::updateMetadata`` is called by the templating helper when the response is retrieved, then an event listener is registered to alter the final Response.

The service can be configured using the ``http_cache_handler`` key.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml

        sonata_block:
            http_cache:
                handler: sonata.block.cache.handler.noop    # no cache alteration
                handler: sonata.block.cache.handler.default # default value
                listener: true                              # default to true, register or not the event listener to alter the final response

Cache Backends
~~~~~~~~~~~~~~

* ``sonata.cache.mongo``: use mongodb to store cache element. This is a nice backend as you can remove a cache element by
  only one value. (remove all block where profile.media.id == 3 is used.)
* ``sonata.cache.memcached``: use memcached as a backend, shared across multiple hosts
* ``sonata.cache.apc``: use apc from PHP runtime, cannot be shared across multiple hosts, and it is not suitable to store high volume of data
* ``sonata.cache.esi``: use an ESI compatible backend to store the cache, like Varnish
* ``sonata.cache.ssi``: use an SSI compatible backend to store the cache, like Apache or Nginx

Cache configuration
~~~~~~~~~~~~~~~~~~~

The configuration is defined per `block service`, so if you want to use `memcached` for a block type ``sonata.page.block.container`` then use the following configuration:

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml

        sonata_block:
            sonata.page.block.container:
                cache: sonata.cache.memcached

Also, make sure the memcached backend is configured in the ``sonata_cache`` definition:

.. configuration-block::

    .. code-block:: yaml

        sonata_cache:
            caches:
                memcached:
                    prefix: test     # prefix to ensure there is no clash between instances
                    servers:
                        - {host: 127.0.0.1, port: 11211, weight: 0}
