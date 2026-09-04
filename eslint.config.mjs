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
  pluginJs.configs.recommended,
  ...pluginVue.configs["flat/essential"],
  pluginPrettierRecommended,
]
