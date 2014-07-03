Assetic Configuration
=====================

If you are not using [symfony-bootstrap](http://github.com/phiamo/symfony-bootstrap) you must configure assetic to use less

Yui CSS and CSS Embed are very nice and recommended.
To make full use of bootstraps capabilites they are not needed, neither is less but its up to you

Here is an example configuration for your config.yml:
Make sure you have java installed

``` yaml
assetic:
    filters:
        less:
            node: /usr/bin/node
            node_paths: [/opt/lessc/lib, /usr/lib/node_modules]
            apply_to: "\.less$"
        cssrewrite: ~
        cssembed:
            jar: %kernel.root_dir%/Resources/java/cssembed-0.3.6.jar
            apply_to: "\.css$|\.less$"
        yui_css:
            jar: %kernel.root_dir%/Resources/java/yuicompressor-2.4.6.jar
            apply_to: "\.css$"
        yui_js:
            jar: %kernel.root_dir%/Resources/java/yuicompressor-2.4.6.jar
```

Do not forget to add the jars to your app.

If you encounter the following Error:

```
An exception has been thrown during the compilation of a template ("You must add MopaBootstrapBundle to the assetic.bundle config to use the {% stylesheets %} tag in MopaBootstrapBundle::base.html.twig.") in "/YourProject/vendor/mopa/bootstrap-bundle/Mopa/Bundle/BootstrapBundle/Resources/views/base.html.twig".
```

It's because the Bundle is not added to the bundles: [ ] config option in the assetic config.

``` yaml
assetic:
    debug:          %kernel.debug%
        use_controller: false
        bundles:        [ ] # <-
        filters:
            ....
```

You need to either remove that config var (to use assetic for all Bundles) or add the MopaBootstrapBundle

If your are using cssembed, you might notice problems when embedding bootrap via less:

[RuntimeException]                                                                                                                                   
  [ERROR] /path/to/your/bundle/Resources/public/less/../img/glyphicons-halflings.png (No such file or directory)  

this is due to cssembed and bootstrap not working so nicely with relative paths.

Try using the fully qualified path in the source instead.

Bootstrap provides a variable which allows to configure this.

Less:

```
@icon-font-path: "/bundles/mopabootstrap/bootstrap/fonts/";
```

Sass:

```
$icon-font-path: "/bundles/mopabootstrap/bootstrap/fonts/";
```

Another way is to copy the glyphicons-halflings.png to your public img folder

``` bash
cp /your/path/to/bootstrap/img/glyphicons-halflings.png to /path/to/your/bundle/Resources/public/img/
```

so cssembed finds the file in the corresponding position
