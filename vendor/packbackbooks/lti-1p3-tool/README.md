# LTI 1.3 Tool Library

A library used for building IMS-certified LTI 1.3 tool providers in PHP.

This library allows a tool provider (your app) to receive LTI launches from a tool consumer (i.e. LMS). It validates LTI launches and lets an application interact with services like the Names Roles Provisioning Service (to fetch a roster for an LMS course) and Assignment Grades Service (to update grades for students in a course in the LMS).

This library was forked from [IMSGlobal/lti-1-3-php-library](https://github.com/IMSGlobal/lti-1-3-php-library), initially created by @MartinLenord. [Packback](https://packback.co) found the library immensely helpful and extended it over the years. It has been rewritten by Packback to bring it into compliance with the standards set out by the PHP-FIG and the IMS LTI 1.3 Certification process. Packback actively uses and maintains this library.

## Installation

Run:

```bash
composer require packbackbooks/lti-1p3-tool
```

In your code, you will now be able to use classes in the `Packback\Lti1p3` namespace to access the library.

### Configuring JWT

Add the following when bootstrapping your app.

```php
use Firebase\JWT\JWT;

JWT::$leeway = 5;
```

### Data Storage

This library uses three methods for storing and accessing data: cache, cookie, and database. All three must be implemented in order for the library to work. You may create your own custom implementations so long as they adhere to the following interfaces:

- `Packback\Lti1p3\Interfaces\Cache`
- `Packback\Lti1p3\Interfaces\Cookie`
- `Packback\Lti1p3\Interfaces\Database`

Cache and Cookie storage have legacy implementations at `Packback\Lti1p3\ImsStorage\` if you do not wish to implement your own. However you must implement your own database.

#### Database

To allow for launches to be validated and to allow the tool to know where it has to make calls to, registration data must be stored.

The `Packback\Lti1p3\Database` interface must be fully implemented for this to work.

```php
class ExampleDatabase implements Packback\Lti1p3\Interfaces\Database
{
    public function findRegistrationByIssuer($iss): Packback\Lti1p3\LtiRegistration
    {
        $issuer = Issuer::find($iss);
        return Packback\Lti1p3\LtiRegistration::new()
            ->setAuthLoginUrl($issuer->auth_login_url)
            ->setAuthTokenUrl($issuer->auth_token_url)
            ->setClientId($issuer->client_id)
            ->setKeySetUrl($issuer->key_set_url)
            ->setKid($issuer->kid)
            ->setIssuer($issuer->issuer)
            ->setToolPrivateKey($issuer->private_key);
    }
    public function findDeployment($iss, $deployment_id): Packback\Lti1p3\LtiDeployment
    {
        $issuer = Issuer::where('issuer', $issuer_url)
            ->where('client_id', $deployment_id)
            ->first();
        return Packback\Lti1p3\LtiDeployment::new()
            ->setDeploymentId($issuer->deployment_id);
    }
}
```

### Creating a JWKS endpoint

A JWKS (JSON Web Key Set) endpoint can be generated for either an individual registration or from an array of `KID`s and private keys.

```php
use Packback\Lti1p3\JwksEndpoint;

// From issuer
JwksEndpoint::fromIssuer($database, 'http://example.com')->outputJwks();
// From registration
JwksEndpoint::fromRegistration($registration)->outputJwks();
// From array
JwksEndpoint::new(['a_unique_KID' => file_get_contents('/path/to/private/key.pem')])->outputJwks();
```

## Handling Requests

### Open Id Connect Login Request

LTI 1.3 uses a modified version of the OpenId Connect third party initiate login flow. This means that to do an LTI 1.3 launch, you must first receive a login initialization request and return to the platform.

To handle this request, you must first create a new `Packback\Lti1p3\LtiOidcLogin` object.

```php
$login = LtiOidcLogin::new($database);
```

Now you must configure your login request with a return url (this must be preconfigured and white-listed on the tool).

If a redirect url is not given or the registration does not exist an `Packback\Lti1p3\OidcException` will be thrown.

```php
try {
    $redirect = $login->doOidcLoginRedirect("https://my.tool/launch");
} catch (Packback\Lti1p3\OidcException $e) {
    // handle the error
}
```

With the redirect, we can now redirect the user back to the tool.  From there you can get the url you need to redirect to, with all the necessary query parameters.

```php
$redirect_url = $redirect->getRedirectUrl();
```

Alternatively you can redirect using a 302 location header, or javascript.

```php
// Location header
$redirect->doRedirect();
// Javascript
$redirect->doJsRedirect();
```

Redirect is now done, we can move onto the launch.

### LTI Message Launches

Now that we have done the OIDC log the platform will launch back to the tool. To handle this request, first we need to create a new `Packback\Lti1p3\LtiMessageLaunch` object.

```php
$launch = Packback\Lti1p3\LtiMessageLaunch::new($database);
```

#### Validating a Launch

Once we have the message launch, we can validate it. This will check signatures and the presence of a deployment and any required parameters.

If the validation fails an exception will be thrown.

```php
try {
    $launch->validate();
} catch (Exception $e) {
    // Handle failed launch
}
```

#### Accessing Launch Information

Now we know the launch is valid we can find out more information about the launch.

Check if we have a resource launch or a deep linking launch.

```php
if ($launch->isResourceLaunch()) {
    echo 'Resource Launch!';
} else if ($launch->isDeepLinkLaunch()) {
    echo 'Deep Linking Launch!';
} else {
    echo 'Unknown launch type';
}
```

Check which services we have access to.

```php
if ($launch->hasAgs()) {
    echo 'Has Assignments and Grades Service';
}
if ($launch->hasNrps()) {
    echo 'Has Names and Roles Service';
}
```

### Accessing Cached Launch Requests

It is likely that you will want to refer back to a launch later during subsequent requests. This is done using the launch id to identify a cached request. The launch id can be found using:

```php
$launch_id = $launch->getLaunchId().
```

Once you have the launch id, you can link it to your session and pass it along as a query parameter.

**Make sure you check the launch id against the user session to prevent someone from making actions on another person's launch.**

Retrieving a launch using the launch id can be done using:

```php
$launch = LtiMessageLaunch::fromCache($launch_id, $database);
```

Once retrieved, you can call any of the methods on the launch object as normal, e.g.

```php
if ($launch->hasAgs()) {
    echo 'Has Assignments and Grades Service';
}
```

### Deep Linking Responses

If you receive a deep linking launch, it is very likely that you are going to want to respond to the deep linking request with resources for the platform.

To create a deep link response you will need to get the deep link for the current launch.

```php
$dl = $launch->getDeepLink();
```

Now we are going to need to create `Packback\Lti1p3\LtiDeepLinkResource` to return.

```php
$resource = Packback\Lti1p3\LtiDeepLinkResource::new()
    ->setUrl("https://my.tool/launch")
    ->setCustomParams(['my_param' => $my_param])
    ->setTitle('My Resource');
```

Everything is set to return the resource to the platform. There are two methods of doing this.

The following method will output the html for an aut-posting form for you.

```php
$dl->outputResponseForm([$resource]);
```

Alternatively you can just request the signed JWT that will need posting back to the platform by calling.

```php
$dl->getResponseJwt([$resource]);
```

## Calling Services

### Names and Roles Service

Before using names and roles you should check that you have access to it.

```php
if (!$launch->hasNrps()) {
    throw new Exception("Don't have names and roles!");
}
```

Once we know we can access it, we can get an instance of the service from the launch.

```php
$nrps = $launch->getNrps();
```

From the service we can get an array of all the members by calling:

```php
$members = $nrps->getMembers();
```

### Assignments and Grades Service

Before using assignments and grades you should check that you have access to it.

```php
if (!$launch->hasAgs()) {
    throw new Exception("Don't have assignments and grades!");
}
```

Once we know we can access it, we can get an instance of the service from the launch.

```php
$ags = $launch->getAgs();
```

To pass a grade back to the platform, you will need to create an `Packback\Lti1p3\LtiGrade` object and populate it with the necessary information.

```php
$grade = Packback\Lti1p3\LtiGrade::new()
    ->setScoreGiven($grade)
    ->setScoreMaximum(100)
    ->setTimestamp(date(DateTime::ISO8601))
    ->setActivityProgress('Completed')
    ->setGradingProgress('FullyGraded')
    ->setUserId($external_user_id);
```

To send the grade to the platform we can call:

```php
$ags->putGrade($grade);
```

This will put the grade into the default provided lineitem. If no default lineitem exists it will create one.

If you want to send multiple types of grade back, that can be done by specifying an `Packback\Lti1p3\LtiLineitem`.

```php
$lineitem = Packback\Lti1p3\LtiLineitem::new()
    ->setTag('grade')
    ->setScoreMaximum(100)
    ->setLabel('Grade');

$ags->putGrade($grade, $lineitem);
```

If a lineitem with the same `tag` exists, that lineitem will be used, otherwise a new lineitem will be created.

## Laravel Implementation Guide

### Installation

Install the library with composer. Open up the AppServiceProvider and set the `JWT::$leeway` `boot()` method. (Both of these steps are described in the installation section above).

In the `register()` method, bind your implementation of the data Cache, Cookie, and Database to their interfaces:

```php
use App\Lti13Cache;
use App\Lti13Cookie;
use App\Lti13Database;
use Firebase\JWT\JWT;
use Illuminate\Support\ServiceProvider;
use Packback\Lti1p3\Interfaces\Cache;
use Packback\Lti1p3\Interfaces\Cookie;
use Packback\Lti1p3\Interfaces\Database;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JWT::$leeway = 5;
    }

    public function register()
    {
        $this->app->bind(Cache::class, Lti13Cache::class);
        $this->app->bind(Cookie::class, Lti13Cookie::class);
        $this->app->bind(Database::class, Lti13Database::class);
    }
}
```

Once this is done, you can begin building the endpoints necessary to handle an LTI 1.3 launch, such as:

- Login
- Launch
- JWKs

### Sample Data Store Implementations

Below are examples of how to get the library's data store interfaces to work with Laravel's facades.

#### Cache

```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Packback\Lti1p3\Interfaces\Cache as Lti1p3Cache;

class Lti13Cache implements Lti1p3Cache
{
    public const NONCE_PREFIX = 'nonce_';

    public function getLaunchData($key)
    {
        return Cache::get($key);
    }

    public function cacheLaunchData($key, $jwt_body)
    {
        $duration = Config::get('cache.duration.default');
        Cache::put($key, $jwt_body, $duration);
        return $this;
    }

    public function cacheNonce($nonce)
    {
        $duration = Config::get('cache.duration.default');
        Cache::put(static::NONCE_PREFIX . $nonce, true, $duration);
        return $this;
    }

    public function checkNonce($nonce)
    {
        return Cache::get(static::NONCE_PREFIX . $nonce, false);
    }
}
```

#### Cookie

```php
use Illuminate\Support\Facades\Cookie;
use Packback\Lti1p3\Interfaces\Cookie as Lti1p3Cookie;

class Lti13Cookie implements Lti1p3Cookie
{
    public function getCookie($name)
    {
        return Cookie::get($name, false);
    }

    public function setCookie($name, $value, $exp = 3600, $options = []): self
    {
        Cookie::queue($name, $value, $exp);
        return $this;
    }
}
```

#### Database

For this data store you will need to create models to store the issuer and deployment in the database.

```php
use App\Models\Issuer;
use App\Models\Deployment;
use Packback\Lti1p3\Interfaces\Database;
use Packback\Lti1p3\LtiRegistration;
use Packback\Lti1p3\LtiDeployment;
use Packback\Lti1p3\OidcException;

class Lti13Database implements Database
{
    public static function findIssuer($issuer_url, $client_id = null)
    {
        $query = Issuer::where('issuer', $issuer_url);
        if ($client_id) {
            $query = $query->where('client_id', $client_id);
        }
        if ($query->count() > 1) {
            throw new OidcException('Found multiple registrations for the given issuer, ensure a client_id is specified on login (contact your LMS administrator)', 1);
        }
        return $query->first();
    }

    public function findRegistrationByIssuer($issuer, $client_id = null)
    {
        $issuer = self::findIssuer($issuer, $client_id);
        if (!$issuer) {
            return false;
        }

        return LtiRegistration::new()
            ->setAuthTokenUrl($issuer->auth_token_url)
            ->setAuthLoginUrl($issuer->auth_login_url)
            ->setClientId($issuer->client_id)
            ->setKeySetUrl($issuer->key_set_url)
            ->setKid($issuer->kid)
            ->setIssuer($issuer->issuer)
            ->setToolPrivateKey($issuer->tool_private_key);
    }

    public function findDeployment($issuer, $deployment_id, $client_id = null)
    {
        $issuerModel = self::findIssuer($issuer, $client_id);
        if (!$issuerModel) {
            return false;
        }
        $deployment = $issuerModel->deployments()->where('deployment_id', $deployment_id)->first();
        if (!$deployment) {
            return false;
        }

        return LtiDeployment::new()
            ->setDeploymentId($deployment->id);
    }
}
```

## Sample Legacy Implementation

This library was forked and rewritten from [IMSGlobal/lti-1-3-php-library](https://github.com/IMSGlobal/lti-1-3-php-library). That repo provides an [example implementation](https://github.com/IMSGlobal/lti-1-3-php-example-tool) that may be helpful.

## Contributing

For improvements, suggestions or bug fixes, make a pull request or an issue. Before opening a pull request, add automated tests for your changes and ensure that all tests pass.

### Testing

Automated tests can be run using the command:

```bash
composer test
```
