<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import Dialog from "primevue/dialog"
import { findUserActivePortals } from "../../services/accessurlService"
import { useSecurityStore } from "../../store/securityStore"
import { useNotification } from "../../composables/notification"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const securityStore = useSecurityStore()
const { t } = useI18n()
const { showErrorNotification } = useNotification()

const isLoading = ref(true)
const accessUrls = ref([])

if (securityStore.showAccessUrlChooser) {
  findUserActivePortals(securityStore.user["@id"])
    .then((items) => {
      accessUrls.value = items

      if (1 === items.length) {
        window.location.href = items[0].url
      }
    })
    .catch((error) => showErrorNotification(error))
    .finally(() => {
      if (1 !== accessUrls.value.length) {
        isLoading.value = false
      }
    })
}
</script>

<template>
  <Dialog
    v-model:visible="securityStore.showAccessUrlChooser"
    :modal="true"
    :closable="false"
    :header="t('Access URL')"
    :style="{ width: '50vw' }"
  >
    <i
      v-if="isLoading"
      class="pi pi-spin pi-spinner"
    />
    <div
      v-else-if="accessUrls.length"
      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
    >
      <div
        v-for="accessUrl in accessUrls"
        :key="accessUrl.id"
        class="text-center"
      >
        <BaseAppLink :url="accessUrl.url">{{ accessUrl.url }}</BaseAppLink>
        <p
          v-if="accessUrl.description"
          v-text="accessUrl.description"
        />
      </div>
    </div>
    <p
      v-else
      v-text="t('No active access URLs found')"
    />
  </Dialog>
</template>
