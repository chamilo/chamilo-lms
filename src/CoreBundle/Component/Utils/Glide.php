<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;

/**
 * Class Glide.
 */
class Glide
{
    protected $server;
    protected $filters;

    /**
     * Glide constructor.
     *
     * @param array $config
     * @param array $filters
     */
    public function __construct(array $config, array $filters)
    {
        $this->server = ServerFactory::create(
            [
                'response' => new SymfonyResponseFactory(),
                'source' => $config['source'],
                'cache' => $config['cache'],
            ]
        );
        $this->filters = $filters;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
