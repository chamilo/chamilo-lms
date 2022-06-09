const colors = require('tailwindcss/colors')

module.exports = {
  content: [
      './assets/**/*.{js,vue}',
      './public/main/**/*.php',
      './src/CoreBudnle/Resources/views/**/*.html.twig',
  ],
  theme: {
    colors: {
      'primary': '#2e75a3',
      'secondary': '#fd6600',
      'gray': {
        5: '#fcfcfc',
        10: '#fafafa',
        15: '#f7f8f9',
        20: '#edf0f2',
        25: '#e4e9ed',
        30: 'rgba(0, 0, 0, 0.12)',
        50: '#a2a6b0',
        90: '#333333',
      },
      'support': {
        1: 'rgba(46, 117, 163, 0.08)',
        2: '#f5f8fa',
        3: 'rgba(46, 117, 163, 0.5)',
        4: '#244d67',
        5: '#e06410',
        6: '#faf7f5',
      },
      'warning': '#f5ce01',
      'success': '#77aa0c',
      'error': '#df3b3b',
      'info': '#0d7bfd',

      'white': colors.white,
      'black': colors.black,
      'transparent': colors.transparent,
      'current': colors.current,
    },
    extend: {
      fontFamily: {
        sans: ['Helvetica Neue', 'Helvetica', 'Arial', 'sans-serif']
      },
    },
    fontSize: {
      'header': ['52px', '64px'],
      'h1': ['32px', '40px'],
      'h2': ['29px', '32px'],
      'h3': ['26px', '32px'],
      'h4': ['23px', '32px'],
      'h5': ['20px', '24px'],
      'h6': ['18px', '24px'],
      'body-1': ['16px', '24px'],
      'body-2': ['14px', '16px'],
      'caption': ['13px', '16px'],
      'tiny': ['11px', '16px'],
    }
  },
  plugins: [
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/line-clamp'),
    require('@tailwindcss/forms'),
    require("@tailwindcss/typography")
  ],
}
