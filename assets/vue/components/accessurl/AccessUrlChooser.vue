<script setup>
import { useI18n } from "vue-i18n"
import Dialog from "primevue/dialog"
import { useAccessUrlChooser } from "../../composables/accessurl/accessUrlChooser"

const { t } = useI18n()

const { visible, isLoading, accessUrls, doRedirectToPortal } = useAccessUrlChooser()
</script>

<template>
  <Dialog
    v-model:visible="visible"
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
        @click="doRedirectToPortal(accessUrl.url)"
      >
        {{ accessUrl.url }}
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
