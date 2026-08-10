import { createApp } from "vue"
import { createPinia } from "pinia"
import AppInstaller from "./AppInstaller"
import PrimeVue from "primevue/config"
import i18n, { i18nReady } from "./i18n"

const app = createApp(AppInstaller)

const pinia = createPinia()

app
  .use(PrimeVue, {
    ripple: false,
    theme: {
      options: {
        cssLayer: {
          name: "primevue",
          order: "app-styles, primevue",
        },
      },
    },
  })
  .use(i18n)
  .use(pinia)

// Locale messages are loaded on demand (see i18n.js); wait for the boot
// locale before mounting so templates don't flash untranslated keys.
i18nReady.then(() => {
  app.mount("#app")
})
