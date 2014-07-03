<?php

namespace FOS\MessageBundle\Event;

/**
 * Declares all events thrown in the MessageBundle
 */
final class FOSMessageEvents
{
    /**
     * The POST_SEND event occurs after a message has been sent
     * The event is an instance of FOS\MessageBundle\Event\MessageEvent
     *
     * @var string
     */
    const POST_SEND = 'fos_message.post_send';

    /**
     * The POST_DELETE event occurs after a thread has been marked as deleted
     * The event is an instance of FOS\MessageBundle\Event\ThreadEvent
     *
     * @var string
     */
    const POST_DELETE = 'fos_message.post_delete';

    /**
     * The POST_UNDELETE event occurs after a thread has been marked as undeleted
     * The event is an instance of FOS\MessageBundle\Event\ThreadEvent
     *
     * @var string
     */
    const POST_UNDELETE = 'fos_message.post_undelete';

    /**
     * The POST_READ event occurs after a thread has been marked as read
     * The event is an instance of FOS\MessageBundle\Event\ReadableEvent
     *
     * @var string
     */
    const POST_READ = 'fos_message.post_read';

    /**
     * The POST_UNREAD event occurs after a thread has been unread
     * The event is an instance of FOS\MessageBundle\Event\ReadableEvent
     *
     * @var string
     */
    const POST_UNREAD = 'fos_message.post_unread';
}
