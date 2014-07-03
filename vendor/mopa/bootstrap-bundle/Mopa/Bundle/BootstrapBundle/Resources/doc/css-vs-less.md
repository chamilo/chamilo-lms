Css vs. Less
============

Bootstrap is written in [http://lesscss.org](less) and compiled to css.
You can download a pure css distribution from http://twitter.github.com/bootstrap/

But then you must manage it yourself. e.g.
 - Install it in Ressources/public/
 - use the pure css template

If you want to make use e.g. of mixins, variables etc. you must use less see http://twitter.github.com/bootstrap/less.html
There are several ways of including it

- either you let assetic manage the compilation of your less files 
- you use the less js

### Which is faster to kickstart?

Depends on what you want to achieve, and what your knowledge is:

- If you feel good hacking your system, installing newer programs and maintaining your dev environment, 
the recommended way would be to install less.
  You probably should invest the time to get a working setup, and  after that relax, and see less beeing integrated well, and providing what it should.

- If you dont feel comfortable to install adittional software and maintain them, and you are not experienced in less, probabaly you could start with css version.

But be warned:

Since less is THE recommended way of working with bootstrap, all css specific instructions may work well or not.
They have a least been tested once and worked in this scenario.

The less way is tested dayly and works out to be a flexible fast way of working with all the (also less) dependencies.

For getting less working, there are seceral documents provided, so the focus here is on the css way:

This is how it could be done without using less features:
hear is a sniplet that is known to be working and was tested
First you have to go from your project directory:

```bash
cd vendor/mopa/bootstrap-bundle/Mopa/BootstrapBundle/Resources/public/bootstrap
make
```

If you get any error have a look into
https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/less-installation.md

If there is still an error like

"TypeError: Cannot call method 'charAt' of undefined"

Check your lessc bins and pathes:

I had this 'charAt' error with
lessc 1.2.1 (LESS Compiler) [JavaScript]
So you need to use a newer lessc version:
lessc 1.3.0 (LESS Compiler) [JavaScript]

so you need to tell your shell which version to use, if you already installed a newer lessc as suposed in https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/less-installation.md

Try:

```bash
export PATH=/opt/lessc/bin:$PATH
```

be warned:

- paginator.less isnt compiled, so its missing
- all less features are not available
- you have to take care for everything yourself!

So here is the final sniplet:
make sure the files have been generated (make):

```jinja
{% stylesheets filter='?yui_css'
    '@MopaBootstrapBundle/Resources/public/bootstrap/docs/assets/css/bootstrap.css'
    '@YourNiceBundle/Resources/public/css/*'
%}
<link href="{{ asset_url }}" type="text/css" rel="stylesheet" media="screen" />
{% endstylesheets %}
```
