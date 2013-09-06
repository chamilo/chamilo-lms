# Snappy Service Provider for Silex

The SnappyProvider provides integration with [Snappy](https://github.com/knplabs/snappy/) in [Silex](http://silex-project.org/).

## Parameters

* __snappy.image_binary:__ Absolute path to `wkhtmltoimage`.
* __snappy.image_options:__ Array of options to give to Snappy (see [wkhtmltoimage doc](http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltoimage_0.10.0_rc2-doc.html)).
* __snappy.pdf_binary:__ Absolute path to `wkhtmltopdf`.
* __snappy.pdf_options:__ Array of options to give to Snappy (see [wkhtmltopdf doc](http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltopdf_0.10.0_rc2-doc.html)).

## Services

* __snappy.image:__ Snappy service to create image snapshots / thumbnails.
* __snappy.pdf:__ Snappy service to create pdf.

## Registering

If you are using [composer](http://getcomposer.org) to include the SilexServiceProvider in your project, you do not need to register anything. Composer automatically adds the appropriate namespaces to the autoloader.

In case you're not using composer, you will need to register the `Knp\Snappy` namespace in your autoloader yourself. Make sure you place a copy of _Snappy_ in the `vendor/snappy` directory.

```php
$app->register(new Grom\Silex\SnappyServiceProvider(), array(
    'snappy.image_binary' => '/usr/local/bin/wkhtmltoimage',
    'snappy.pdf_binary'   => '/usr/local/bin/wkhtmltopdf',
));
```

## Usage

You can use both `snappy.image` and `snappy.pdf` the same way.

```php
use Symfony\Component\HttpFoundation\Response;

$app->get('/image', function() use ($app) {
    $url = $app['request']->get('url');
    $image = $app['snappy.image']->getOutput($url);

    $response = new Response($image);
    $response->headers->set('Content-Type', 'image/jpeg');

    return $response;
});
```

This will convert the given url into an image. Try `/image?url=http://www.github.com`

```php
use Symfony\Component\HttpFoundation\Response;

$app->get('/pdf', function() use ($app) {
    $url = $app['request']->get('url');
    $pdf = $app['snappy.pdf']->getOutput($url);

    $response = new Response($pdf);
    $response->headers->set('Content-Type', 'application/pdf');

    return $response;
});
```

This will convert the given url into a PDF. Try `/pdf?url=http://www.github.com`