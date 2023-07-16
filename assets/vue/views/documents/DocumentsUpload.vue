<template>
  <div class="flex flex-col justify-start">
    <div class="mb-4">
      <Dashboard
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          //metaFields: [{id: 'name', name: 'Name', placeholder: 'file name'}],
          proudlyDisplayPoweredByUppy: false,
          width: '100%',
          height: '350px',
        }"
        :uppy="uppy"
      />
    </div>

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <div class="flex flex-row mb-2">
        <label class="font-semibold w-28">{{ t("Options") }}:</label>
        <BaseCheckbox id="uncompress" v-model="isUncompressZipEnabled" :label="t('Uncompres zip')" name="uncompress" />
      </div>

      <div class="flex flex-row mb-2">
        <label class="font-semibold w-28">{{ t("If file exists") }}:</label>
        <BaseRadioButtons
          v-model="fileExistsOption"
          :options="[
            { label: t('Do nothing'), value: 'nothing' },
            { label: t('Overwrite the existing file'), value: 'overwrite' },
            { label: t('Rename the uploaded file if it exists'), value: 'rename' },
          ]"
          initial-value="rename"
          name="file-exists-options"
        />
      </div>
    </BaseAdvancedSettingsButton>
  </div>
</template>

<script setup>
import { ref } from "vue";
import "@uppy/core/dist/style.css";
import "@uppy/dashboard/dist/style.css";
import "@uppy/image-editor/dist/style.css";

import Uppy from "@uppy/core";
import Webcam from "@uppy/webcam";
import { Dashboard } from "@uppy/vue";
import { useRoute, useRouter } from "vue-router";
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility";
import { ENTRYPOINT } from "../../config/entrypoint";
import { useCidReq } from "../../composables/cidReq";
import { useUpload } from "../../composables/upload";
import { useI18n } from "vue-i18n";
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue";
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue";
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue";

const XHRUpload = require("@uppy/xhr-upload");
const ImageEditor = require("@uppy/image-editor");

const route = useRoute();
const router = useRouter();
const { gid, sid, cid } = useCidReq();
const { onCreated, onError } = useUpload();
const { t } = useI18n();

const showAdvancedSettings = ref(false);

const parentResourceNodeId = ref(Number(route.params.node));
const resourceLinkList = ref(
  JSON.stringify([
    {
      gid,
      sid,
      cid,
      visibility: RESOURCE_LINK_PUBLISHED,
    },
  ])
);
const isUncompressZipEnabled = ref(false);
const fileExistsOption = ref("");

let uppy = ref();
uppy.value = new Uppy()
  .use(Webcam)
  .use(ImageEditor, {
    cropperOptions: {
      viewMode: 1,
      background: false,
      autoCropArea: 1,
      responsive: true,
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
      cropWidescreenVertical: true,
    },
  })
  .use(XHRUpload, {
    endpoint: ENTRYPOINT + "documents",
    formData: true,
    fieldName: "uploadFile",
  })
  .on("upload-success", (item, response) => {
    onCreated(response.body);
    router.back();
  });

uppy.value.setMeta({
  filetype: "file",
  parentResourceNodeId: parentResourceNodeId.value,
  resourceLinkList: resourceLinkList.value,
});
</script>
