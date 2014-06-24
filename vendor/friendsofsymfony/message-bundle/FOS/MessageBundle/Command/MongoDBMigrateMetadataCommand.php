<?php

namespace FOS\MessageBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MongoDBMigrateMetadataCommand extends ContainerAwareCommand
{
    /**
     * @var \MongoCollection
     */
    private $messageCollection;

    /**
     * @var \MongoCollection
     */
    private $threadCollection;

    /**
     * @var \MongoCollection
     */
    private $participantCollection;

    /**
     * @var array
     */
    private $updateOptions;

    /**
     * @var \Closure
     */
    private $printStatusCallback;

    /**
     * @see Symfony\Component\Console\Command\Command::isEnabled()
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('doctrine.odm.mongodb')) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * @see Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('fos:message:mongodb:migrate:metadata')
            ->setDescription('Migrates document hash fields to embedded metadata and active/unread arrays')
            ->addArgument('participantClass', InputArgument::REQUIRED, 'Participant class')
            ->addOption('safe', null, InputOption::VALUE_OPTIONAL, 'Mongo update option', false)
            ->addOption('fsync', null, InputOption::VALUE_OPTIONAL, 'Mongo update option', false)
            ->setHelp(<<<'EOT'
The <info>fos:message:mongodb:migrate:metadata</info> command migrates old document hash
fields to a new schema optimized for MongoDB queries. This command requires the
participant class to be provided as its first and only parameter:

  <info>php app/console fos:message:mongodb:migrate:metadata "Acme\Document\User"</info>

The following hash fields will become obsolete after migration:

  <info>*</info> message.isReadByParticipant
  <info>*</info> thread.datesOfLastMessageWrittenByOtherParticipant
  <info>*</info> thread.datesOfLastMessageWrittenByParticipant
  <info>*</info> thread.isDeletedByParticipant

The following new fields will be created:

  <info>*</info> message.metadata <comment>(array of embedded metadata documents)</comment>
  <info>*</info> message.unreadForParticipants <comment>(array of participant ID's)</comment>
  <info>*</info> thread.activeParticipants <comment>(array of participant ID's)</comment>
  <info>*</info> thread.activeRecipients <comment>(array of participant ID's)</comment>
  <info>*</info> thread.activeSenders <comment>(array of participant ID's)</comment>
  <info>*</info> thread.lastMessageDate <comment>(timestamp of the most recent message)</comment>
  <info>*</info> thread.metadata <comment>(array of embedded metadata documents)</comment>

<info>Note:</info> This migration script will not unset any obsolete fields, which will
preserve backwards compatibility. You may manually remove those fields from
message and thread documents at your own discretion.
EOT
            )
        ;
    }

    /**
     * @see Symfony\Bundle\FrameworkBundle\Command\Command::initialize()
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $registry = $this->getContainer()->get('doctrine.odm.mongodb');

        $this->messageCollection = $this->getMongoCollectionForClass($registry, $this->getContainer()->getParameter('fos_message.message_class'));
        $this->threadCollection = $this->getMongoCollectionForClass($registry, $this->getContainer()->getParameter('fos_message.thread_class'));
        $this->participantCollection = $this->getMongoCollectionForClass($registry, $input->getArgument('participantClass'));

        $this->updateOptions = array(
            'multiple' => false,
            'safe' => $input->getOption('safe'),
            'fsync' => $input->getOption('fsync'),
        );

        $this->printStatusCallback = function() {};
        register_tick_function(array($this, 'printStatus'));
    }

    /**
     * @see Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->migrateMessages($output);
        $this->migrateThreads($output);

        $size = memory_get_peak_usage(true);
        $unit = array('b', 'k', 'm', 'g', 't', 'p');
        $output->writeln(sprintf("Peak Memory Usage: <comment>%s</comment>", round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).$unit[$i]));
    }

    /**
     * Migrate message documents
     *
     * @param OutputInterface $output
     */
    private function migrateMessages(OutputInterface $output)
    {
        $cursor = $this->messageCollection->find(
            array('metadata' => array('$exists' => false)),
            array(
                'isReadByParticipant' => 1,
                'isSpam' => 1,
            )
        );
        $cursor->snapshot();

        $numProcessed = 0;

        if (!$numTotal = $cursor->count()) {
            $output->writeln('There are no message documents to migrate.');
            return;
        }

        $this->printStatusCallback = function() use ($output, &$numProcessed, $numTotal) {
            $output->write(sprintf("Processed: <info>%d</info> / Complete: <info>%d%%</info>\r", $numProcessed, round(100 * ($numProcessed / $numTotal))));
        };

        declare(ticks=2500) {
            foreach ($cursor as $message) {
                $this->createMessageMetadata($message);
                $this->createMessageUnreadForParticipants($message);

                $this->messageCollection->update(
                    array('_id' => $message['_id']),
                    array('$set' => array(
                        'metadata' => $message['metadata'],
                        'unreadForParticipants' => $message['unreadForParticipants'],
                    )),
                    $this->updateOptions
                );
                ++$numProcessed;
            }
        }

        $output->write(str_repeat(' ', 28 + ceil(log10($numProcessed))) . "\r");
        $output->writeln(sprintf('Migrated <info>%d</info> message documents.', $numProcessed));
    }

    /**
     * Migrate thread documents
     *
     * @param OutputInterface $output
     */
    private function migrateThreads(OutputInterface $output)
    {
        $cursor = $this->threadCollection->find(
            array('metadata' => array('$exists' => false)),
            array(
                'datesOfLastMessageWrittenByOtherParticipant' => 1,
                'datesOfLastMessageWrittenByParticipant' => 1,
                'isDeletedByParticipant' => 1,
                'isSpam' => 1,
                'messages' => 1,
                'participants' => 1,
            )
        );

        $numProcessed = 0;

        if (!$numTotal = $cursor->count()) {
            $output->writeln('There are no thread documents to migrate.');
            return;
        }

        $this->printStatusCallback = function() use ($output, &$numProcessed, $numTotal) {
            $output->write(sprintf("Processed: <info>%d</info> / Complete: <info>%d%%</info>\r", $numProcessed, round(100 * ($numProcessed / $numTotal))));
        };

        declare(ticks=2500) {
            foreach ($cursor as $thread) {
                $this->createThreadMetadata($thread);
                $this->createThreadLastMessageDate($thread);
                $this->createThreadActiveParticipantArrays($thread);

                $this->threadCollection->update(
                    array('_id' => $thread['_id']),
                    array('$set' => array(
                        'activeParticipants' => $thread['activeParticipants'],
                        'activeRecipients' => $thread['activeRecipients'],
                        'activeSenders' => $thread['activeSenders'],
                        'lastMessageDate' => $thread['lastMessageDate'],
                        'metadata' => $thread['metadata'],
                    )),
                    $this->updateOptions
                );
                ++$numProcessed;
            }
        }

        $output->write(str_repeat(' ', 28 + ceil(log10($numProcessed))) . "\r");
        $output->writeln(sprintf('Migrated <info>%d</info> thread documents.', $numProcessed));
    }

    /**
     * Sets the metadata array on the message.
     *
     * By default, Mongo will not include "$db" when creating the participant
     * reference. We'll add that manually to be consistent with Doctrine.
     *
     * @param array &$message
     */
    private function createMessageMetadata(array &$message)
    {
        $metadata = array();

        foreach ($message['isReadByParticipant'] as $participantId => $isRead) {
            $metadata[] = array(
                'isRead' => $isRead,
                'participant' => $this->participantCollection->createDBRef(array('_id' => new \MongoId($participantId))) + array('$db' => (string) $this->participantCollection->db),
            );
        }

        $message['metadata'] = $metadata;
    }

    /**
     * Sets the unreadForParticipants array on the message.
     *
     * @see FOS\MessageBundle\Document\Message::doEnsureUnreadForParticipantsArray()
     * @param array &$message
     */
    private function createMessageUnreadForParticipants(array &$message)
    {
        $unreadForParticipants = array();

        if (!$message['isSpam']) {
            foreach ($message['metadata'] as $metadata) {
                if (!$metadata['isRead']) {
                    $unreadForParticipants[] = (string) $metadata['participant']['$id'];
                }
            }
        }

        $message['unreadForParticipants'] = $unreadForParticipants;
    }

    /**
     * Sets the metadata array on the thread.
     *
     * By default, Mongo will not include "$db" when creating the participant
     * reference. We'll add that manually to be consistent with Doctrine.
     *
     * @param array &$thread
     */
    private function createThreadMetadata(array &$thread)
    {
        $metadata = array();

        $participantIds = array_keys($thread['datesOfLastMessageWrittenByOtherParticipant'] + $thread['datesOfLastMessageWrittenByParticipant'] + $thread['isDeletedByParticipant']);

        foreach ($participantIds as $participantId) {
            $meta = array(
                'isDeleted' => false,
                'participant' => $this->participantCollection->createDBRef(array('_id' => new \MongoId($participantId))) + array('$db' => (string) $this->participantCollection->db),
            );

            if (isset($thread['isDeletedByParticipant'][$participantId])) {
                $meta['isDeleted'] = $thread['isDeletedByParticipant'][$participantId];
            }

            if (isset($thread['datesOfLastMessageWrittenByOtherParticipant'][$participantId])) {
                $meta['lastMessageDate'] = new \MongoDate($thread['datesOfLastMessageWrittenByOtherParticipant'][$participantId]);
            }

            if (isset($thread['datesOfLastMessageWrittenByParticipant'][$participantId])) {
                $meta['lastParticipantMessageDate'] = new \MongoDate($thread['datesOfLastMessageWrittenByParticipant'][$participantId]);
            }

            $metadata[] = $meta;
        }

        $thread['metadata'] = $metadata;
    }

    /**
     * Sets the lastMessageDate timestamp on the thread.
     *
     * @param array &$thread
     */
    private function createThreadLastMessageDate(array &$thread)
    {
        $lastMessageRef = end($thread['messages']);

        if (false !== $lastMessageRef) {
            $lastMessage = $this->messageCollection->findOne(
                array('_id' => $lastMessageRef['$id']),
                array('createdAt' => 1)
            );
        }

        $thread['lastMessageDate'] = isset($lastMessage['createdAt']) ? $lastMessage['createdAt'] : null;
    }

    /**
     * Sets the active participant arrays on the thread.
     *
     * @see FOS\MessageBundle\Document\Thread::doEnsureActiveParticipantArrays()
     * @param array $thread
     */
    private function createThreadActiveParticipantArrays(array &$thread)
    {
        $activeParticipants = array();
        $activeRecipients = array();
        $activeSenders = array();

        foreach ($thread['participants'] as $participantRef) {
            foreach ($thread['metadata'] as $metadata) {
                if ($participantRef['$id'] == $metadata['participant']['$id'] && $metadata['isDeleted']) {
                    continue 2;
                }
            }

            $participantIsActiveRecipient = $participantIsActiveSender = false;

            foreach ($thread['messages'] as $messageRef) {
                $message = $this->threadCollection->getDBRef($messageRef);

                if (null === $message) {
                    throw new \UnexpectedValueException(sprintf('Message "%s" not found for thread "%s"', $messageRef['$id'], $thread['_id']));
                }

                if (!isset($message['sender']['$id'])) {
                    throw new \UnexpectedValueException(sprintf('Sender reference not found for message "%s"', $messageRef['$id']));
                }

                if ($participantRef['$id'] == $message['sender']['$id']) {
                    $participantIsActiveSender = true;
                } elseif (!$thread['isSpam']) {
                    $participantIsActiveRecipient = true;
                }

                if ($participantIsActiveRecipient && $participantIsActiveSender) {
                    break;
                }
            }

            if ($participantIsActiveSender) {
                $activeSenders[] = (string) $participantRef['$id'];
            }

            if ($participantIsActiveRecipient) {
                $activeRecipients[] = (string) $participantRef['$id'];
            }

            if ($participantIsActiveSender || $participantIsActiveRecipient) {
                $activeParticipants[] = (string) $participantRef['$id'];
            }
        }

        $thread['activeParticipants'] = $activeParticipants;
        $thread['activeRecipients'] = $activeRecipients;
        $thread['activeSenders'] = $activeSenders;
    }

    /**
     * Get the MongoCollection for the given class
     *
     * @param ManagerRegistry $registry
     * @param string          $class
     * @return \MongoCollection
     * @throws \RuntimeException if the class has no DocumentManager
     */
    private function getMongoCollectionForClass(ManagerRegistry $registry, $class)
    {
        if (!$dm = $registry->getManagerForClass($class)) {
            throw new \RuntimeException(sprintf('There is no DocumentManager for class "%s"', $class));
        }

        return $dm->getDocumentCollection($class)->getMongoCollection();
    }

    /**
     * Invokes the print status callback
     *
     * Since unregister_tick_function() does not support anonymous functions, it
     * is easier to register one method (this) and invoke a dynamic callback.
     */
    public function printStatus()
    {
        call_user_func($this->printStatusCallback);
    }
}
