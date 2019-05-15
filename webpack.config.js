var Encore = require('@symfony/webpack-encore');
var copyWebpackPlugin = require('copy-webpack-plugin');
var fileManagerPlugin = require('filemanager-webpack-plugin');

Encore
    .setOutputPath('public/build/')
    .setManifestKeyPrefix('public/build/')
    .setPublicPath('../')
    .cleanupOutputBeforeBuild()

    .addEntry('app', './assets/js/app.js')
    .addEntry('bootstrap', './assets/js/bootstrap.js')

    .addEntry('free-jqgrid', './assets/js/free-jqgrid.js')

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
    .enableVueLoader()
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

// Fix free-jqgrid langiages files
Encore.addPlugin(new fileManagerPlugin({
    onEnd: {
        move: [
            {
                source: './public/public/build/free-jqgrid/',
                destination: './public/build/free-jqgrid/'
            }
        ],
        delete: [
            './public/public/'
        ]
    }
}));

module.exports = Encore.getWebpackConfig();
