# ConfigServiceProvider

A config ServiceProvider for [Silex](http://silex.sensiolabs.org) with support
for php, json, yaml, and toml.

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

You can also specify replacements inside the config file by using a key with
`%foo%` notation:

    {
        "%root_path%": "../..",
        "xsl.path": "%root_path%/xsl"
    }

### Using Yaml

To use Yaml instead of JSON, just pass a file that ends on `.yml`:

    $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/services.yml"));

Note, you will have to require the `~2.1` of the `symfony/yaml` package.

### Using TOML

To use [TOML](https://github.com/mojombo/toml) instead of any of the other supported formats,
just pass a file that ends on `.toml`:

    $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/services.toml"));

Note, you will have to require the `~0.1` of the `jamesmoss/toml` package and you are using
a bleeding edge configuration format, as the spec of TOML is still subject to change.

### Using plain PHP

If reading the config file on every request becomes a performance problem in
production, you can use a plain PHP file instead, and it will get cached by
APC.

You'll have to rewrite your config to be a PHP file that returns the array of
config data, and also make sure it ends with `.php`:

    $app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/prod.php"));

### Multiple config files

You can use multiple config files, e. g. one for a whole application and a
specific one for a task by calling `$app->register()` several times, each time
passing another instance of `Igorw\Silex\ConfigServiceProvider`.

### Register order

Make sure you register ConfigServiceProvider last with your application. If you do not do this,
the default values of other Providers will override your configuration.
