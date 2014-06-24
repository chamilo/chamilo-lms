Configuration Reference
=======================

All configuration options are listed below::

```yaml
# app/config/config.yml

fos_message:
    db_driver:              orm
    thread_class:           Acme\MessageBundle\Entity\Thread
    message_class:          Acme\MessageBundle\Entity\Message
    message_manager:        fos_message.message_manager         # See ModelManager\MessageManagerInterface
    thread_manager:         fos_message.thread_manager          # See ModelManager\ThreadManagerInterface
    sender:                 fos_message.sender                  # See Sender\SenderInterface
    composer:               fos_message.composer                # See Composer\ComposerInterface
    provider:               fos_message.provider                # See Provider\ProviderInterface
    participant_provider:   fos_message.participant_provider    # See Security\ParticipantProviderInterface
    authorizer:             fos_message.authorizer              # See Security\AuthorizerInterface
    message_reader:         fos_message.message_reader          # See Reader\ReaderInterface
    thread_reader:          fos_message.thread_reader           # See Reader\ReaderInterface
    deleter:                fos_message.deleter                 # See Deleter\DeleterInterface
    spam_detector:          fos_message.noop_spam_detector      # See SpamDetection\SpamDetectorInterface
    twig_extension:         fos_message.twig_extension          # See Twig\Extension\MessageExtension
    user_transformer:       fos_user.user_transformer           # See Symfony\Component\Form\DataTransformerInterface
    search:
        finder:             fos_message.search_finder           # See Finder\FinderInterface
        query_factory:      fos_message.search_query_factory    # See Finder\QueryFactoryInterface
        query_parameter:    'q'                                     # Request query parameter containing the term
    new_thread_form:
        factory:            fos_message.new_thread_form.factory # See FormFactory\NewThreadMessageFormFactory
        type:               fos_message.new_thread_form.type    # See FormType\NewThreadMessageFormType
        handler:            fos_message.new_thread_form.handler # See FormHandler\NewThreadMessageFormHandler
        name:               message
    reply_form:
        factory:            fos_message.reply_form.factory      # See FormFactory\ReplyMessageFormFactory
        type:               fos_message.reply_form.type         # See FormType\ReplyMessageFormType
        handler:            fos_message.reply_form.handler      # See FormHandler\ReplyMessageFormHandler
        name:               message
```
