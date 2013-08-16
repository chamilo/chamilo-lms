Sphinx Extensions for PHP and Symfony
=====================================

After adding `sensio` to your path (with something like `sys.path.insert(0,
os.path.abspath('./path/to/sensio'))`), you can use the following extensions
in your `conf.py` file:

 * `sensio.sphinx.refinclude`
 * `sensio.sphinx.configurationblock`
 * `sensio.sphinx.phpcode`

To enable highlighting for PHP code not between `<?php ... ?>` by default:

    lexers['php'] = PhpLexer(startinline=True)
    lexers['php-annotations'] = PhpLexer(startinline=True)

And here is how to use PHP as the primary domain:

    primary_domain = 'php'

Configure the `api_url` for links to the API:

    api_url = 'http://api.symfony.com/master/%s'
