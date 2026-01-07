import { createApp, watch } from "vue"
import App from "./App.vue"
import i18n from "./i18n"
import router from "./router"
import store from "./store"
import axios from "axios"
import { createPinia } from "pinia"

// Services.
import courseCategoryService from "./services/coursecategory"
import documentsService from "./services/documents"
import courseService from "./services/course"
import personalFileService from "./services/personalfile"
import resourceLinkService from "./services/resourcelink"
import resourceNodeService from "./services/resourcenode"
import messageService from "./services/message"
import messageRelUserService from "./services/messagereluser"
import messageTagService from "./services/messagetag"
import userService from "./services/user"
import userGroupService from "./services/usergroup"
import userRelUserService from "./services/userreluser"
import calendarEventService from "./services/ccalendarevent"
import toolIntroService from "./services/ctoolintro"
import pageService from "./services/page"
import sessionService from "./services/session"
import socialPostService from "./services/socialpost"

import makeCrudModule from "./store/modules/crud"
import installHttpErrors from "./plugins/httpErrors"
import uxModule from "./store/modules/ux"

import VueFlatPickr from "vue-flatpickr-component"
import "flatpickr/dist/flatpickr.css"
import "@mdi/font/css/materialdesignicons.css"

// Prime
import PrimeVue from "primevue/config"
import DataView from "primevue/dataview"
import Select from "primevue/select"
import Toolbar from "primevue/toolbar"

import Dialog from "primevue/dialog"
import ConfirmDialog from "primevue/confirmdialog"
import InputText from "primevue/inputtext"
import Button from "primevue/button"
import Column from "primevue/column"
import ColumnGroup from "primevue/columngroup"
import ToastService from "primevue/toastservice"
import ConfirmationService from "primevue/confirmationservice"
import BaseAppLink from "./components/basecomponents/BaseAppLink.vue"

// import 'primeflex/primeflex.css';
import "primeicons/primeicons.css"
import Alpine from "alpinejs"
import { ENTRYPOINT } from "./config/entrypoint"

// @todo move in a file:
store.registerModule(
  "course",
  makeCrudModule({
    service: courseService,
  }),
)

store.registerModule(
  "coursecategory",
  makeCrudModule({
    service: courseCategoryService,
  }),
)

store.registerModule(
  "documents",
  makeCrudModule({
    service: documentsService,
  }),
)

store.registerModule(
  "ccalendarevent",
  makeCrudModule({
    service: calendarEventService,
  }),
)

store.registerModule(
  "ctoolintro",
  makeCrudModule({
    service: toolIntroService,
  }),
)

store.registerModule(
  "page",
  makeCrudModule({
    service: pageService,
  }),
)

store.registerModule(
  "session",
  makeCrudModule({
    service: sessionService,
  }),
)

store.registerModule(
  "personalfile",
  makeCrudModule({
    service: personalFileService,
  }),
)

store.registerModule(
  "resourcelink",
  makeCrudModule({
    service: resourceLinkService,
  }),
)

store.registerModule(
  "resourcenode",
  makeCrudModule({
    service: resourceNodeService,
  }),
)

store.registerModule(
  "message",
  makeCrudModule({
    service: messageService,
  }),
)

store.registerModule(
  "messagereluser",
  makeCrudModule({
    service: messageRelUserService,
  }),
)

store.registerModule(
  "messagetag",
  makeCrudModule({
    service: messageTagService,
  }),
)

store.registerModule(
  "userreluser",
  makeCrudModule({
    service: userRelUserService,
  }),
)

store.registerModule(
  "user",
  makeCrudModule({
    service: userService,
  }),
)

store.registerModule(
  "usergroup",
  makeCrudModule({
    service: userGroupService,
  }),
)

store.registerModule(
  "socialpost",
  makeCrudModule({
    service: socialPostService,
  }),
)

store.registerModule("ux", uxModule)

// Vue setup.
const app = createApp(App)

app.use(ToastService)
app.use(ConfirmationService)
app.component("Dialog", Dialog)
app.component("ConfirmDialog", ConfirmDialog)
app.component("DataView", DataView)
app.component("Dropdown", Select)
app.component("InputText", InputText)
app.component("Button", Button)
app.component("Column", Column)
app.component("ColumnGroup", ColumnGroup)
app.component("Toolbar", Toolbar)
app.component("BaseAppLink", BaseAppLink)

app.config.globalProperties.axios = axios
app.config.globalProperties.window = window

window.Alpine = Alpine
Alpine.start()

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
  .use(VueFlatPickr)
  .use(router)
  .use(store)
  .use(pinia)
  .use(i18n)

function applyPrimeLocale() {
  const t = i18n.global.t
  const cfg = app.config.globalProperties.$primevue?.config
  if (!cfg) return
  cfg.locale = {
    ...(cfg.locale ?? {}),
    accept: t("Yes"),
    reject: t("No"),
    emptyMessage: t("No available options"),
    emptyFilterMessage: t("No available options"),
  }
}
applyPrimeLocale()

try {
  const loc = i18n.global.locale
  if (loc && typeof loc === "object" && "value" in loc) {
    watch(loc, applyPrimeLocale)
  }
} catch {}

installHttpErrors({
  store,
  t: (key, params) => i18n.global.t(key, params),
  on401: (err) => console.warn("Unauthorized", err?.response?.data?.error || "Unauthorized"),
  on403: (msg) => console.info("Forbidden shown:", msg),
  on500: (err) => console.error("Server error", err?.response?.data?.detail || "Server error"),
})

// Add cid/sid/gid automatically to every axios request to /api/*
axios.interceptors.request.use((config) => {
  const sp = new URLSearchParams(window.location.search)
  const pageCid = sp.get("cid")
  const pageSid = sp.get("sid")
  const pageGid = sp.get("gid")

  if (!pageCid && !pageSid && !pageGid) return config

  // Only for API calls (ENTRYPOINT usually ends with /api)
  const url = config.url || ""
  const isApiCall = url.includes("/api/") || url.startsWith("/api/") || url.startsWith(ENTRYPOINT)
  if (!isApiCall) return config

  // Ensure params is an object
  config.params = { ...(config.params || {}) }

  // Inject/override context, especially if request has cid=0
  if (
    pageCid &&
    (config.params.cid === undefined ||
      config.params.cid === null ||
      config.params.cid === "" ||
      String(config.params.cid) === "0")
  ) {
    config.params.cid = pageCid
  }
  if (pageSid && (config.params.sid === undefined || config.params.sid === null || config.params.sid === "")) {
    config.params.sid = pageSid
  }
  if (pageGid && (config.params.gid === undefined || config.params.gid === null || config.params.gid === "")) {
    config.params.gid = pageGid
  }

  return config
})

app.mount("#app")
