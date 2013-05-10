# ConfigServiceProvider

A JSON/Yaml-based config ServiceProvider for [Silex](http://silex.sensiolabs.org).

## Usage

### Passing a config file

Pass the config file's path to the service provider's constructor. This is the
recommended way of doing it, allowing you to define multiple environments.

    $env = getenv('APP_ENV') ?: 'prod';
    $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));

Now you can specify a `prod` and a `dev` environment.

**config/prod.json**

    {
        "debug": false
    }

**config/dev.json**

    {
        "debug": true
    }

To switch between them, just set the `APP_ENV` environment variable. In apache
that would be:

    SetEnv APP_ENV dev

Or in nginx with fcgi:

    fastcgi_param APP_ENV dev

### Replacements

Also, you can pass an array of replacement patterns as second argument.

    $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/services.json", array(
        'data_path' => __DIR__.'/data',
    )));

Now you can use the pattern in your configuration file.

**/config/services.json**

    {
        "xsl.path": "%data_path%/xsl"
    }

### Using Yaml instead of JSON

To use Yaml instead of JSON, just pass a file that ends on `.yml`:

    $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/services.yml"));

Note, you will have to require the `2.1.*` of the `symfony/yaml` package.

### Multiple config files

You can use multiple config files, e. g. one for a whole application and a
specific one for a task by calling `$app->register()` several times, each time
passing another instance of `Igorw\Silex\ConfigServiceProvider`.
