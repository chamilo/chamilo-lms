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
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const router = useRouter()

const { languageList, currentLanguageFromList, reloadWithLocale } = useLocale()

const languageItems = languageList.map((language) => ({
  label: language.originalName,
  isoCode: language.isocode,
  command: (event) => reloadWithLocale(event.item.isoCode),
}))

const platformConfigStore = usePlatformConfig()
const allowRegistration = computed(() => "false" !== platformConfigStore.getSetting("registration.allow_registration"))

const menuItems = computed(() => {
  const items = [
    {
      label: t("Home"),
      url: router.resolve({ name: "Index" }).href,
    },
    {
      label: t("FAQ"),
      url: router.resolve({ name: "Faq" }).href,
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
  ]

  if (allowRegistration.value) {
    items.splice(2, 0, {
      label: t("Registration"),
      url: "/main/auth/registration.php",
    })
  }

  const showCatalogueLink =
    platformConfigStore.getSetting("course.course_catalog_published") !== "false" &&
    platformConfigStore.getSetting("course.catalog_hide_public_link") !== "true" &&
    platformConfigStore.getSetting("display.allow_students_to_browse_courses") !== "false"

  if (showCatalogueLink) {
    items.splice(1, 0, {
      label: t("Browse courses"),
      url: router.resolve({ name: "CatalogueCourses" }).href,
    })
  }

  console.log("Menu Items:", items)
  return items
})
</script>
