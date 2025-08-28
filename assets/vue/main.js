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

import VueFlatPickr from "vue-flatpickr-component"
import "flatpickr/dist/flatpickr.css"
import "@mdi/font/css/materialdesignicons.css"

// Prime
import PrimeVue from "primevue/config"
import DataView from "primevue/dataview"
import DataTable from "primevue/datatable"
import Select from "primevue/select"
import Toolbar from "primevue/toolbar"

import Dialog from "primevue/dialog"
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

// Vue setup.
const app = createApp(App)

app.use(ToastService)
app.use(ConfirmationService)
app.component("Dialog", Dialog)
app.component("DataView", DataView)
app.component("DataTable", DataTable)
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

app.mount("#app")
