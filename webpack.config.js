var Encore = require('@symfony/webpack-encore');
var copyWebpackPlugin = require('copy-webpack-plugin');

Encore
    .setOutputPath('public/build/')
    .setManifestKeyPrefix('public/build/')
    // If chamilo is installed in localhost/chamilo2
    .setPublicPath('../')
    // If chamilo is installed in a domain my.chamilo.net
    //.setPublicPath('/public/build')
    .cleanupOutputBeforeBuild()

    .addEntry('app', './assets/js/app.js')
    .addEntry('bootstrap', './assets/js/bootstrap.js')

    .addStyleEntry('css/app', './assets/css/app.scss')
    .addStyleEntry('css/bootstrap', './assets/css/bootstrap.scss')

    .addStyleEntry('css/chat', './assets/css/chat.css')
    .addStyleEntry('css/document', './assets/css/document.css')
    .addStyleEntry('css/editor', './assets/css/editor.css')
    .addStyleEntry('css/editor_content', './assets/css/editor_content.css')
    .addStyleEntry('css/markdown', './assets/css/markdown.css')
    .addStyleEntry('css/print', './assets/css/print.css')
    .addStyleEntry('css/responsive', './assets/css/responsive.css')
    .addStyleEntry('css/scorm', './assets/css/scorm.css')

    .enableSingleRuntimeChunk()

    .enableSourceMaps(!Encore.isProduction())
    // .enableVersioning(Encore.isProduction())

    .enableSassLoader()

    .autoProvidejQuery()
;

var themes = [
    'chamilo'
];

// Add Chamilo themes
themes.forEach(function (theme) {
    Encore.addStyleEntry('css/themes/' + theme + '/default', './assets/css/themes/' + theme + '/default.css');

    // Copy images from themes into public/build
    Encore.addPlugin(new copyWebpackPlugin([{
        from: 'assets/css/themes/' + theme + '/images',
        to: 'css/themes/' + theme + '/images'
    },
    ]));
});

module.exports = Encore.getWebpackConfig();
