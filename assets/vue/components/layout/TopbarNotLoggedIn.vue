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
import { ref } from "vue"
import Menubar from "primevue/menubar"
import headerLogoPath from "../../../../assets/css/themes/chamilo/images/header-logo.svg"
import { useI18n } from "vue-i18n"

const { t, locale } = useI18n()

function setLanguage(event) {
  const { label, isoCode } = event.item

  const selectorIndex = menuItems.value.findIndex((item) => "language_selector" === item.key)

  menuItems.value[selectorIndex] ? (menuItems.value[selectorIndex].label = label) : null

  locale.value = isoCode
}

const languageItems = window.languages.map((language) => ({
  label: language.originalName,
  isoCode: language.isocode,
  command: setLanguage,
}))

const currentLanguage = window.languages.find((language) => document.querySelector("html").lang === language.isocode)

const menuItems = ref([
  {
    label: t("Home"),
    to: { name: "Index" },
  },
  {
    label: t("FAQ"),
    to: { name: "Faq" },
  },
  {
    label: t("Registration"),
    url: "/main/auth/inscription.php",
  },
  {
    label: t("Demo"),
    to: { name: "Demo" },
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
