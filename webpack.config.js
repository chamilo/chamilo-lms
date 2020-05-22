var Encore = require('@symfony/webpack-encore');

const CopyWebpackPlugin = require('copy-webpack-plugin');
const FileManagerPlugin = require('filemanager-webpack-plugin');

Encore
    .setOutputPath('public/build/')
    .setManifestKeyPrefix('public/build/')
    // .setPublicPath('../')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()

    .addEntry('app', './assets/js/app.js')
    .addEntry('vue', './assets/vue/main.js')
    .addEntry('bootstrap', './assets/js/bootstrap.js')
    .addEntry('exercise', './assets/js/exercise.js')
    // .addEntry('free-jqgrid', './assets/js/free-jqgrid.js')
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
    .enableIntegrityHashes()

    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabel((babelConfig) => {
        babelConfig.plugins.push('@babel/plugin-transform-runtime');
    }, {
        useBuiltIns: 'usage',
        corejs: 3
    })

    .enableSassLoader()
    .enableVueLoader(function(options) {
        options.pluginOptions = {
            quasar: {
                importStrategy: 'manual',
                rtlSupport: false
            }
        }

        options.transpileDependencies = ['quasar'];
    })
    .autoProvidejQuery()
    .copyFiles([
        {
            from: './node_modules/multiselect-two-sides/dist/js',
            pattern: /(multiselect.js)$/,
            to: 'libs/multiselect-two-sides/dist/js/multiselect.js'
        },
        {
            from: './node_modules/pwstrength-bootstrap/dist/',
            pattern: /(pwstrength-bootstrap.js)$/,
            to: 'libs/pwstrength-bootstrap/dist/pwstrength-bootstrap.js'
        },
        {
            from: './node_modules/readmore-js',
            pattern: /(readmore.js)$/,
            to: 'libs/readmore-js/readmore.js'
        },
        {
            from: './node_modules/js-cookie/src/',
            pattern: /(js.cookie.js)$/,
            to: 'libs/js-cookie/src/js.cookie.js'
        },
        {
            from: './node_modules/mathjax/',
            pattern: /(MathJax.js)$/,
            to: 'libs/mathjax/MathJax.js'
        },
    ])
    // enable ESLint
    // .addLoader({
    //     enforce: 'pre',
    //     test: /\.(js|vue)$/,
    //     loader: 'eslint-loader',
    //     exclude: /node_modules/,
    //     options: {
    //         fix: true,
    //         emitError: false,
    //         emitWarning: true,
    //
    //     },
    // })
;

Encore.addPlugin(new CopyWebpackPlugin([
    {
        from: './node_modules/mediaelement/build',
        to: 'libs/mediaelement'
    },
    {
        from: './node_modules/mediaelement-plugins/dist',
        to: 'libs/mediaelement/plugins'
    },
    {
        from: './node_modules/mathjax/config',
        to: 'libs/mathjax/config'
    },
]));

// Encore.addPlugin(new copyWebpackPlugin([{
//     from: 'assets/css/themes/' + theme + '/images',
//     to: 'css/themes/' + theme + '/images'
// };

var themes = [
    'chamilo'
];

// Add Chamilo themes
themes.forEach(function (theme) {
    Encore.addStyleEntry('css/themes/' + theme + '/default', './assets/css/themes/' + theme + '/default.css');

    // Copy images from themes into public/build
    Encore.addPlugin(new CopyWebpackPlugin([{
        from: 'assets/css/themes/' + theme + '/images',
        to: 'css/themes/' + theme + '/images'
    },
    ]));
});

// Fix free-jqgrid languages files
// Encore.addPlugin(new FileManagerPlugin({
//     onEnd: {
//         move: [
//             {
//                 source: './public/public/build/free-jqgrid/',
//                 destination: './public/build/free-jqgrid/'
//             }
//         ],
//         delete: [
//             './public/public/'
//         ]
//     }
// }));

module.exports = Encore.getWebpackConfig();