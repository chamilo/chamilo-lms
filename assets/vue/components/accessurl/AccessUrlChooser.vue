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
    :draggable="false"
    :header="t('Access URL')"
    :style="{ width: '60vw' }"
  >
    <i
      v-if="isLoading"
      class="pi pi-spin pi-spinner"
    />
    <div
      class="space-y-4 text-center"
      v-else-if="1 === accessUrls.length"
    >
      <p>{{ t("You only have access to the URL %s", [accessUrls[0].url]) }}</p>
      <p v-text="t('You will therefore be automatically redirected to this URL.')" />
    </div>
    <div
      class="space-y-4 text-center"
      v-else-if="accessUrls.length > 1"
    >
      <p v-text="t('You have access to multiple URLs. Here is the list of your accesses.')" />
      <p v-text="t('Please click on the link below corresponding to the URL you wish to access.')" />
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="accessUrl in accessUrls"
          :key="accessUrl.id"
          class="text-center"
          @click="doRedirectToPortal(accessUrl.url)"
        >
          <span
            class="cursor-pointer"
            v-text="accessUrl.url"
          />
          <p
            v-if="accessUrl.description"
            v-text="accessUrl.description"
          />
        </div>
      </div>
    </div>
    <p
      v-else
      v-text="
        t(
          'You do not currently have access to any URL. Please ask the administrator to grant you access to the URL to which you belong.',
        )
      "
    />
  </Dialog>
</template>
