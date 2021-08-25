Building an xAPI Client
=======================

The xAPI client library ships with a builder class which eases the process of
creating an instance of an ``XApiClient`` class:

```php
use Xabbuh\XApi\Client\XApiClientBuilder;

$builder = new XApiClientBuilder();
$xApiClient = $builder->setBaseUrl('http://example.com/lrs/api')
    ->setVersion('1.0.0')
    ->build();
```

The builder creates a client for the 1.0.1 API version if you don't set a version.

HTTP Basic Authentication
-------------------------

Use the ``setAuth()`` method if access to the LRS resources is protected with
HTTP Basic authentication:

```php
use Xabbuh\XApi\Client\XApiClientBuilder;

$builder = new XApiClientBuilder();
$xApiClient = $builder->setBaseUrl('http://example.com/lrs/api')
    ->setAuth('username', 'password')
    ->build();
```

OAuth1 Authentication
---------------------

Using the ``setOAuthCredentials()`` method, you can configure the client to
access OAuth1 protected resources:

```php
use Xabbuh\XApi\Client\XApiClientBuilder;

$builder = new XApiClientBuilder();
$xApiClient = $builder->setBaseUrl('http://example.com/lrs/api')
    ->setOAuthCredentials('consumer-key', 'consumer-secret', 'token', 'token-secret')
    ->build();
```

Using the APIs
--------------

The Experience API consists of four sub APIs: the statements API, the state API,
the activity profile API and the agent profile API. A client for each of these
APIs can be obtained from the global ``XApiClient`` instance:

```php
$statementsApiClient = $xApiClient->getStatementsApiClient();
$stateApiClient = $xApiClient->getStateApiClient();
$activityProfileApiClient = $xApiClient->getActivityProfileApiClient();
$agentProfileApiClient = $xApiClient->getAgentProfileApiClient();
```

Read the dedicated chapters of the sub APIs to learn how to make use of them:

1. [The Statements API](statements.md)

1. [The State API](state.md)

1. [The Activity Profile API](activity_profile.md)

1. [The Agent profile API](agent_profile.md)
