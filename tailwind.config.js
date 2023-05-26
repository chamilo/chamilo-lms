const colors = require("tailwindcss/colors");

module.exports = {
  important: true,
  content: [
    "./assets/**/*.{js,vue}",
    "./public/main/**/*.{php,twig}",
    "./src/CoreBudnle/Resources/views/**/*.html.twig",
  ],
  theme: {
    colors: {
      primary: {
        DEFAULT: "#2e75a3",
        gradient: "#9cc2da",
      },
      secondary: {
        DEFAULT: "#f37e2f",
        gradient: "#e06410",
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
      success: "#77aa0c",
      error: "#df3b3b",
      info: "#0d7bfd",

      white: colors.white,
      black: colors.black,
      transparent: colors.transparent,
      current: colors.current,
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
      gridTemplateColumns: {
        "course-tools": "repeat(auto-fit, 120px)",
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
