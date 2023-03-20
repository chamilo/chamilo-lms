<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;

class Glide
{
    protected Server $server;
    protected array $filters;

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

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
