const tailwindcss = require('tailwindcss');

const plugins = [
  tailwindcss,
  require('autoprefixer'),
]

if (process.env.QUASAR_RTL) {
  plugins.push(
    require('postcss-rtl')({})
  )
}

module.exports = {
  plugins
}
