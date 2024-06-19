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
import { useRoute, useRouter } from "vue-router"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

function setLanguage(event) {
  const { isoCode } = event.item

  const newUrl = router.resolve({
    path: route.path,
    query: {
      _locale: isoCode,
    },
  })

  window.location.href = newUrl.fullPath
}

const languages = window.languages || [{ originalName: "English", isocode: "en" }]
const languageItems = languages.map((language) => ({
  label: language.originalName,
  isoCode: language.isocode,
  command: setLanguage,
}))

const currentLanguage = languages.find((language) => document.querySelector("html").lang === language.isocode)

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
    label: currentLanguage ? currentLanguage.originalName : "English",
    items: languageItems,
  },
])

const headerLogo = headerLogoPath
</script>
