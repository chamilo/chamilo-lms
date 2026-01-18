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
  .addEntry("glossary_auto", "./assets/js/glossary-auto.js")

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
      from: "./node_modules/flatpickr/dist",
      pattern: /flatpickr\.min\.(css|js)$/,
      to: "flatpickr/[name].[ext]",
    },
    {
      from: "./node_modules/flatpickr/dist/l10n",
      to: "flatpickr/l10n/[name].[ext]",
    },
    {
      from: "./node_modules/chart.js/dist/",
      to: "libs/chartjs/[name].[ext]",
      pattern: /\.(js|css)$/,
    },
  ])
  .addPlugin(
    new webpack.DefinePlugin({
      ENV_CUSTOM_VUE_TEMPLATE: JSON.stringify(env.parsed?.APP_CUSTOM_VUE_TEMPLATE),
    }),
  )
  .configureDevServerOptions((options) => {
    options.host = "0.0.0.0"
  })
  .enableVersioning()

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

const fs = require("fs")
const path = require("path")
class CopyUnhashedAssetsPlugin {
  apply(compiler) {
    compiler.hooks.afterEmit.tap("CopyUnhashedAssetsPlugin", (compilation) => {
      const buildPath = path.resolve(__dirname, "public/build")

      // === COPY legacy_document.js without hash ===
      const legacyDocumentFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_document\.[a-f0-9]+\.js$/)
      )
      if (legacyDocumentFile) {
        fs.copyFileSync(
          path.join(buildPath, legacyDocumentFile),
          path.join(buildPath, "legacy_document.js")
        )
      }

      // === COPY legacy_exercise.js without hash ===
      const legacyExerciseFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_exercise\.[a-f0-9]+\.js$/)
      )
      if (legacyExerciseFile) {
        fs.copyFileSync(
          path.join(buildPath, legacyExerciseFile),
          path.join(buildPath, "legacy_exercise.js")
        )
      }

      // === COPY runtime.js without hash ===
      const runtimeFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^runtime\.[a-f0-9]+\.js$/)
      )
      if (runtimeFile) {
        fs.copyFileSync(
          path.join(buildPath, runtimeFile),
          path.join(buildPath, "runtime.js")
        )
      }

      // === COPY document.css without hash ===
      const cssPath = path.join(buildPath, "css")
      if (fs.existsSync(cssPath)) {
        const documentCssFile = fs.readdirSync(cssPath).find((f) =>
          f.match(/^document\.[a-f0-9]+\.css$/)
        )
        if (documentCssFile) {
          fs.copyFileSync(
            path.join(cssPath, documentCssFile),
            path.join(cssPath, "document.css")
          )
        }
        const editorContentCssFile = fs.readdirSync(cssPath).find((f) =>
          f.match(/^editor_content\.[a-f0-9]+\.css$/)
        )
        if (editorContentCssFile) {
          fs.copyFileSync(
            path.join(cssPath, editorContentCssFile),
            path.join(cssPath, "editor_content.css")
          )
        }
      }

      // === COPY legacy_framereadyloader.js without hash ===
      const frameReadyFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_framereadyloader\.[a-f0-9]+\.js$/)
      )
      if (frameReadyFile) {
        fs.copyFileSync(
          path.join(buildPath, frameReadyFile),
          path.join(buildPath, "legacy_framereadyloader.js")
        )
      }

      // === COPY legacy_framereadyloader.css without hash ===
      const frameReadyCssFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_framereadyloader\.[a-f0-9]+\.css$/)
      )
      if (frameReadyCssFile) {
        fs.copyFileSync(
          path.join(buildPath, frameReadyCssFile),
          path.join(buildPath, "legacy_framereadyloader.css")
        )
      }

      // === COPY jquery.qtip.js without hash ===
      const qtipFile = fs.readdirSync(buildPath + "/libs/qtip2/dist").find((f) =>
        f.match(/^jquery\.qtip\.js$/)
      )
      if (qtipFile) {
        fs.copyFileSync(
          path.join(buildPath, "libs/qtip2/dist", qtipFile),
          path.join(buildPath, "libs/qtip2/dist/jquery.qtip.js")
        )
      }

      // === COPY jquery.qtip.css without hash ===
      const qtipCssFile = fs.readdirSync(buildPath + "/libs/qtip2/dist").find((f) =>
        f.match(/^jquery\.qtip\.css$/)
      )
      if (qtipCssFile) {
        fs.copyFileSync(
          path.join(buildPath, "libs/qtip2/dist", qtipCssFile),
          path.join(buildPath, "libs/qtip2/dist/jquery.qtip.css")
        )
      }

      // === COPY glossary_auto.js without hash ===
      const glossaryFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^glossary_auto\.[a-f0-9]+\.js$/)
      )
      if (glossaryFile) {
        fs.copyFileSync(
          path.join(buildPath, glossaryFile),
          path.join(buildPath, "glossary_auto.js")
        )
      }

    })
  }
}

Encore.addPlugin(new CopyUnhashedAssetsPlugin())
const config = Encore.getWebpackConfig()
module.exports = config
