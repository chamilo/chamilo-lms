import globals from "globals"
import pluginJs from "@eslint/js"
import pluginVue from "eslint-plugin-vue"
import pluginPrettierRecommended from "eslint-plugin-prettier/recommended"

export default [
  {
    files: ["**/*.{js,mjs,cjs,vue}"],
  },
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        // Webpack replaces process.env.NODE_ENV at build time.
        process: "readonly",
      },
    },
  },
  {
    // CommonJS files are build and check scripts; they run under Node, not in a browser.
    files: ["**/*.cjs"],
    languageOptions: {
      sourceType: "commonjs",
      globals: globals.node,
    },
  },
  pluginJs.configs.recommended,
  ...pluginVue.configs["flat/essential"],
  pluginPrettierRecommended,
]
