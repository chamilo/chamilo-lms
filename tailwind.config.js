const colors = require("tailwindcss/colors");

// from tailwind youtube channel https://youtu.be/MAtaT8BZEAo?t=1023
const colorWithOpacity = (variableName) => {
  return ({opacityValue}) => {
    if (opacityValue !== undefined) {
      return `rgba(var(${variableName}), ${opacityValue})`
    }
    return `rgb(var(${variableName}))`
  }
}

module.exports = {
  important: true,
  content: [
    "./assets/**/*.{js,vue}",
    "./public/main/**/*.{php,twig}",
    "./src/CoreBundle/Resources/views/**/*.html.twig",
  ],
  theme: {
    colors: {
      primary: {
        DEFAULT: colorWithOpacity("--color-primary-base"),
        gradient: colorWithOpacity("--color-primary-gradient"),
        bgdisabled: "#fafafa",
        borderdisabled: "#e4e9ed",
      },
      secondary: {
        DEFAULT: colorWithOpacity("--color-secondary-base"),
        gradient: colorWithOpacity("--color-secondary-gradient"),
        bgdisabled: '#e4e9ed',
        hover: "#d35e0f",
      },
      tertiary: {
        DEFAULT: colorWithOpacity("--color-tertiary-base"),
        gradient: colorWithOpacity("--color-tertiary-gradient"),
      },
      gray: {
        5: "#fcfcfc",
        10: "#fafafa",
        15: "#f7f8f9",
        20: "#edf0f2",
        25: "#e4e9ed",
        30: "rgba(0, 0, 0, 0.12)",
        50: "#a2a6b0",
        90: "#333333",
      },
      support: {
        1: "rgba(46, 117, 163, 0.08)",
        2: "#f5f8fa",
        3: "rgba(46, 117, 163, 0.5)",
        4: "#244d67",
        5: "#e06410",
        6: "#faf7f5",
      },
      warning: "#f5ce01",
      success: {
        DEFAULT: colorWithOpacity("--color-success-base"),
        gradient: colorWithOpacity("--color-success-gradient"),
      },
      error: colorWithOpacity("--color-danger-base"),
      info: "#0d7bfd",

      white: colors.white,
      black: colors.black,
      transparent: colors.transparent,
      current: colors.current,

      fontdisabled: '#a2a6b0',
    },
    extend: {
      fontFamily: {
        sans: ["Helvetica Neue", "Helvetica", "Arial", "sans-serif"],
      },
      fontSize: {
        "body-1": ["16px", "24px"],
        "body-2": ["14px", "16px"],
        caption: ["13px", "16px"],
        tiny: ["11px", "16px"],
      },
    },
  },
  corePlugins: {
    aspectRatio: true,
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/typography"),
  ],
};
