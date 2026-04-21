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
const PICKER_CONTEXT_STORAGE_KEY = "chamilo_filemanager_tinymce_picker_context"

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

    function getFileExtension(name) {
      const value = String(name || "").toLowerCase()
      const parts = value.split(".")
      return parts.length > 1 ? parts.pop() : ""
    }

    function matchesPickerType(file, type) {
      const mime = String(file?.type || "").toLowerCase()
      const ext = getFileExtension(file?.name || "")

      if (type === "images") {
        return (
          mime.startsWith("image/") || ["png", "jpg", "jpeg", "gif", "svg", "webp", "bmp", "tif", "tiff"].includes(ext)
        )
      }

      if (type === "media") {
        return (
          mime.startsWith("video/") ||
          mime.startsWith("audio/") ||
          ["mp4", "webm", "ogg", "mov", "avi", "mkv", "mp3", "wav", "m4a", "aac", "flac"].includes(ext)
        )
      }

      return true
    }

    function getInvalidTypeMessage(type) {
      if (type === "images") {
        return t("Only image files are allowed in this upload screen.")
      }

      if (type === "media") {
        return t("Only audio and video files are allowed in this upload screen.")
      }

      return t("This file type is not allowed in this upload screen.")
    }

    function readStoredPickerContext() {
      try {
        const raw = sessionStorage.getItem(PICKER_CONTEXT_STORAGE_KEY)
        if (!raw) return null

        const parsed = JSON.parse(raw)
        if (!parsed || typeof parsed !== "object") return null

        return parsed
      } catch {
        return null
      }
    }

    function writeStoredPickerContext(context) {
      try {
        sessionStorage.setItem(PICKER_CONTEXT_STORAGE_KEY, JSON.stringify(context))
      } catch {
        // Ignore storage errors.
      }
    }

    function sanitizeQuery(query) {
      const clean = {}

      Object.entries(query || {}).forEach(([key, value]) => {
        if (value === undefined || value === null || value === "") {
          return
        }

        clean[key] = value
      })

      return clean
    }

    function resolveParentNodeId() {
      const queryParent = Number(route.query.parentResourceNodeId || route.query.parent || 0)
      if (queryParent > 0) {
        return queryParent
      }

      const stored = readStoredPickerContext()
      const storedParent = Number(stored?.parentResourceNodeId || stored?.parent || 0)
      if (storedParent > 0) {
        return storedParent
      }

      const routeNode = Number(route.params.node || 0)
      if (routeNode > 0) {
        return routeNode
      }

      return Number(user.value?.resourceNode?.id || 0)
    }

    const pickerType = computed(() => {
      const stored = readStoredPickerContext()
      return normalizePickerType(route.query.type || stored?.type)
    })

    function persistCurrentPickerContext() {
      const currentQuery = {
        picker: String(route.query.picker || ""),
        cbId: String(route.query.cbId || ""),
        type: pickerType.value,
        tab: String(route.query.tab || "personalFiles"),
        returnTo: String(route.query.returnTo || "FileManagerList"),
        loadNode: String(route.query.loadNode || "1"),
        parentResourceNodeId: String(
          parentResourceNodeId.value || route.query.parentResourceNodeId || route.query.parent || "",
        ),
        parent: String(parentResourceNodeId.value || route.query.parent || route.query.parentResourceNodeId || ""),
      }

      if (currentQuery.picker === "tinymce") {
        writeStoredPickerContext(currentQuery)
      }
    }

    function buildReturnQuery() {
      const stored = readStoredPickerContext()

      const nextType = normalizePickerType(route.query.type || stored?.type)
      const nextPicker = String(route.query.picker || stored?.picker || "")
      const nextCbId = String(route.query.cbId || stored?.cbId || "")
      const nextTab = String(route.query.tab || stored?.tab || "personalFiles")
      const nextReturnTo = String(
        route.query.returnTo || stored?.returnTo || (nextPicker === "tinymce" ? "FileManagerList" : ""),
      )
      const nextLoadNode = String(route.query.loadNode || stored?.loadNode || "1")
      const nextParent = String(
        parentResourceNodeId.value ||
          route.query.parentResourceNodeId ||
          route.query.parent ||
          stored?.parentResourceNodeId ||
          stored?.parent ||
          "",
      )

      return sanitizeQuery({
        loadNode: nextLoadNode,
        type: nextType,
        picker: nextPicker || undefined,
        cbId: nextCbId || undefined,
        tab: nextTab,
        parentResourceNodeId: nextParent || undefined,
        parent: nextParent || undefined,
        returnTo: nextReturnTo || undefined,
      })
    }

    function resolveReturnRouteName() {
      const stored = readStoredPickerContext()
      const returnTo = String(route.query.returnTo || stored?.returnTo || "").trim()

      if (returnTo && router.hasRoute(returnTo)) {
        return returnTo
      }

      if (String(route.query.picker || stored?.picker || "") === "tinymce") {
        return "FileManagerList"
      }

      return "PersonalFileList"
    }

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

    parentResourceNodeId.value = resolveParentNodeId()
    persistCurrentPickerContext()

    const uppy = ref()
    uppy.value = new Uppy({
      autoProceed: false,
      onBeforeFileAdded(file) {
        if (!matchesPickerType(file, pickerType.value)) {
          const message = getInvalidTypeMessage(pickerType.value)

          console.warn("[PERSONAL FILE UPLOAD] Invalid file type for picker", {
            pickerType: pickerType.value,
            fileName: file?.name,
            mimeType: file?.type,
          })

          try {
            uppy.value?.info(message, "error", 4000)
          } catch {
            // Ignore Uppy info errors.
          }

          return false
        }

        return true
      },
    })
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
      parentResourceNode: `/api/resource_nodes/${parentResourceNodeId.value}`,
      "resourceNode.parent": parentResourceNodeId.value,
    })

    uppy.value.setOptions({
      restrictions: {
        allowedFileTypes: getAllowedFileTypes(pickerType.value),
      },
    })

    function goBack() {
      persistCurrentPickerContext()

      router.push({
        name: resolveReturnRouteName(),
        params: { node: parentResourceNodeId.value },
        query: buildReturnQuery(),
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
