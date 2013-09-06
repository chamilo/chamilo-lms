## PagerfantaServiceProvider

Provider to use [Pagerfanta](https://github.com/whiteoctober/Pagerfanta) with [Silex](https://github.com/fabpot/Silex)

This Provider is based on [WhiteOctoberPagerfantaBundle](https://github.com/whiteoctober/WhiteOctoberPagerfantaBundle) and includes:
  * Twig function to render pagerfantas with views and options.
  * Way to use easily views.

#### Registering

```
$app->register(new FranMoreno\Silex\Provider\PagerfantaServiceProvider());
```

#### Parameters

This are the default parameters:

```
$app['pagerfanta.view.options'] = array(
    'routeName'     => null,
    'routeParams'   => array(),
    'pageParameter' => '[page]',
    'proximity'     => 3,
    'next_message'  => '&raquo;',
    'prev_message'  => '&laquo;',
    'default_view'  => 'default'
);
```

#### Rendering pagination

The Twig Extension provides this function:

    {{ pagerfanta(my_pager, view_name, view_options) }}

The routes are generated automatically for the current route using the variable
"page" to propagate the page number. By default, the bundle uses the
*DefaultView* with the *default* name. 

    {{ pagerfanta(my_pager) }}

If you want to use a custom template, add another argument

    <div class="pagerfanta">
        {{ pagerfanta(my_pager, 'my_template') }}
    </div>

With Options

    {{ pagerfanta(my_pager, 'default', { 'proximity': 2}) }}

See the Pagerfanta documentation for the list of the parameters.

#### Adding a custom template

You can use the CSS classes provided with DefaultView, but if you want to create a Custom View, you have to implement ```Pagerfanta\View\ViewInterface```, then add the View to the Factory:

    $app['pagerfanta.view_factory'] = $app->share($app->extend('pagerfanta.view_factory', function($viewFactory, $app) {
        $customView = new \Foo\Bar\View\CustomView();
        $viewFactory->add(array(
            'my_view' => $customView
        ));
    
        return $viewFactory;
    }));
    
And if you want set as default view:

    $app['pagerfanta.view.options'] = array(
        'default_view'  => 'my_view'
    );

#### Use in controllers

    use Pagerfanta\Pagerfanta;
    use Pagerfanta\Adapter\ArrayAdapter;

    $app->get('/index', function (Request $request) use ($app) {

        $results = $app['some.service']->getResults();
    
        $adapter = new ArrayAdapter($results);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(10);
        $pagerfanta->setCurrentPage($request->query->get('page', 1));
    
        return $app['twig']->render('index.html', array(
            'my_pager' => $pagerfanta
        ));
    })
