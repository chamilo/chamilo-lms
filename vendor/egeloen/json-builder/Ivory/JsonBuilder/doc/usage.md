# Usage

The Ivory JSON builder allows yout to build your JSON through the Symfony2
[PropertyAccess](http://symfony.com/doc/current/components/property_access/index.html) component while keeping the
control of the value escaping.

## Create a builder

To build some JSON, you will need to instanciate a builder:

``` php
use Ivory\JsonBuilder\JsonBuilder;

$builder = new JsonBuilder();
```

## Set your values

To set your values on the builder, you can either use `setValues` or `setValue` but be aware they don't behave same.
Basically, `setValues` allows you to append a set of values in the builder without escaping control whereas `setValue`
allows you to append one value in the builder but with escaping control.

### Append a set of values

To append a set of values in the builder, just use `setValues` and pass your values as first argument:

``` php
$builder->setValues(array('foo' => array('bar')));
```

Additionally, this method takes as second argument a path prefix (Symfony2
[PropertyAccess](http://symfony.com/doc/current/components/property_access/index.html) component syntax) which
allows you to append your values where you want in the builder graph. So, the next sample is basically the equivalent
of the precedent:

``` php
$builder->setValues(array('bar'), '[foo]');
```

### Append one value

To append one value in the builder, just use `setValue` and pass the path as first argument and the value as second one:

``` php
$builder->setValue('[foo][0]','bar');
```

If you want to keep control of the value escaping, this part is for you :) Basically, just pass `false` as third
argument:

``` php
$builder->setValue('[foo][0]','bar', false);
```

## Configure the JSON encode options

By default, the JSON builder uses the native `json_encode` options. To override it, you can use:

``` php
$builder->setJsonEncodeOptions(JSON_FORCE_OBJECT);
$jsonEncodeOptions = $builder->getJsonEncodeOptions();
```

## Build your JSON

Once your builder is well configured, you can build your JSON:

``` php
$json = $builder->build();
```

## Reset the builder

Because the builder is statefull (keep a track of every values), you need to reset it if you want to restart a json
build:

``` php
$builder->reset();
```

## Example

If you still don't understand, a concrete example :)

``` php
use Ivory\JsonBuilder\JsonBuilder;

$builder = new JsonBuilder();

// {"0":"foo","1":bar}
echo $builder
    ->setJsonEncodeOptions(JSON_FORCE_OBJECT)
    ->setValues(array('foo'))
    ->setValue('[1]', 'bar', false)
    ->build();

// {"foo":["bar"],"baz":bat}
echo $builder
    ->reset()
    ->setValues(array('foo' => array('bar')))
    ->setValue('[baz]', 'bat', false)
    ->build();
```
