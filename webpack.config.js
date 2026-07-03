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

// Desktop build notifications are optional. Keep them disabled by default
// because webpack-notifier pulls node-notifier, which is not compatible with
// the ESM-only uuid package on newer Node.js versions and breaks the build.
if (process.env.CHAMILO_WEBPACK_NOTIFICATIONS === "1") {
  Encore.enableBuildNotifications()
}

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
  .addStyleEntry("css/chat", "./assets/css/chat.scss")
  .addStyleEntry("css/document", "./assets/css/document.scss")
  .addStyleEntry("css/editor", "./assets/css/editor.scss")
  .addStyleEntry("css/editor_content", "./assets/css/editor_content.scss")
  .addStyleEntry("css/markdown", "./assets/css/markdown.scss")
  .addStyleEntry("css/print", "./assets/css/print.scss")
  .addStyleEntry("css/responsive", "./assets/css/responsive.scss")
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
/*Encore.copyFiles({
  from: "./node_modules/mathjax/config",
  to: "libs/mathjax/config/[path][name].[ext]",
})*/
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

      // Read each directory once and reuse the listing for every lookup below,
      // instead of re-scanning the (large) build directory on every match.
      const buildFiles = fs.readdirSync(buildPath)

      // Copy the first file matching `pattern` in `dir` to `target`, dropping the content hash.
      const copyUnhashed = (dir, files, pattern, target) => {
        const match = files.find((f) => f.match(pattern))
        if (match) {
          fs.copyFileSync(path.join(dir, match), path.join(dir, target))
        }
      }

      // Copy legacy_document.js without hash.
      copyUnhashed(buildPath, buildFiles, /^legacy_document\.[a-f0-9]+\.js$/, "legacy_document.js")

      // Copy legacy_exercise.js without hash.
      copyUnhashed(buildPath, buildFiles, /^legacy_exercise\.[a-f0-9]+\.js$/, "legacy_exercise.js")

      // Do not copy runtime.js without hash.
      // A non-versioned runtime file can be cached and become desynchronized
      // from hashed chunks, causing ChunkLoadError ("missing") at runtime.

      // Copy document.css and editor_content.css without hash.
      if (fs.existsSync(cssPath)) {
        const cssFiles = fs.readdirSync(cssPath)
        copyUnhashed(cssPath, cssFiles, /^document\.[a-f0-9]+\.css$/, "document.css")
        copyUnhashed(cssPath, cssFiles, /^editor_content\.[a-f0-9]+\.css$/, "editor_content.css")
      }

      // Copy legacy_framereadyloader.js and .css without hash.
      copyUnhashed(buildPath, buildFiles, /^legacy_framereadyloader\.[a-f0-9]+\.js$/, "legacy_framereadyloader.js")
      copyUnhashed(buildPath, buildFiles, /^legacy_framereadyloader\.[a-f0-9]+\.css$/, "legacy_framereadyloader.css")

      // Keep unhashed qTip assets for legacy direct references.
      if (fs.existsSync(qtipDistPath)) {
        const qtipFiles = fs.readdirSync(qtipDistPath)
        copyUnhashed(qtipDistPath, qtipFiles, /^jquery\.qtip\.js$/, "jquery.qtip.js")
        copyUnhashed(qtipDistPath, qtipFiles, /^jquery\.qtip\.css$/, "jquery.qtip.css")
      }

      // Copy glossary_auto.js without hash.
      copyUnhashed(buildPath, buildFiles, /^glossary_auto\.[a-f0-9]+\.js$/, "glossary_auto.js")
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

// Use a lightweight source map in development to lower the build's memory peak.
// The default ("inline-source-map") inlines full per-module maps and is the main
// driver of "JavaScript heap out of memory" failures on smaller servers.
if (!isProd) {
  config.devtool = "eval-cheap-module-source-map"
}

module.exports = config
