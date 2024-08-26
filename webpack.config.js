const Encore = require("@symfony/webpack-encore")
const dotenv = require("dotenv")
const webpack = require("webpack")

const env = dotenv.config()

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev")
}

Encore.setOutputPath("public/build/")
  .setManifestKeyPrefix("public/build/")
  .setPublicPath("/build")
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()

  .addEntry("legacy_app", "./assets/js/legacy/app.js")
  .addEntry("legacy_exercise", "./assets/js/legacy/exercise.js")
  .addEntry("legacy_free-jqgrid", "./assets/js/legacy/free-jqgrid.js")
  .addEntry("legacy_lp", "./assets/js/legacy/lp.js")
  .addEntry("legacy_document", "./assets/js/legacy/document.js")
  .addEntry("legacy_framereadyloader", "./assets/js/legacy/frameReadyLoader.js")

  .addEntry("vue", "./assets/vue/main.js")
  .addEntry("vue_installer", "./assets/vue/main_installer.js")
  .addEntry("translatehtml", "./assets/js/translatehtml.js")

  .addStyleEntry("app", "./assets/css/app.scss")
  .addStyleEntry("css/chat", "./assets/css/chat.css")
  .addStyleEntry("css/document", "./assets/css/document.css")
  .addStyleEntry("css/editor", "./assets/css/editor.css")
  .addStyleEntry("css/editor_content", "./assets/css/editor_content.css")
  .addStyleEntry("css/markdown", "./assets/css/markdown.css")
  .addStyleEntry("css/print", "./assets/css/print.css")
  .addStyleEntry("css/responsive", "./assets/css/responsive.css")
  .addStyleEntry("css/scorm", "./assets/css/scorm.scss")

  .enableSingleRuntimeChunk()
  .enableIntegrityHashes()
  .enableSourceMaps(!Encore.isProduction())

  // enables @babel/preset-env polyfills
  .configureBabel(() => {})
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = "usage"
    config.corejs = 3
  })

  .enableSassLoader()
  .enableTypeScriptLoader(function (tsConfig) {
    tsConfig.transpileOnly = true
  })
  .enableVueLoader(() => {}, { version: 3, runtimeCompilerBuild: false })
  .autoProvidejQuery()
  .enablePostCssLoader((options) => {
    options.postcssOptions = {
      plugins: {
        tailwindcss: {},
        autoprefixer: {},
      },
    }
  })
  .copyFiles([
    {
      from: "./node_modules/multiselect-two-sides/dist/js",
      pattern: /(multiselect.js)$/,
      to: "libs/multiselect-two-sides/dist/js/multiselect.js",
    },
    {
      from: "./node_modules/pwstrength-bootstrap/dist/",
      pattern: /(pwstrength-bootstrap.js)$/,
      to: "libs/pwstrength-bootstrap/dist/pwstrength-bootstrap.js",
    },
    {
      from: "./node_modules/readmore-js",
      pattern: /(readmore.js)$/,
      to: "libs/readmore-js/readmore.js",
    },
    {
      from: "./node_modules/js-cookie/src/",
      pattern: /(js.cookie.js)$/,
      to: "libs/js-cookie/src/js.cookie.js",
    },
    {
      from: "./node_modules/qtip2/dist/basic",
      pattern: /(jquery.qtip.js)$/,
      to: "libs/qtip2/dist/jquery.qtip.js",
    },
    {
      from: "./node_modules/qtip2/dist/basic",
      pattern: /(jquery.qtip.css)$/,
      to: "libs/qtip2/dist/jquery.qtip.css",
    },
    {
      from: "./node_modules/flatpickr/dist/l10n",
      to: "flatpickr/l10n/[name].[ext]",
    },
    {
      from: "./node_modules/chart.js/dist/",
      to: "libs/chartjs/[name].[ext]",
      pattern: /\.(js|css)$/,
    }
  ])
  .addPlugin(
    new webpack.DefinePlugin({
      ENV_CUSTOM_VUE_TEMPLATE: JSON.stringify(env.parsed?.APP_CUSTOM_VUE_TEMPLATE),
    }),
  )
  .configureDevServerOptions((options) => {
    options.host = "0.0.0.0"
  })

Encore.copyFiles({
  from: "./node_modules/mediaelement/build",
  to: "libs/mediaelement/[path][name].[ext]",
})
Encore.copyFiles({
  from: "./node_modules/mediaelement-plugins/dist",
  to: "libs/mediaelement/plugins/[path][name].[ext]",
})
Encore.copyFiles({
  from: "./node_modules/mathjax/config",
  to: "libs/mathjax/config/[path][name].[ext]",
})
Encore.copyFiles({
  from: "node_modules/moment/locale",
  to: "libs/locale/[path][name].[ext]",
})
Encore.copyFiles({
  from: "./node_modules/select2/dist/css",
  to: "libs/select2/css/[name].[ext]",
})
Encore.copyFiles({
  from: "./node_modules/select2/dist/js",
  to: "libs/select2/js/[name].[ext]",
})

const config = Encore.getWebpackConfig()

module.exports = config
