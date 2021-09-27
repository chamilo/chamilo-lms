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
          DEFAULT: '#2e75a3',
          'dark': '#1c6391',
          'light': '#9cc2da',
        },
        'ch-secondary': {
          DEFAULT: '#fd6600',
          'dark': '#ea5300',
        },
        'ch-support-1': 'rgba(46, 117, 163, 0.08)',
        'ch-support-2': '#f5f8fa',
        'ch-support-3': 'rgba(46, 117, 163, 0.5)',
        'ch-support-4': '#244d67',
        'ch-support-5': '#e06410',
        'ch-support-6': '#faf7f5',
        'ch-warning': '#eddf0e',
        'ch-success': '#a4dc2d',
        'ch-error': {
          DEFAULT: '#ef3e3e',
          'dark': '#dc2b2b',
        },
        'ch-info': '#3e9aef'
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
