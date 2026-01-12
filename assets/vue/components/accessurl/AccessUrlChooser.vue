<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import Dialog from "primevue/dialog"
import { useAccessUrlChooser } from "../../composables/accessurl/accessUrlChooser"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()

const { loadComponent, isLoading, accessUrls, doRedirectToPortal } = useAccessUrlChooser()

const visible = ref(loadComponent.value)
</script>

<template>
  <Dialog
    v-model:visible="visible"
    :closable="securityStore.isAdmin"
    :close-on-escape="securityStore.isAdmin"
    :draggable="false"
    :header="t('Access URL')"
    :style="{ width: '60vw' }"
    modal
  >
    <i
      v-if="isLoading"
      class="pi pi-spin pi-spinner"
    />
    <div
      v-else
      class="space-y-4 text-center"
    >
      <div v-if="1 === accessUrls.length">
        <p>{{ t("You only have access to the URL {0}", [accessUrls[0].url]) }}</p>
        <p v-text="t('You will therefore be automatically redirected to this URL.')" />
      </div>
      <div v-else-if="accessUrls.length > 1">
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

      <p
        v-if="securityStore.isAdmin"
        v-text="t('You can close this message to continue.')"
      />
    </div>
  </Dialog>
</template>
