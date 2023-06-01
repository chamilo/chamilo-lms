<template>
  <div class="flex flex-col justify-start">
    <div class="mb-4">
      <Dashboard
        :uppy="uppy"
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          //metaFields: [{id: 'name', name: 'Name', placeholder: 'file name'}],
          proudlyDisplayPoweredByUppy: false,
          width: '100%',
          height: '350px',
        }"
      />
    </div>

    <BaseButton
      :label="showAdvancedSettingsLabel"
      class="mr-auto mb-4"
      type="black"
      icon="cog"
      @click="advancedSettingsClicked"
    />

    <div v-if="showAdvancedSettings">
      <div class="flex flex-row mb-2">
        <label class="font-semibold w-28">{{ t('Options') }}:</label>
        <BaseCheckbox
          id="uncompress"
          v-model="isUncompressZipEnabled"
          :label="t('Uncompres zip')"
          name="uncompress"
        />
      </div>

      <div class="flex flex-row mb-2">
        <label class="font-semibold w-28">{{ t('If file exists') }}:</label>
        <BaseRadioButtons
          v-model="fileExistsOption"
          name="file-exists-options"
          initial-value="rename"
          :options="[
          {label: t('Do nothing'), value: 'nothing'},
          {label: t('Overwrite the existing file'), value: 'overwrite'},
          {label: t('Rename the uploaded file if it exists'), value: 'rename'},
        ]"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import {computed, ref} from 'vue'
import '@uppy/core/dist/style.css'
import '@uppy/dashboard/dist/style.css'
import '@uppy/image-editor/dist/style.css'

import Uppy from '@uppy/core'
import Webcam from '@uppy/webcam'
import {Dashboard} from '@uppy/vue'
import {useRoute, useRouter} from "vue-router";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility";
import {ENTRYPOINT} from "../../config/entrypoint";
import {useCidReq} from "../../composables/cidReq";
import {useUpload} from "../../composables/upload";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import {useI18n} from "vue-i18n";
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue";
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue";

const XHRUpload = require('@uppy/xhr-upload');
const ImageEditor = require('@uppy/image-editor');

const route = useRoute();
const router = useRouter();
const {gid, sid, cid} = useCidReq()
const {onCreated, onError} = useUpload()
const {t} = useI18n()

const parentResourceNodeId = ref(Number(route.params.node))
const resourceLinkList = ref(JSON.stringify([{
  gid,
  sid,
  cid,
  visibility: RESOURCE_LINK_PUBLISHED,
}]))
const showAdvancedSettings = ref(false)
const isUncompressZipEnabled = ref(false)
const fileExistsOption = ref('')


const showAdvancedSettingsLabel = computed(() => {
  if (showAdvancedSettings.value) {
    return t('Hide advanced settings')
  } else {
    return t('Show advanced settings')
  }
})

let uppy = ref();
uppy.value = new Uppy()
  .use(Webcam)
  .use(ImageEditor, {
    cropperOptions: {
      viewMode: 1,
      background: false,
      autoCropArea: 1,
      responsive: true
    },
    actions: {
      revert: true,
      rotate: true,
      granularRotate: true,
      flip: true,
      zoomIn: true,
      zoomOut: true,
      cropSquare: true,
      cropWidescreen: true,
      cropWidescreenVertical: true
    }
  })
  .use(
    XHRUpload, {
      endpoint: ENTRYPOINT + 'documents',
      formData: true,
      fieldName: 'uploadFile',
    }
  )
  .on('upload-success', (item, response) => {
    onCreated(response.body)
    router.back()
  })

uppy.value.setMeta({
  filetype: 'file',
  parentResourceNodeId: parentResourceNodeId.value,
  resourceLinkList: resourceLinkList.value,
});

const advancedSettingsClicked = () => {
  showAdvancedSettings.value = !showAdvancedSettings.value
}
</script>
