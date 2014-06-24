<?php

namespace FOS\MessageBundle\Search;

use FOS\MessageBundle\ModelManager\ThreadManagerInterface;
use FOS\MessageBundle\Security\ParticipantProviderInterface;

/**
 * Finds threads of a participant, matching a given query
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class Finder implements FinderInterface
{
    /**
     * The participant provider instance
     *
     * @var ParticipantProviderInterface
     */
    protected $participantProvider;

    /**
     * The thread manager
     *
     * @var ThreadManagerInterface
     */
    protected $threadManager;

    public function __construct(ParticipantProviderInterface $participantProvider, ThreadManagerInterface $threadManager)
    {
        $this->participantProvider = $participantProvider;
        $this->threadManager = $threadManager;
    }

    /**
     * Finds threads of a participant, matching a given query
     *
     * @param Query $query
     * @return array of ThreadInterface
     */
    public function find(Query $query)
    {
        return $this->threadManager->findParticipantThreadsBySearch($this->getAuthenticatedParticipant(), $query->getEscaped());
    }

    /**
     * Finds threads of a participant, matching a given query
     *
     * @param Query $query
     * @return mixed a query builder suitable for pagination
     */
    public function getQueryBuilder(Query $query)
    {
        return $this->threadManager->getParticipantThreadsBySearchQueryBuilder($this->getAuthenticatedParticipant(), $query->getEscaped());
    }

    /**
     * Gets the current authenticated user
     *
     * @return ParticipantInterface
     */
    protected function getAuthenticatedParticipant()
    {
        return $this->participantProvider->getAuthenticatedParticipant();
    }
}
