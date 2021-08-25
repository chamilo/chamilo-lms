UPGRADE
=======

Upgrading from 0.4 to 0.5
-------------------------

* **CAUTION**: This release drops support for PHP 5.3 due to the introduced
  dependency on `php-http/httplug` (see below).

* The client now depends on the [HTTPlug library](http://httplug.io/) to
  perform HTTP requests. This means that the package now depends the virtual
  `php-http/client-implementation`. To satisfy this dependency you have to
  pick [an implementation](https://packagist.org/providers/php-http/client-implementation)
  and install it together with `php-xapi/client`.

  For example, if you prefer to use [Guzzle 6](http://docs.guzzlephp.org/en/latest/)
  you would do the following:

  ```bash
  $ composer require --no-update php-http/guzzle6-adapter
  $ composer require php-xapi/client
  ```

* The `setHttpClient()` and `setRequestFactory()` method have been added
  to the `XApiClientBuilderInterface` and must be used to configure the
  `HttpClient` and `RequestFactory` instances you intend to use.

  To use [Guzzle 6](http://docs.guzzlephp.org/en/latest/), for example,
  this will look like this:

  ```php
  use Http\Adapter\Guzzle6\Client;
  use Http\Message\MessageFactory\GuzzleMessageFactory;
  use Xabbuh\XApi\Client\XApiClientBuilder;

  $builder = new XApiClientBuilder();
  $client = $builder->setHttpClient(new Client())
      ->setRequestFactory(new GuzzleMessageFactory())
      ->setBaseUrl('http://example.com/xapi/')
      ->build();
  ```

  You can avoid calling `setHttpClient()` and `setRequestFactory` by installing
  the [HTTP discovery](http://php-http.org/en/latest/discovery.html) package.

* The `xabbuh/oauth1-authentication` package now must be installed if you want
  to use OAuth1 authentication.

* A second optional `$attachments` argument (defaulting to `true`) has been added
  to the `getStatement()`, `getVoidedStatement()`, and `getStatements()` methods
  of the `StatementsApiClient` class and the `StatementsApiClientInterface`.

* An optional fifth `$headers` parameter has been added to the `createRequest()`
  method of the `HandlerInterface` and the `Handler` class which allows to pass
  custom headers when performing HTTP requests.

Upgrading from 0.2 to 0.3
-------------------------

* Removed the `ApiClient` class. The `$requestHandler` and `$version` attributes
  have been moved to the former child classes of the `ApiClient` class and
  their visibility has been changed to `private`.

* The visibility of the `$documentDataSerializer` property of the `ActivityProfileApiClient`,
  `AgentProfileApiClient`, `DocumentApiClient`, and `StateApiClient` classes
  has been changed to `private`.

* Removed the `getRequestHandler()` method from the API classes:

  * `ActivityProfileApiClient::getRequestHandler()`
  * `AgentProfileApiClient::getRequestHandler()`
  * `ApiClient::getRequestHandler()`
  * `DocumentApiClient::getRequestHandler()`
  * `StateApiClient::getRequestHandler()`
  * `StatementsApiClient::getRequestHandler()`
  * `XApiClient::getRequestHandler()`

* Removed the `getVersion()` method from the API interfaces:

  * `ActivityProfileApiClientInterface::getVersion()`
  * `AgentProfileApiClientInterface::getVersion()`
  * `StateApiClientInterface::getVersion()`
  * `StatementsApiClientInterface::getVersion()`

* Removed the `getVersion()` method from the API classes:

  * `ActivityProfileApiClient::getVersion()`
  * `AgentProfileApiClient::getVersion()`
  * `ApiClient::getVersion()`
  * `DocumentApiClient::getVersion()`
  * `StateApiClient::getVersion()`
  * `StatementsApiClient::getVersion()`
  * `XApiClient::getVersion()`

* Removed the `getUsername()` and `getPassword()` methods from the `HandlerInterface`
  and the `Handler` class.

* Removed the `getHttpClient()` method from the `Handler` class.

* Removed the `getSerializerRegistry()` method from the `XApiClient` class.

* All classes are final now which means that you can now longer extend them.
  Consider using composition/decoration instead if you need to build functionality
  on top of the built-in classes.

Upgrading from 0.1 to 0.2
-------------------------

* Statement identifiers must be passed as `StatementId` objects instead of
  strings.
