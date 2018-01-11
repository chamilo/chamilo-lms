var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')

    .setManifestKeyPrefix('build/public')
    // If chamilo is installed in localhost/chamilo2
    .setPublicPath('/chamilo2/public/build/')
    // If chamilo is installed in a domain my.chamilo.net
    //.setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    // enable features!
    .enableSassLoader(function(sassOptions) {}, {
    //     resolveUrlLoader: false
     })
    .autoProvidejQuery()
    .enableReactPreset()

    // read main.js     -> output as public/build/chamilo.js
    .addEntry('chamilo', './assets/js/main.js')
    // read main.scss -> output as web/build/css/base.css
    .addStyleEntry('css/base', './assets/css/main.scss')

    .addStyleEntry('css/editor', './assets/css/editor.css')

    // Add chamilo themes
    .addStyleEntry('css/themes/academica/default', './assets/css/themes/academica/default.css')
    .addStyleEntry('css/themes/chamilo/default', './assets/css/themes/chamilo/default.css')

    .enableSourceMaps(!Encore.isProduction())
    .autoProvideVariables({
        $: 'jquery',
        jQuery: 'jquery',
        'window.jQuery': 'jquery'
    })
    //.enableVersioning() // hashed filenames (e.g. main.abc123.js)
;

module.exports = Encore.getWebpackConfig();

