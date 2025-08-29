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
import { computed, onMounted, ref } from "vue"
import Menubar from "primevue/menubar"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useLocale } from "../../composables/locale"
import PlatformLogo from "./PlatformLogo.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import axios from "axios"

const { t } = useI18n()
const router = useRouter()
const platformConfigStore = usePlatformConfig()
const { languageList, currentLanguageFromList, reloadWithLocale } = useLocale()

const currentLocale = computed(() => currentLanguageFromList?.isocode || "en")
const languageItems = languageList.map(lang => ({
  label: lang.originalName,
  isoCode: lang.isocode,
  command: (e) => reloadWithLocale(e.item.isoCode),
}))

const allowRegistration = computed(
  () => platformConfigStore.getSetting("registration.allow_registration") !== "false"
)

const flags = ref({ home:false, faq:false, demo:false, contact:false })

async function resolveVisibility() {
  const { data } = await axios.get("/pages/_topbar-visibility", {
    params: { locale: currentLocale.value },
    headers: { "Cache-Control": "no-cache" },
  })
  flags.value = data
}
onMounted(resolveVisibility)

const menuItems = computed(() => {
  const items = []

  if (flags.value.home) items.push({ label: t("Home"), url: router.resolve({ name: "Index" }).href })

  const showCatalogueLink =
    platformConfigStore.getSetting("catalog.course_catalog_published") !== "false" &&
    platformConfigStore.getSetting("catalog.hide_public_link") !== "true" &&
    platformConfigStore.getSetting("catalog.allow_students_to_browse_courses") !== "false"

  if (showCatalogueLink) {
    items.push({ label: t("Browse courses"), url: router.resolve({ name: "CatalogueCourses" }).href })
  }

  if (flags.value.faq) items.push({ label: t("FAQ"), url: router.resolve({ name: "Faq" }).href })
  if (allowRegistration.value) items.push({ label: t("Registration"), url: "/main/auth/registration.php" })
  if (flags.value.demo) items.push({ label: t("Demo"), url: router.resolve({ name: "Demo" }).href })
  if (flags.value.contact) items.push({ label: t("Contact"), url: "/contact" })

  if (languageList.length > 1) {
    items.push({
      key: "language_selector",
      label: currentLanguageFromList?.originalName || t("Language"),
      items: languageItems,
    })
  }

  return items
})
</script>
