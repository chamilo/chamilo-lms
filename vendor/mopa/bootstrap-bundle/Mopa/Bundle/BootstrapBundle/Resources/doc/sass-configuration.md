Sass / Compass Configuration
=====================

[Sass](http://sass-lang.com/ "Sass Website") is another CSS-preprocessor like less.

[Compass](http://compass-style.org/) is a authoring Framework for [Sass](http://sass-lang.com/ "Sass Website")

## Why Sass / Compass instead of Less?
It's maybe a matter of taste, maybe one Preprocessor/CSS-Framework is more powerful. Just read the following Comparisons and decide for yourself.

[Side by Side Comparison](https://gist.github.com/1591333)

[Sass vs.Less](http://css-tricks.com/sass-vs-less/ "Sass vs. Less")

## Installation ##

Compass / Sass needs a running ruby gem installation.

#### FreeBSD
If you are using **FreeBSD**, you can install Compass directly via the Port, which also installs the dependencies on ruby:

```bash
cd /usr/ports/textproc/rubygem-compass && make install
```

#### Ubuntu
Install Instructions for Ubuntu: [http://www.ubuntulinuxhelp.com/installing-compass-on-ubuntu/](http://www.ubuntulinuxhelp.com/installing-compass-on-ubuntu/)
#### Running gem Installation

You can also install Sass or Compass via ruby gem (if that's already installed):

```bash
gem install sass
```
or 

```bash
gem install compass
```
## Configuration ##

Here is an example configuration for your **app/config.yml**:

```yaml

assetic:
    filters:
        cssrewrite: ~
        sass:
            bin: /usr/local/bin/sass
            apply_to: "\.sass$"
        scss:
            sass: /usr/local/bin/sass
            apply_to: "\.scss$"        
        yui_css:
            jar: %kernel.root_dir%/Resources/java/yuicompressor-2.4.6.jar
            apply_to: "\.css$"
        yui_js:
            jar: %kernel.root_dir%/Resources/java/yuicompressor-2.4.6.jar
```

You can also use Compass to parse **.sass** and **.scss** Files:

```yaml

assetic:
    filters:
        cssrewrite: ~
        compass:
            sass: /usr/local/bin/compass
            apply_to: "\.(scss|sass)$" 
        yui_css:
            jar: %kernel.root_dir%/Resources/java/yuicompressor-2.4.6.jar
            apply_to: "\.css$"
        yui_js:
            jar: %kernel.root_dir%/Resources/java/yuicompressor-2.4.6.jar
```

## Usage ##

Just use the usual twig/assetic tag for scripts

```jinja
{% stylesheets filter='?yui_css'
    '@MopaBootstrapBundle/Resources/public/sass/mopabootstrapbundle.scss'
%}
```