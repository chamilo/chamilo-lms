const Encore = require("@symfony/webpack-encore")
const dotenv = require("dotenv")
const webpack = require("webpack")
const fs = require("fs")
const path = require("path")

const env = dotenv.config({ quiet: true })

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev")
}

const isProd = Encore.isProduction()

Encore.setOutputPath("public/build/")
  .setManifestKeyPrefix("public/build/")
  .setPublicPath("/build")
  .enableBuildNotifications()

// Clean output only in production to speed up development builds.
if (isProd) {
  Encore.cleanupOutputBeforeBuild()
}

Encore.addEntry("legacy_app", "./assets/js/legacy/app.js")
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
  .enableSourceMaps(!isProd)

  // Enable @babel/preset-env polyfills.
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

// Enable production-only hashing features.
// This keeps dev builds faster and avoids unnecessary hashed asset handling while developing.
if (isProd) {
  Encore.enableIntegrityHashes()
  Encore.enableVersioning()
}

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

class CopyUnhashedAssetsPlugin {
  apply(compiler) {
    compiler.hooks.afterEmit.tap("CopyUnhashedAssetsPlugin", () => {
      const buildPath = path.resolve(__dirname, "public/build")
      const cssPath = path.join(buildPath, "css")
      const qtipDistPath = path.join(buildPath, "libs/qtip2/dist")

      if (!fs.existsSync(buildPath)) {
        return
      }

      // Copy legacy_document.js without hash.
      const legacyDocumentFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_document\.[a-f0-9]+\.js$/)
      )
      if (legacyDocumentFile) {
        fs.copyFileSync(
          path.join(buildPath, legacyDocumentFile),
          path.join(buildPath, "legacy_document.js")
        )
      }

      // Copy legacy_exercise.js without hash.
      const legacyExerciseFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_exercise\.[a-f0-9]+\.js$/)
      )
      if (legacyExerciseFile) {
        fs.copyFileSync(
          path.join(buildPath, legacyExerciseFile),
          path.join(buildPath, "legacy_exercise.js")
        )
      }

      // Do not copy runtime.js without hash.
      // A non-versioned runtime file can be cached and become desynchronized
      // from hashed chunks, causing ChunkLoadError ("missing") at runtime.

      // Copy document.css without hash.
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

      // Copy legacy_framereadyloader.js without hash.
      const frameReadyFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_framereadyloader\.[a-f0-9]+\.js$/)
      )
      if (frameReadyFile) {
        fs.copyFileSync(
          path.join(buildPath, frameReadyFile),
          path.join(buildPath, "legacy_framereadyloader.js")
        )
      }

      // Copy legacy_framereadyloader.css without hash.
      const frameReadyCssFile = fs.readdirSync(buildPath).find((f) =>
        f.match(/^legacy_framereadyloader\.[a-f0-9]+\.css$/)
      )
      if (frameReadyCssFile) {
        fs.copyFileSync(
          path.join(buildPath, frameReadyCssFile),
          path.join(buildPath, "legacy_framereadyloader.css")
        )
      }

      // Keep unhashed qTip assets for legacy direct references.
      if (fs.existsSync(qtipDistPath)) {
        const qtipFile = fs.readdirSync(qtipDistPath).find((f) =>
          f.match(/^jquery\.qtip\.js$/)
        )
        if (qtipFile) {
          fs.copyFileSync(
            path.join(qtipDistPath, qtipFile),
            path.join(qtipDistPath, "jquery.qtip.js")
          )
        }

        const qtipCssFile = fs.readdirSync(qtipDistPath).find((f) =>
          f.match(/^jquery\.qtip\.css$/)
        )
        if (qtipCssFile) {
          fs.copyFileSync(
            path.join(qtipDistPath, qtipCssFile),
            path.join(qtipDistPath, "jquery.qtip.css")
          )
        }
      }

      // Copy glossary_auto.js without hash.
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

// Use explicit runtime/chunk global names to reduce collisions
// when multiple bundles are loaded on the same page.
config.output = config.output || {}
config.output.uniqueName = "chamilo"
config.output.chunkLoadingGlobal = "webpackChunkChamilo"

// Enable persistent filesystem cache to speed up rebuilds.
config.cache = {
  type: "filesystem",
}

module.exports = config
