<?php
/**
 * MessageListEvent.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\ThemeBundle\Event;


use Chamilo\ThemeBundle\Model\MessageInterface;

/**
 * The MessageListEvent should be used with the {@link ThemeEvents::THEME_MESSAGES}
 * in order to collect all {@link MessageInterface} objects that should be rendered in the messages section.
 *
 */
class MessageListEvent extends ThemeEvent
{

    /**
     * Stores the list of messages
     * @var array
     */
    protected $messages = array();

    /**
     * Stores the total amount
     * @var int
     */
    protected $totalMessages = 0;

    /**
     * Returns the message list
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Pushes the given message to the list of messages.
     *
     * @param MessageInterface $messageInterface
     *
     * @return $this
     */
    public function addMessage(MessageInterface $messageInterface)
    {

        $this->messages[] = $messageInterface;

        return $this;

    }

    /**
     * Returns the message count
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->totalMessages == 0 ? sizeof($this->messages) : $this->totalMessages;
    }

}
