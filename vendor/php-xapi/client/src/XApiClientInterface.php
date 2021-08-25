<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client;

/**
 * An Experience API client.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface XApiClientInterface
{
    /**
     * Returns an API client to access the statements API of an xAPI based LRS.
     *
     * @return \Xabbuh\XApi\Client\Api\StatementsApiClientInterface The API client
     */
    public function getStatementsApiClient();

    /**
     * Returns an API client to access the state API of an xAPI based LRS.
     *
     * @return \Xabbuh\XApi\Client\Api\StateApiClientInterface The API client
     */
    public function getStateApiClient();

    /**
     * Returns an API client to access the activity profile API of an xAPI based
     * LRS.
     *
     * @return \Xabbuh\XApi\Client\Api\ActivityProfileApiClientInterface The API client
     */
    public function getActivityProfileApiClient();

    /**
     * Returns an API client to access the agent profile API of an xAPI based
     * LRS.
     *
     * @return \Xabbuh\XApi\Client\Api\AgentProfileApiClientInterface The API client
     */
    public function getAgentProfileApiClient();
}
