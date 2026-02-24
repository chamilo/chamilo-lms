<template>
  <div v-if="item">
    <p
      class="text-body-1 font-semibold"
      v-text="item.title"
    />

    <div class="flex flex-row gap-4">
      <div class="w-2/3 flex justify-center">
        <div class="w-4/5">
          <div v-html="item.content" />
        </div>
      </div>

      <div class="w-1/3">
        <dl class="grid grid-cols-2">
          <dt
            v-text="t('Author')"
            class="font-semibold"
          />
          <dl v-text="item.creator.username" />

          <dt v-text="t('Language')" class="font-semibold" />
          <dl>{{ languageLabel }}</dl>

          <dt
            v-text="t('Enabled')"
            class="font-semibold"
          />
          <dl v-text="t(item.enabled ? 'Yes' : 'No')" />

          <dt
            v-text="t('Category')"
            class="font-semibold"
          />
          <dl v-text="item.category.title" />

          <dt
            v-text="t('Created at')"
            class="font-semibold"
          />
          <dl v-text="item.createdAt ? relativeDatetime(item.createdAt) : ''" />

          <dt
            v-text="t('Updated at')"
            class="font-semibold"
          />
          <dl v-text="item.updatedAt ? relativeDatetime(item.updatedAt) : ''" />
        </dl>
      </div>
    </div>
  </div>

  <Loading :visible="isLoading" />
</template>

<script setup>
import Loading from "../../components/Loading.vue"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import { useConfirm } from "primevue/useconfirm"
import { useRoute, useRouter } from "vue-router"
import { inject, ref, watch } from "vue"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"
import pageService from "../../services/page"
import { useNotification } from "../../composables/notification"
import { useLocale } from "../../composables/locale"

const { t } = useI18n()
const { relativeDatetime } = useFormatDate()

const securityStore = useSecurityStore()
const { isAdmin } = storeToRefs(securityStore)
const route = useRoute()
const router = useRouter()

const confirm = useConfirm()
const notification = useNotification()

const isLoading = ref(true)
const item = ref()

const layoutMenuItems = inject("layoutMenuItems")

const { getLanguageName, fetchLanguageNameFromApi } = useLocale()
const languageLabel = ref("-")

watch(item, (val) => {
  if (!val) return
  const iso = val.locale
  languageLabel.value = getLanguageName(iso)
  fetchLanguageNameFromApi(iso)
    .then((name) => { if (name) languageLabel.value = name })
    .catch(() => {})
})

watch(item, () => {
  if (!isAdmin.value) {
    return
  }

  layoutMenuItems.value = [
    {
      label: "Edit page",
      to: {
        name: "PageUpdate",
        query: { id: item.value["@id"] },
      },
    },

    {
      label: "Delete page",
      command() {
        confirm.require({
          header: "Confirmation",
          message: "Are you sure you want to delete it?",
          async accept() {
            await pageService.del(item.value)

            await router.push({ name: "PageList" })
          },
        })
      },
    },
  ]
})

pageService
  .find(route.query.id)
  .then((response) => response.json())
  .then((json) => (item.value = json))
  .catch((e) => notification.showErrorNotification(e))
  .finally(() => (isLoading.value = false))
</script>
