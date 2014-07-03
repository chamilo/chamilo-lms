# Configuration

The CKEditor provides an advanced configuration which can be reused on multiple CKEditor instance. Instead of duplicate
the configuration on each form builder, you can directly configure it once & then reuse it all the time.

## Basic configuration

The bundle allows you to define as many configs you want. The list of all config options are available
[here](http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html).

``` yaml
ivory_ck_editor:
    configs:
        my_config:
            toolbar:                [ [ "Source", "-", "Save" ], "/", [ "Anchor" ], "/", [ "Maximize" ] ]
            uiColor:                "#000000"
            filebrowserUploadRoute: "my_route"
            extraPlugins:           "wordcount"
            # ...
```

When you have defined a config, you can use it with the `config_name` option:

``` php
$builder->add('field', 'ckeditor', array(
    'config_name' => 'my_config',
));
```

If you want to override some parts of the defined config, you can still use the `config` option:

``` php
$builder->add('field', 'ckeditor', array(
    'config_name' => 'my_config',
    'config'      => array('uiColor' => '#ffffff'),
));
```

If you want to define globally your configuration, you can use the `default_config` node:

``` yaml
ivory_ck_editor:
    default_config: my_config
    configs:
        my_config:
            # ...
```

## Toolbar configuration

### Built-in

CKEditor provides 3 different packages with their own configurations (full, standard & basic). The bundle is shipped
with the full edition but you can easily switch the toolbar configuration by using the `full`, `standard` or `basic`
keywork as toolbar.

``` yaml
ivory_ck_editor:
    configs:
        my_config:
            toolbar: standard
```

Here, the toolbar will be rendered as it is defined in the standard edition. If you want to define your own toolbar,
you should use the custom way :)

### Custom

Build a toolbar in the configuration or on the form builder is really a pain... Each time, you want a custom one, you
need to redefine all the structure... To avoid this duplication, the bundle allows you to define your toolbars in a
separate node & reuse them:

``` yaml
ivory_ck_editor:
    configs:
        my_config_1:
            toolbar: "my_toolbar_1"
            uiColor: "#000000"
            # ...
        my_config_2:
            toolbar: "my_toolbar_2"
            uiColor: "#ffffff"
            # ...
        my_config_2:
            toolbar: "my_toolbar_1"
            uiColor: "#cccccc"
    toolbars:
        configs:
            my_toolbar_1: [ [ "Source", "-", "Save" ], "/", [ "Anchor" ], "/", [ "Maximize" ] ]
            my_toolbar_2: [ [ "Source" ], "/", [ "Anchor" ], "/", [ "Maximize" ] ]
```

The config is still not perfect as you still have code duplications in the toolbar items. To avoid this part, you can
define a group of items in a separate node & then, inject them in your toolbar by prefixing them with a `@`.

``` yaml
ivory_ck_editor:
    configs:
        my_config_1:
            toolbar: "my_toolbar_1"
            uiColor: "#000000"
            # ...
        my_config_2:
            toolbar: "my_toolbar_2"
            uiColor: "#ffffff"
            # ...
    toolbars:
        configs:
            my_toolbar_1: [ "@document", "/", "@link" , "/", "@tool" ]
            my_toolbar_2: [ "@document", "/", "@tool" ]
        items:
            document: [ "Source", "-", "Save" ]
            link:     [ "Anchor" ]
            tool:     [ "Maximize" ]
```
