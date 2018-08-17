var Encore = require('@symfony/webpack-encore');
var copyWebpackPlugin = require('copy-webpack-plugin');

Encore
    .setOutputPath('public/build/')
    .setManifestKeyPrefix('public/build/')
    // If chamilo is installed in localhost/chamilo2
    .setPublicPath('../')
    // If chamilo is installed in a domain my.chamilo.net
    //.setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    // enable features!
    .enableSassLoader(function(sassOptions) {}, {
         //resolveUrlLoader: false
     })
    .enableLessLoader()
    .autoProvidejQuery() // not needed because in window.jQuery we set the $
    // Reads the "assets/js/vendor.js" file and it will generate the file public/build/vendor.js file
    .addEntry('vendor', './assets/js/vendor.js')
    .addEntry('app', './assets/js/app.js')
    .addEntry('bootstrap', './assets/js/bootstrap.js')
    // Reads app.scss -> output as web/build/css/base.css
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

    .enableSourceMaps(!Encore.isProduction())
    .autoProvideVariables({
        $: 'jquery',
        jQuery: 'jquery',
        'window.jQuery': 'jquery'
    })
    //.enableVersioning() // hashed filenames (e.g. main.abc123.js)
;

var themes = [
    'academica',
    'baby_orange',
    'beach',
    'blue_lagoon',
    'chamilo',
    'chamilo_electric_blue',
    'chamilo_green',
    'chamilo_orange',
    'chamilo_red',
    'chamilo_sport_red',
    'cool_blue',
    'corporate',
    'cosmic_campus',
    'delicious_bordeaux',
    'empire_green',
    'fruity_orange',
    'holi',
    'journal',
    'kiddy',
    'medical',
    'readable',
    'royal_purple',
    'silver_line',
    'simplex',
    'sober_brown',
    'spacelab',
    'steel_grey',
    'tasty_olive',
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

var config = Encore.getWebpackConfig();
module.exports = config;
