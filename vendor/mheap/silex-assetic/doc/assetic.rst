AsseticServiceProvider
================

The *AsseticServiceProvider* provides powerful asset management
through Kris Wallsmith's `Assetic <https://github.com/kriswallsmith/assetic>`_
library.

Parameters
----------

* **assetic.path_to_web**: Location where to dump all generated files

* **assetic.options**: An associative array of assetic options.

* **assetic.options => debug** (defaults to false, optional):

* **assetic.options => formulae_cache_dir** (optional): When formulae_cache_dir is set, Assetic
  will cache assets generated trough formulae in this folder to improve performance. Remember,
  assets added trough the AssetManager need to care about their own cache.

* **assetic.options => auto_dump_assets** (defaults to true,optional): Whether to write all the assets
  to filesystem on every request.

Services
--------

* **assetic**: Instance of AssetFactory for
  holding filters and assets (not formulae)

* **assetic.asset_manager**: Instance of AssetManager
  for adding assets (implements AssetInterface)

  Example usage::

    $asset = new FileAsset(__DIR__ . '/extra/*.css');
    $app['assetic.asset_manager']->set('extra_css', $asset);

* **assetic.filter_manager**: Instance of FilterManager
  for adding filters (implements FilterInterface)

  Example usage::

    $filter = new CssMinFilter();
    $app['assetic.filter_manager']->set('css_min', $filter);

* **assetic.asset_writer**: If you need it, feel free to use.

* **assetic.lazy_asset_manager**:  Instance of LazyAssetManager
  to enable passing-in assets as formulae

  Example usage::

    $app['assetic.lazy_asset_manager']->setFormula('extra_css', array(
        array(__DIR__ . '/extra/*.css'),
        array('yui_css'),
        array('output' => 'css/extra')
    ));

* **assetic.dumper**:  Instance of SilexAssetic\Assetic\Dumper. Contains methods
  to dump assets.

Registering
-----------

  Example registration and configuration::

    $app->register(new SilexAssetic\AsseticServiceProvider());

    $app['assetic.path_to_web'] = __DIR__ . '/assets';
    $app['assetic.options'] = array(
    	'debug' => true,
    );
    $app['assetic.filter_manager'] = $app->share(
        $app->extend('assetic.filter_manager', function($fm, $app) {
            $fm->set('yui_css', new Assetic\Filter\Yui\CssCompressorFilter(
                '/usr/share/yui-compressor/yui-compressor.jar'
            ));
            $fm->set('yui_js', new Assetic\Filter\Yui\JsCompressorFilter(
                '/usr/share/yui-compressor/yui-compressor.jar'
            ));

            return $fm;
        })
    );
    $app['assetic.asset_manager'] = $app->share(
        $app->extend('assetic.asset_manager', function($am, $app) {
            $am->set('styles', new Assetic\Asset\AssetCache(
                new Assetic\Asset\GlobAsset(
                    __DIR__ . '/resources/css/*.css',
                    array($app['assetic.filter_manager']->get('yui_css'))
                ),
                new Assetic\Cache\FilesystemCache(__DIR__ . '/cache/assetic')
            ));
            $am->get('styles')->setTargetPath('css/styles.css');

            return $am;
        })
    );

