<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import PanelMenu from "primevue/panelmenu"
import Dropdown from "primevue/dropdown"
import SidebarLogin from "../SidebarLogin.vue"
import PageList from "../../../../assets/vue/components/page/PageList.vue"
import { useLocale } from "../../../../assets/vue/composables/locale"

const { t } = useI18n()
const router = useRouter()

const { languageList, currentLanguageFromList, reloadWithLocale } = useLocale()

const selectedCity = ref({ label: currentLanguageFromList.originalName, isoCode: currentLanguageFromList.isocode })

watch(selectedCity, ({ isoCode }) => reloadWithLocale(isoCode))

const languageItems = languageList.map((language) => ({
  label: language.originalName,
  isoCode: language.isocode,
}))

const menuItems = computed(() => [
  {
    label: t("Home"),
    command: async () => await router.push({ name: "Index" }),
  },
  {
    id: "login-header-item",
    label: t("Login"),
    items: [
      {
        id: "login-form-item",
      },
    ],
  },
  {
    label: t("Registration"),
    url: "/main/auth/inscription.php",
  },
  {
    label: t("Demo"),
    command: async () => await router.push({ name: "Demo" }),
  },
  {
    label: t("FAQ"),
    command: async () => await router.push({ name: "Faq" }),
  },
  {
    label: t("Contact"),
    url: "/contact",
  },
])

const sidebarIsOpen = ref(window.localStorage.getItem("sidebarIsOpen") === "true")

watch(
  sidebarIsOpen,
  (newValue) => {
    const appEl = document.querySelector("#app")

    window.localStorage.setItem("sidebarIsOpen", newValue.toString())

    appEl.classList.toggle("app--sidebar-inactive", !newValue)
  },
  {
    immediate: true,
  },
)
</script>

<template>
  <aside class="app-sidebar">
    <div class="app-sidebar__container">
      <h3 class="app-sidebar__top">
        {{ t("Menu") }}
      </h3>

      <div class="app-sidebar__panel flex flex-col">
        <div class="px-6 my-4">
          <Dropdown
            v-model="selectedCity"
            :options="languageItems"
            option-label="label"
          />
        </div>

        <PanelMenu :model="menuItems">
          <template #item="{ item, active }">
            <a
              v-if="item.id && 'login-header-item' === item.id"
              class="p-panelmenu-header-action"
              tabindex="-1"
              data-pc-section="headeraction"
            >
              <svg
                width="14"
                height="14"
                viewBox="0 0 14 14"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="p-icon p-submenu-icon"
                aria-hidden="true"
                data-pc-section="submenuicon"
              >
                <path
                  v-if="active"
                  d="M7.01744 10.398C6.91269 10.3985 6.8089 10.378 6.71215 10.3379C6.61541 10.2977 6.52766 10.2386 6.45405 10.1641L1.13907 4.84913C1.03306 4.69404 0.985221 4.5065 1.00399 4.31958C1.02276 4.13266 1.10693 3.95838 1.24166 3.82747C1.37639 3.69655 1.55301 3.61742 1.74039 3.60402C1.92777 3.59062 2.11386 3.64382 2.26584 3.75424L7.01744 8.47394L11.769 3.75424C11.9189 3.65709 12.097 3.61306 12.2748 3.62921C12.4527 3.64535 12.6199 3.72073 12.7498 3.84328C12.8797 3.96582 12.9647 4.12842 12.9912 4.30502C13.0177 4.48162 12.9841 4.662 12.8958 4.81724L7.58083 10.1322C7.50996 10.2125 7.42344 10.2775 7.32656 10.3232C7.22968 10.3689 7.12449 10.3944 7.01744 10.398Z"
                  fill="currentColor"
                />
                <path
                  v-else
                  d="M4.38708 13C4.28408 13.0005 4.18203 12.9804 4.08691 12.9409C3.99178 12.9014 3.9055 12.8433 3.83313 12.7701C3.68634 12.6231 3.60388 12.4238 3.60388 12.2161C3.60388 12.0084 3.68634 11.8091 3.83313 11.6622L8.50507 6.99022L3.83313 2.31827C3.69467 2.16968 3.61928 1.97313 3.62287 1.77005C3.62645 1.56698 3.70872 1.37322 3.85234 1.22959C3.99596 1.08597 4.18972 1.00371 4.3928 1.00012C4.59588 0.996539 4.79242 1.07192 4.94102 1.21039L10.1669 6.43628C10.3137 6.58325 10.3962 6.78249 10.3962 6.99022C10.3962 7.19795 10.3137 7.39718 10.1669 7.54416L4.94102 12.7701C4.86865 12.8433 4.78237 12.9014 4.68724 12.9409C4.59212 12.9804 4.49007 13.0005 4.38708 13Z"
                  fill="currentColor"
                />
              </svg>

              <span
                class="p-menuitem-text"
                data-pc-section="headerlabel"
                v-text="item.label"
              />
            </a>

            <SidebarLogin v-else-if="item.id && 'login-form-item' === item.id" />

            <a
              v-else
              class="p-panelmenu-header-action"
              tabindex="-1"
              data-pc-section="headeraction"
              :href="item.url ? item.url : undefined"
            >
              <span
                class="p-menuitem-text"
                data-pc-section="headerlabel"
                v-text="item.label"
              />
            </a>
          </template>
        </PanelMenu>

        <PageList category-title="footer_public" />
      </div>
    </div>
  </aside>

  <Teleport to=".app-topbar .p-menubar-end">
    <a
      class="app-sidebar__topbar-button item-button"
      tabindex="0"
      @click="sidebarIsOpen = !sidebarIsOpen"
    >
      <i class="mdi mdi-close" />
    </a>
  </Teleport>
</template>

<style scoped lang="scss">
#app {
  &.app--sidebar-inactive {
    .app-sidebar {
      @apply hidden
      sm:block sm:w-60;

      .p-panelmenu-header {
        > .p-panelmenu-header-content a {
          .p-submenu-icon {
            @apply block;
          }

          .p-menuitem-text {
            @apply block;
          }
        }
      }
    }
  }
}
</style>
