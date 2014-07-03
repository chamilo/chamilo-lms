# Usage of Pager component

This tutorial will cover installation and usage examples.

## Installation

Initially, all what is needed is:

- >= php5.3.2
- Symfony **EventDispatcher** component and if you do not have autoloader, I recommend
**ClassLoader** from the same symfony components
- this repository

Now somewhere in you third party vendor directory download mentioned dependencies:
**Note:** if you are in your project root and you have [git](http://help.github.com/set-up-git-redirect)
installed, **vendor** directory is the location where these components will be installed.

- run **git clone git://github.com/knplabs/knp-components.git vendor/knp-components**
- run **git clone git://github.com/symfony/EventDispatcher.git vendor/Symfony/Component/EventDispatcher**
- run **git clone git://github.com/symfony/ClassLoader.git vendor/Symfony/Component/ClassLoader**

To initially autoload these components, you will need to include
**vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php**

``` php
<?php
// file: autoloader.php
// taking into account that this autoloader is in the same directory as vendor folder
require_once __DIR__.'/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader;
$loader->registerNamespaces(array(
    'Symfony\\Component' => __DIR__.'/vendor',
    'Knp\\Component' => __DIR__.'/vendor/knp-components/src'
));
$loader->register();
```

Next, usually **index.php** file is the starting point. Lets create it in the same
project root directory as **autoloader.php**

``` php
<?php
// file: index.php
include 'autoloader.php';

// usage examples will continue here
```

In general now you can start using the paginator..

## Basic usage

As mentioned in [introduction](https://github.com/knplabs/knp-components/tree/master/doc/pager/intro.md)
paginator uses event listeners to paginate the given data. First we will start from the simplest data - array.
Lets add some code in **index.php** and see it in action:

``` php
<?php
// file: index.php
include 'autoloader.php';

// usage examples will continue here

use Knp\Component\Pager\Paginator; // used class name

// end of line and tab definition
define('EOL', php_sapi_name() === 'cli' ? PHP_EOL : '<br/>');
define('TAB', php_sapi_name() === 'cli' ? "\t" : '<span style="margin-left:25px"/>');

$paginator = new Paginator; // initializes default event dispatcher, with standard listeners
$target = range('a', 'z'); // an array to paginate
// paginate target and generate representation class, it can be overrided by event listener
$pagination = $paginator->paginate($target, 1/*page number*/, 10/*limit per page*/);

echo 'total count: '.$pagination->getTotalItemCount().EOL;
echo 'pagination items of page: '.$pagination->getCurrentPageNumber().EOL;
// iterate items
foreach ($pagination as $item) {
    //...
    echo TAB.'paginated item: '.$item.EOL;
}

$pagination = $paginator->paginate($target, 3/*page number*/, 10/*limit per page*/);
echo 'pagination items of page: '.$pagination->getCurrentPageNumber().EOL;
// iterate items
foreach ($pagination as $item) {
    //...
    echo TAB.'paginated item: '.$item.EOL;
}
```

### Rendering pagination

**$paginator->paginate($target...)** will return pagination class, which is by
default **SlidingPagination** it executes a **$pagination->renderer** callback
with all arguments reguired in view template. Its your decision to implement
it whatever way you like.

**Note:** this is the default method. There will be more examples on how to render pagination templates

So if you by default print the pagination you should see something like:

``` php
<?php
// continuing in file: index.php
// ...

echo $pagination; // outputs: "override in order to render a template"
```

Now if we override the renderer callback

``` php
<?php
// continuing in file: index.php
// ...

$pagination->renderer = function($data) {
    return EOL.TAB.'page range: '.implode(' ', $data['pagesInRange']).EOL;
};
echo $pagination; // outputs: "page range: 1 2 3"
```
