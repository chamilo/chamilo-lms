<?php

namespace FOS\MessageBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\MessageBundle\Provider\ProviderInterface;

class MessageController extends ContainerAware
{
    /**
     * Displays the authenticated participant inbox
     *
     * @return Response
     */
    public function inboxAction()
    {
        $threads = $this->getProvider()->getInboxThreads();

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:inbox.html.twig', array(
            'threads' => $threads
        ));
    }

    /**
     * Displays the authenticated participant messages sent
     *
     * @return Response
     */
    public function sentAction()
    {
        $threads = $this->getProvider()->getSentThreads();

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:sent.html.twig', array(
            'threads' => $threads
        ));
    }

    /**
     * Displays the authenticated participant deleted threads
     *
     * @return Response
     */
    public function deletedAction()
    {
        $threads = $this->getProvider()->getDeletedThreads();

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:deleted.html.twig', array(
            'threads' => $threads
        ));
    }

    /**
     * Displays a thread, also allows to reply to it
     *
     * @param string $threadId the thread id
     * 
     * @return Response
     */
    public function threadAction($threadId)
    {
        $thread = $this->getProvider()->getThread($threadId);
        $form = $this->container->get('fos_message.reply_form.factory')->create($thread);
        $formHandler = $this->container->get('fos_message.reply_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                'threadId' => $message->getThread()->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:thread.html.twig', array(
            'form' => $form->createView(),
            'thread' => $thread
        ));
    }

    /**
     * Create a new message thread
     *
     * @return Response
     */
    public function newThreadAction()
    {
        $form = $this->container->get('fos_message.new_thread_form.factory')->create();
        $formHandler = $this->container->get('fos_message.new_thread_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                'threadId' => $message->getThread()->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:newThread.html.twig', array(
            'form' => $form->createView(),
            'data' => $form->getData()
        ));
    }

    /**
     * Deletes a thread
     * 
     * @param string $threadId the thread id
     * 
     * @return RedirectResponse
     */
    public function deleteAction($threadId)
    {
        $thread = $this->getProvider()->getThread($threadId);
        $this->container->get('fos_message.deleter')->markAsDeleted($thread);
        $this->container->get('fos_message.thread_manager')->saveThread($thread);

        return new RedirectResponse($this->container->get('router')->generate('fos_message_inbox'));
    }
    
    /**
     * Undeletes a thread
     * 
     * @param string $threadId
     * 
     * @return RedirectResponse
     */
    public function undeleteAction($threadId)
    {
        $thread = $this->getProvider()->getThread($threadId);
        $this->container->get('fos_message.deleter')->markAsUndeleted($thread);
        $this->container->get('fos_message.thread_manager')->saveThread($thread);

        return new RedirectResponse($this->container->get('router')->generate('fos_message_inbox'));
    }

    /**
     * Searches for messages in the inbox and sentbox
     *
     * @return Response
     */
    public function searchAction()
    {
        $query = $this->container->get('fos_message.search_query_factory')->createFromRequest();
        $threads = $this->container->get('fos_message.search_finder')->find($query);

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:search.html.twig', array(
            'query' => $query,
            'threads' => $threads
        ));
    }

    /**
     * Gets the provider service
     *
     * @return ProviderInterface
     */
    protected function getProvider()
    {
        return $this->container->get('fos_message.provider');
    }
}
