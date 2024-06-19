<template>
  <div class="app-topbar">
    <Menubar :model="menuItems">
      <template #start>
        <img
          :src="headerLogo"
          alt="Chamilo LMS"
        />
      </template>
    </Menubar>
  </div>
</template>

<script setup>
import { computed } from "vue"
import Menubar from "primevue/menubar"
import headerLogoPath from "../../../../assets/css/themes/chamilo/images/header-logo.svg"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useLocale } from "../../composables/locale"

const { t } = useI18n()
const router = useRouter()

const { languageList, currentLanguageFromList, reloadWithLocale } = useLocale()

const languageItems = languageList.map((language) => ({
  label: language.originalName,
  isoCode: language.isocode,
  command: (event) => reloadWithLocale(event.item.isoCode),
}))

const menuItems = computed(() => [
  {
    label: t("Home"),
    command: async () => await router.push({ name: "Index" }),
  },
  {
    label: t("FAQ"),
    command: async () => await router.push({ name: "Faq" }),
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
    label: t("Contact"),
    url: "/contact",
  },
  {
    key: "language_selector",
    label: currentLanguageFromList.originalName,
    items: languageItems,
  },
])

const headerLogo = headerLogoPath
</script>
