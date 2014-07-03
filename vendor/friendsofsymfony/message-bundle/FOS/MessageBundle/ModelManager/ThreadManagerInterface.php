<?php

namespace FOS\MessageBundle\ModelManager;

use FOS\MessageBundle\Model\ParticipantInterface;
use FOS\MessageBundle\Model\ThreadInterface;

/**
 * Interface to be implemented by comment thread managers. This adds an additional level
 * of abstraction between your application, and the actual repository.
 *
 * All changes to comment threads should happen through this interface.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ThreadManagerInterface extends ReadableManagerInterface
{
    /**
     * Finds a thread by its ID
     *
     * @return ThreadInterface or null
     */
    function findThreadById($id);

    /**
     * Finds not deleted threads for a participant,
     * containing at least one message not written by this participant,
     * ordered by last message not written by this participant in reverse order.
     * In one word: an inbox.
     *
     * @param ParticipantInterface $participant
     * @return Builder a query builder suitable for pagination
     */
    function getParticipantInboxThreadsQueryBuilder(ParticipantInterface $participant);

    /**
     * Finds not deleted threads for a participant,
     * containing at least one message not written by this participant,
     * ordered by last message not written by this participant in reverse order.
     * In one word: an inbox.
     *
     * @param ParticipantInterface $participant
     * @return array of ThreadInterface
     */
    function findParticipantInboxThreads(ParticipantInterface $participant);

    /**
     * Finds not deleted threads from a participant,
     * containing at least one message written by this participant,
     * ordered by last message written by this participant in reverse order.
     * In one word: an sentbox.
     *
     * @param ParticipantInterface $participant
     * @return Builder a query builder suitable for pagination
     */
    function getParticipantSentThreadsQueryBuilder(ParticipantInterface $participant);

    /**
     * Finds not deleted threads from a participant,
     * containing at least one message written by this participant,
     * ordered by last message written by this participant in reverse order.
     * In one word: an sentbox.
     *
     * @param ParticipantInterface $participant
     * @return array of ThreadInterface
     */
    function findParticipantSentThreads(ParticipantInterface $participant);

    /**
     * Finds deleted threads from a participant,
     * ordered by last message date
     *
     * @param ParticipantInterface $participant
     * @return Builder a query builder suitable for pagination
     */
    function getParticipantDeletedThreadsQueryBuilder(ParticipantInterface $participant);

    /**
     * Finds deleted threads from a participant,
     * ordered by last message date
     *
     * @param ParticipantInterface $participant
     * @return ThreadInterface[]
     */
    function findParticipantDeletedThreads(ParticipantInterface $participant);

    /**
     * Finds not deleted threads for a participant,
     * matching the given search term
     * ordered by last message not written by this participant in reverse order.
     *
     * @param ParticipantInterface $participant
     * @param string $search
     * @return Builder a query builder suitable for pagination
     */
    function getParticipantThreadsBySearchQueryBuilder(ParticipantInterface $participant, $search);

    /**
     * Finds not deleted threads for a participant,
     * matching the given search term
     * ordered by last message not written by this participant in reverse order.
     *
     * @param ParticipantInterface $participant
     * @param string $search
     * @return array of ThreadInterface
     */
    function findParticipantThreadsBySearch(ParticipantInterface $participant, $search);

    /**
     * Gets threads created by a participant
     *
     * @param ParticipantInterface $participant
     * @return array of ThreadInterface
     */
    function findThreadsCreatedBy(ParticipantInterface $participant);

    /**
     * Creates an empty comment thread instance
     *
     * @return ThreadInterface
     */
    function createThread();

    /**
     * Saves a thread
     *
     * @param ThreadInterface $thread
     * @param Boolean $andFlush Whether to flush the changes (default true)
     */
    function saveThread(ThreadInterface $thread, $andFlush = true);

    /**
     * Deletes a thread
     * This is not participant deletion but real deletion
     *
     * @param ThreadInterface $thread the thread to delete
     */
    function deleteThread(ThreadInterface $thread);
}
