CHANGELOG
=========

0.7.0
-----

* dropped support for PHP < 7.1

0.6.0
-----

* added compatibility with HTTPlug 2

* dropped the support for HHVM and for PHP < 5.6

* updated the `X-Experience-API-Version` header to default to the latest patch
  version (`1.0.3`)

* allow `2.x` releases of the `php-xapi/model` package too

* allow `3.x` releases of the `php-xapi/model` package for PHP 7.2 compatibility

0.5.0
-----

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

* Bumped the required versions of all `php-xapi` packages to the `1.x` release
  series.

* Include the raw attachment content wrapped in a `multipart/mixed` encoded
  request when raw content is part of a statement's attachment.

* Added the possibility to decide whether or not to include attachments when
  requesting statements from an LRS. A second optional `$attachments` argument
  (defaulting to `true`) has been added for this purpose to the `getStatement()`,
  `getVoidedStatement()`, and `getStatements()` methods of the `StatementsApiClient`
  class and the `StatementsApiClientInterface`.

* An optional fifth `$headers` parameter has been added to the `createRequest()`
  method of the `HandlerInterface` and the `Handler` class which allows to pass
  custom headers when performing HTTP requests.

0.4.0
-----

* The `XApiClientBuilder` class now makes use of the `SerializerFactoryInterface`
  introduced in release `0.4.0` of the `php-xapi/serializer` package. By
  default, it will fall back to the `SerializerFactory` implemented provided
  by the `php-xapi/symfony-serializer` to maintain backwards-compatibility
  with the previous release. However, you are now able to inject arbitrary
  implementations of the `SerializerFactoryInterface` into the constructor
  of the `XApiClientBuilder` to use whatever alternative implementation
  (packages providing such an implementation should provide the virtual
  `php-xapi/serializer-implementation` package).

0.3.0
-----

* Do not send authentication headers when no credentials have been configured.

* Fixed treating HTTP methods case insensitive. Rejecting uppercased HTTP
  method names contradicts the HTTP specification. Lowercased method names
  will still be supported to keep backwards compatibility though.

* Fixed creating `XApiClient` instances in an invalid state. The `XApiClientBuilder`
  now throws a `\LogicException` when the `build()` method is called before
  a base URI was configured.

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

* Made all classes final.

0.2.0
-----

* made the client compatible with version 0.5 of the `php-xapi/model` package

* made the client compatible with version 0.3 of the `php-xapi/serializer` package

0.1.0
-----

First release of an Experience API client based on the Guzzle HTTP library.

This package replaces the `xabbuh/xapi-client` package which is now deprecated
and should no longer be used.
