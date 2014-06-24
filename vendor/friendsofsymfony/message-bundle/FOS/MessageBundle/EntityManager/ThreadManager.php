<?php

namespace FOS\MessageBundle\EntityManager;

use FOS\MessageBundle\ModelManager\ThreadManager as BaseThreadManager;
use Doctrine\ORM\EntityManager;
use FOS\MessageBundle\Model\ThreadInterface;
use FOS\MessageBundle\Model\ReadableInterface;
use FOS\MessageBundle\Model\ParticipantInterface;
use Doctrine\ORM\Query\Builder;

/**
 * Default ORM ThreadManager.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class ThreadManager extends BaseThreadManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DocumentRepository
     */
    protected $repository;

    /**
     * The model class
     *
     * @var string
     */
    protected $class;

    /**
     * The model class
     *
     * @var string
     */
    protected $metaClass;

    /**
     * The message manager, required to mark
     * the messages of a thread as read/unread
     *
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * Constructor.
     *
     * @param EntityManager     $em
     * @param string            $class
     * @param string            $metaClass
     * @param MessageManager    $messageManager
     */
    public function __construct(EntityManager $em, $class, $metaClass, MessageManager $messageManager)
    {
        $this->em             = $em;
        $this->repository     = $em->getRepository($class);
        $this->class          = $em->getClassMetadata($class)->name;
        $this->metaClass      = $em->getClassMetadata($metaClass)->name;
        $this->messageManager = $messageManager;
    }

    /**
     * Finds a thread by its ID
     *
     * @return ThreadInterface or null
     */
    public function findThreadById($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Finds not deleted threads for a participant,
     * containing at least one message not written by this participant,
     * ordered by last message not written by this participant in reverse order.
     * In one word: an inbox.
     *
     * @param ParticipantInterface $participant
     * @return Builder a query builder suitable for pagination
     */
    public function getParticipantInboxThreadsQueryBuilder(ParticipantInterface $participant)
    {
        return $this->repository->createQueryBuilder('t')
            ->innerJoin('t.metadata', 'tm')
            ->innerJoin('tm.participant', 'p')

            // the participant is in the thread participants
            ->andWhere('p.id = :user_id')
            ->setParameter('user_id', $participant->getId())

            // the thread does not contain spam or flood
            ->andWhere('t.isSpam = :isSpam')
            ->setParameter('isSpam', false, \PDO::PARAM_BOOL)

            // the thread is not deleted by this participant
            ->andWhere('tm.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false, \PDO::PARAM_BOOL)

            // there is at least one message written by an other participant
            ->andWhere('tm.lastMessageDate IS NOT NULL')

            // sort by date of last message written by an other participant
            ->orderBy('tm.lastMessageDate', 'DESC')
        ;
    }

    /**
     * Finds not deleted threads for a participant,
     * containing at least one message not written by this participant,
     * ordered by last message not written by this participant in reverse order.
     * In one word: an inbox.
     *
     * @param ParticipantInterface $participant
     * @return array of ThreadInterface
     */
    public function findParticipantInboxThreads(ParticipantInterface $participant)
    {
        return $this->getParticipantInboxThreadsQueryBuilder($participant)
            ->getQuery()
            ->execute();
    }

    /**
     * Finds not deleted threads from a participant,
     * containing at least one message written by this participant,
     * ordered by last message written by this participant in reverse order.
     * In one word: an sentbox.
     *
     * @param ParticipantInterface $participant
     * @return Builder a query builder suitable for pagination
     */
    public function getParticipantSentThreadsQueryBuilder(ParticipantInterface $participant)
    {
        return $this->repository->createQueryBuilder('t')
            ->innerJoin('t.metadata', 'tm')
            ->innerJoin('tm.participant', 'p')

            // the participant is in the thread participants
            ->andWhere('p.id = :user_id')
            ->setParameter('user_id', $participant->getId())

            // the thread does not contain spam or flood
            ->andWhere('t.isSpam = :isSpam')
            ->setParameter('isSpam', false, \PDO::PARAM_BOOL)

            // the thread is not deleted by this participant
            ->andWhere('tm.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false, \PDO::PARAM_BOOL)

            // there is at least one message written by this participant
            ->andWhere('tm.lastParticipantMessageDate IS NOT NULL')

            // sort by date of last message written by this participant
            ->orderBy('tm.lastParticipantMessageDate', 'DESC')
        ;
    }

    /**
     * Finds not deleted threads from a participant,
     * containing at least one message written by this participant,
     * ordered by last message written by this participant in reverse order.
     * In one word: an sentbox.
     *
     * @param ParticipantInterface $participant
     * @return array of ThreadInterface
     */
    public function findParticipantSentThreads(ParticipantInterface $participant)
    {
        return $this->getParticipantSentThreadsQueryBuilder($participant)
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function getParticipantDeletedThreadsQueryBuilder(ParticipantInterface $participant)
    {
        return $this->repository->createQueryBuilder('t')
            ->innerJoin('t.metadata', 'tm')
            ->innerJoin('tm.participant', 'p')

            // the participant is in the thread participants
            ->andWhere('p.id = :user_id')
            ->setParameter('user_id', $participant->getId())

            // the thread is deleted by this participant
            ->andWhere('tm.isDeleted = :isDeleted')
            ->setParameter('isDeleted', true, \PDO::PARAM_BOOL)

            // sort by date of last message
            ->orderBy('tm.lastMessageDate', 'DESC')
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function findParticipantDeletedThreads(ParticipantInterface $participant)
    {
        return $this->getParticipantDeletedThreadsQueryBuilder($participant)
            ->getQuery()
            ->execute();
    }

    /**
     * Finds not deleted threads for a participant,
     * matching the given search term
     * ordered by last message not written by this participant in reverse order.
     *
     * @param ParticipantInterface $participant
     * @param string $search
     * @return Builder a query builder suitable for pagination
     */
    public function getParticipantThreadsBySearchQueryBuilder(ParticipantInterface $participant, $search)
    {
        // remove all non-word chars
        $search = preg_replace('/[^\w]/', ' ', trim($search));
        // build a regex like (term1|term2)
        $regex = sprintf('/(%s)/', implode('|', explode(' ', $search)));

        throw new \Exception('not yet implemented');
    }

    /**
     * Finds not deleted threads for a participant,
     * matching the given search term
     * ordered by last message not written by this participant in reverse order.
     *
     * @param ParticipantInterface $participant
     * @param string $search
     * @return array of ThreadInterface
     */
    public function findParticipantThreadsBySearch(participantinterface $participant, $search)
    {
        return $this->getParticipantThreadsBySearchQueryBuilder($participant, $search)
            ->getQuery()
            ->execute();
    }

    /**
     * Gets threads created by a participant
     *
     * @param ParticipantInterface $participant
     * @return array of ThreadInterface
     */
    public function findThreadsCreatedBy(ParticipantInterface $participant)
    {
        return $this->repository->createQueryBuilder('t')
            ->innerJoin('t.createdBy', 'p')

            ->where('p.id = :participant_id')
            ->setParameter('participant_id', $participant->getId())

            ->getQuery()
            ->execute();
    }

    /**
     * Marks the readable as read by this participant
     * Must be applied directly to the storage,
     * without modifying the readable state.
     * We want to show the unread readables on the page,
     * as well as marking the as read.
     *
     * @param ReadableInterface $readable
     * @param ParticipantInterface $participant
     */
    public function markAsReadByParticipant(ReadableInterface $readable, ParticipantInterface $participant)
    {
        return $this->messageManager->markIsReadByThreadAndParticipant($readable, $participant, true);
    }

    /**
     * Marks the readable as unread by this participant
     *
     * @param ReadableInterface $readable
     * @param ParticipantInterface $participant
     */
    public function markAsUnreadByParticipant(ReadableInterface $readable, ParticipantInterface $participant)
    {
        return $this->messageManager->markIsReadByThreadAndParticipant($readable, $participant, false);
    }

    /**
     * Saves a thread
     *
     * @param ThreadInterface $thread
     * @param Boolean $andFlush Whether to flush the changes (default true)
     */
    public function saveThread(ThreadInterface $thread, $andFlush = true)
    {
        $this->denormalize($thread);
        $this->em->persist($thread);
        if ($andFlush) {
            $this->em->flush();
        }
    }

    /**
     * Deletes a thread
     *
     * @param ThreadInterface $thread the thread to delete
     */
    public function deleteThread(ThreadInterface $thread)
    {
        $this->em->remove($thread);
        $this->em->flush();
    }

    /**
     * Returns the fully qualified comment thread class name
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * DENORMALIZATION
     *
     * All following methods are relative to denormalization
     */

    /**
     * Performs denormalization tricks
     */
    protected function denormalize(ThreadInterface $thread)
    {
        $this->doMetadata($thread);
        $this->doCreatedByAndAt($thread);
        $this->doDatesOfLastMessageWrittenByOtherParticipant($thread);
    }

    /**
     * Ensures that the thread metadata are up to date
     */
    protected function doMetadata(ThreadInterface $thread)
    {
        // Participants
        foreach ($thread->getParticipants() as $participant) {
            $meta = $thread->getMetadataForParticipant($participant);
            if (!$meta) {
                $meta = $this->createThreadMetadata();
                $meta->setParticipant($participant);

                $thread->addMetadata($meta);
            }
        }

        // Messages
        foreach ($thread->getMessages() as $message) {
            $meta = $thread->getMetadataForParticipant($message->getSender());
            if (!$meta) {
                $meta = $this->createThreadMetadata();
                $meta->setParticipant($message->getSender());
                $thread->addMetadata($meta);
            }

            $meta->setLastParticipantMessageDate($message->getCreatedAt());
        }
    }

    /**
     * Ensures that the createdBy & createdAt properties are set
     */
    protected function doCreatedByAndAt(ThreadInterface $thread)
    {
        if (!($message = $thread->getFirstMessage())) {
            return;
        }

        if (!$thread->getCreatedAt()) {
            $thread->setCreatedAt($message->getCreatedAt());
        }

        if (!$thread->getCreatedBy()) {
            $thread->setCreatedBy($message->getSender());
        }
    }

    /**
     * Update the dates of last message written by other participants
     */
    protected function doDatesOfLastMessageWrittenByOtherParticipant(ThreadInterface $thread)
    {
        foreach ($thread->getAllMetadata() as $meta) {
            $participantId = $meta->getParticipant()->getId();
            $timestamp = 0;

            foreach ($thread->getMessages() as $message) {
                if ($participantId != $message->getSender()->getId()) {
                    $timestamp = max($timestamp, $message->getTimestamp());
                }
            }
            if ($timestamp) {
                $date = new \DateTime();
                $date->setTimestamp($timestamp);
                $meta->setLastMessageDate($date);
            }
        }
    }

    protected function createThreadMetadata()
    {
        return new $this->metaClass();
    }
}
