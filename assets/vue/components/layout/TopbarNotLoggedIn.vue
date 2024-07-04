<template>
  <div class="app-topbar">
    <Menubar :model="menuItems">
      <template #start>
        <PlatformLogo />
      </template>
    </Menubar>
  </div>
</template>

<script setup>
import { computed } from "vue"
import Menubar from "primevue/menubar"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useLocale } from "../../composables/locale"
import PlatformLogo from "./PlatformLogo.vue"

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
    url: router.resolve({ name: "Index" }).href,
  },
  {
    label: t("FAQ"),
    url: router.resolve({ name: "Faq" }).href,
  },
  {
    label: t("Registration"),
    url: "/main/auth/inscription.php",
  },
  {
    label: t("Demo"),
    url: router.resolve({ name: "Demo" }).href,
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
</script>
