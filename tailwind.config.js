module.exports = {
  purge: [],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      fontFamily: {
        sans: ['Helvetica Neue', 'Helvetica', 'Arial', 'sans-serif']
      },
      colors: {
        'ch-primary': {
          DEFAULT: '#84bd00', //default to '#2e75a3'
          'dark': '#649d00', //default to '#1c6391',
          'light': '#84bd00', //default '#9cc2da'
        },
        'ch-secondary': {
          DEFAULT: '#772583', //default to '#fd6600',
          'dark': '#570563', //default to '#ea5300',
          'light': '#772583', //default to '#ff7913',
        },
        'ch-text': {
          DEFAULT: '#111827',
          'primary': '#fff',
          'secondary': '#fff',
          'warning': '#111827',
          'success': '#fff',
          'error': '#fff',
          'info': '#fff',
        },
        'ch-support-1': 'rgba(46, 117, 163, 0.08)',
        'ch-support-2': '#f5f8fa',
        'ch-support-3': 'rgba(46, 117, 163, 0.5)',
        'ch-support-4': '#244d67',
        'ch-support-5': '#e06410',
        'ch-support-6': '#faf7f5',
        'ch-warning': {
          DEFAULT: '#eddf0e',
          'dark': '#dacc0a',
          'light': '#ffef1f',
        },
        'ch-success': {
          DEFAULT: '#a4dc2d',
          'dark': '#91c91a',
          'light': '#b7ef3f',
        },
        'ch-error': {
          DEFAULT: '#ef3e3e',
          'dark': '#dc2b2b',
          'light': '#ff4f4f',
        },
        'ch-info': {
          DEFAULT: '#3e9aef',
          'dark': '#2b87dc',
          'light': '#4fadff',
        }
      }
    },
  },
  variants: {
    extend: {
      opacity: ['disabled'],
    },
  },
  plugins: [
    require('@tailwindcss/line-clamp'),
    require('@tailwindcss/forms'),
    require("@tailwindcss/typography")
  ],
}
