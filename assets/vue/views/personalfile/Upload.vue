<template>
  <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
    <BaseToolbar class="border-b border-gray-25 bg-support-2">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-gray-90">
            {{ uploadTitle }}
          </h2>
          <p class="mt-1 text-caption text-gray-50">
            {{ uploadHelpText }}
          </p>
        </div>

        <BaseButton
          :label="t('Back')"
          icon="back"
          type="black"
          @click="goBack"
        />
      </div>
    </BaseToolbar>

    <div class="p-4 md:p-5">
      <div class="mb-4 flex flex-wrap items-center gap-2">
        <span
          class="inline-flex rounded-full bg-support-1 px-3 py-1 text-tiny font-semibold uppercase tracking-wide text-support-4"
        >
          {{ pickerTypeLabel }}
        </span>

        <span
          class="inline-flex rounded-full bg-gray-15 px-3 py-1 text-tiny font-semibold uppercase tracking-wide text-gray-90"
        >
          {{ t("Personal files") }}
        </span>
      </div>

      <div class="rounded-2xl border border-gray-25 bg-white p-3">
        <Dashboard
          :plugins="['Webcam', 'ImageEditor']"
          :props="{
            proudlyDisplayPoweredByUppy: false,
            width: '100%',
            height: '380px',
          }"
          :uppy="uppy"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from "vuex"
import { createHelpers } from "vuex-map-fields"
import UploadMixin from "../../mixins/UploadMixin"
import { computed, onBeforeUnmount, ref } from "vue"

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"

import Uppy from "@uppy/core"
import Webcam from "@uppy/webcam"
import { Dashboard } from "@uppy/vue"
import XHRUpload from "@uppy/xhr-upload"
import ImageEditor from "@uppy/image-editor"
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const servicePrefix = "PersonalFile"

const { mapFields } = createHelpers({
  getterType: "personalfile/getField",
  mutationType: "personalfile/updateField",
})

export default {
  name: "PersonalFileUploadFile",
  servicePrefix,
  components: {
    Dashboard,
    BaseToolbar,
    BaseButton,
  },
  setup() {
    const parentResourceNodeId = ref(null)
    const route = useRoute()
    const router = useRouter()
    const securityStore = useSecurityStore()
    const { t } = useI18n()

    const { user, isAuthenticated, isAdmin } = storeToRefs(securityStore)

    function normalizePickerType(raw) {
      const value = String(raw || "")
        .trim()
        .toLowerCase()

      if (value === "images" || value === "image") return "images"
      if (value === "media" || value === "video" || value === "audio") return "media"
      return "files"
    }

    function getAllowedFileTypes(type) {
      if (type === "images") {
        return ["image/*"]
      }

      if (type === "media") {
        return ["video/*", "audio/*"]
      }

      return null
    }

    const pickerType = computed(() => normalizePickerType(route.query.type))
    const pickerTypeLabel = computed(() => {
      if (pickerType.value === "images") return t("Images")
      if (pickerType.value === "media") return t("Media")
      return t("Files")
    })

    const uploadTitle = computed(() => {
      if (pickerType.value === "images") return t("Upload image")
      if (pickerType.value === "media") return t("Upload media")
      return t("Upload file")
    })

    const uploadHelpText = computed(() => {
      if (pickerType.value === "images") {
        return t("Only image files are allowed in this upload screen.")
      }

      if (pickerType.value === "media") {
        return t("Only audio and video files are allowed in this upload screen.")
      }

      return t("Upload files to your personal storage.")
    })

    parentResourceNodeId.value = user.value.resourceNode["id"]

    if (route.params.node) {
      parentResourceNodeId.value = Number(route.params.node)
    }

    const uppy = ref()
    uppy.value = new Uppy({ autoProceed: false })
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
        endpoint: "/api/personal_files",
        formData: true,
        fieldName: "uploadFile",
      })

    uppy.value.setMeta({
      filetype: "file",
      parentResourceNodeId: parentResourceNodeId.value,
    })

    uppy.value.setOptions({
      restrictions: {
        allowedFileTypes: getAllowedFileTypes(pickerType.value),
      },
    })

    function goBack() {
      const returnTo = String(route.query.returnTo || "")

      if (returnTo && router.hasRoute(returnTo)) {
        router.push({
          name: returnTo,
          params: { node: parentResourceNodeId.value },
          query: { ...route.query },
        })
        return
      }

      router.push({
        name: "PersonalFileList",
        params: { node: parentResourceNodeId.value },
        query: { ...route.query },
      })
    }

    uppy.value.on("complete", () => {
      goBack()
    })

    uppy.value.on("restriction-failed", (_file, error) => {
      console.warn("[PERSONAL FILE UPLOAD] Restriction failed", error?.message || error)
    })

    onBeforeUnmount(() => {
      try {
        uppy.value?.close()
      } catch {
        // Ignore Uppy close errors.
      }
    })

    return {
      uppy,
      currentUser: user,
      isAdmin,
      isAuthenticated,
      pickerType,
      pickerTypeLabel,
      uploadTitle,
      uploadHelpText,
      goBack,
      t,
    }
  },
  mixins: [UploadMixin],
  computed: {
    ...mapFields(["error", "isLoading", "created", "violations"]),
  },
  methods: {
    ...mapActions("personalfile", ["uploadMany", "createFile"]),
  },
}
</script>
