module.exports = {
  purge: [],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      fontFamily: {
        sans: ['Helvetica Neue', 'Helvetica', 'Arial', 'sans-serif']
      },
      colors: {
        'ch-primary': '#2e75a3',
        'ch-secondary': '#fd6600',
        'ch-grayscale-100': '#000',
        'ch-grayscale-90': '#333',
        'ch-grayscale-50': '#a2a6b0',
        'ch-grayscale-30': 'rgba(0, 0, 0, 0.12)',
        'ch-grayscale-25': '#e4e9ed',
        'ch-grayscale-20': '#edf0f2',
        'ch-grayscale-15': '#f7f8f9',
        'ch-grayscale-10': '#fafafa',
        'ch-grayscale-5': 'rgba(250, 250, 250, 0.5)',
        'ch-grayscale-0': '#fff',
        'ch-support-1': 'rgba(46, 117, 163, 0.08)',
        'ch-support-2': '#f5f8fa',
        'ch-support-3': 'rgba(46, 117, 163, 0.5)',
        'ch-support-4': '#244d67',
        'ch-support-5': '#e06410',
        'ch-support-6': '#faf7f5',
        'ch-warning': '#eddf0e',
        'ch-success': '#a4dc2d',
        'ch-error': '#ef3e3e',
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
