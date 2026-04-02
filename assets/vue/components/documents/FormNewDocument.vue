<template>
  <form @submit.prevent="$emit('submit')">
    <!-- Title -->
    <BaseInputTextWithVuelidate
      id="item_title"
      v-model.trim="item.title"
      :label="$t('Title')"
      :vuelidate-property="v$.item.title"
    />

    <!-- Content editor -->
    <BaseTinyEditor
      v-if="
        (item.resourceNode && item.resourceNode.firstResourceFile && item.resourceNode.firstResourceFile.text) ||
        item.newDocument
      "
      v-model="item.contentFile"
      :title="t('Content')"
      editor-id="item_content"
      :editor-config="tinyEditorConfig"
      required
    />

    <div
      v-if="aiEditorMessage"
      class="mt-2 rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900"
    >
      {{ aiEditorMessage }}
    </div>

    <!-- Advanced options: search / indexing -->
    <BaseAdvancedSettingsButton
      v-if="searchEnabled"
      v-model="showAdvancedSettings"
    >
      <div class="flex flex-row mb-2">
        <label class="font-semibold w-40">{{ $t("Options") }}:</label>
        <BaseCheckbox
          id="indexDocumentContent"
          v-model="item.indexDocumentContent"
          :label="$t('Index document content?')"
          name="indexDocumentContent"
        />
      </div>

      <!-- Specific search fields -->
      <div
        v-if="searchEnabled && searchFields.length > 0"
        class="flex flex-col gap-2 mt-3"
      >
        <div
          v-for="field in searchFields"
          :key="field.id"
          class="flex flex-row items-center gap-3"
        >
          <label
            class="font-semibold w-40"
            :for="`doc_search_field_${field.code}`"
          >
            {{ field.title }}:
          </label>

          <input
            :id="`doc_search_field_${field.code}`"
            :name="`searchFieldValues[${field.code}]`"
            v-model="item.searchFieldValues[field.code]"
            type="text"
            class="w-full border border-gray-300 rounded px-3 py-2"
            :placeholder="field.title"
            autocomplete="off"
          />
        </div>
      </div>
    </BaseAdvancedSettingsButton>

    <!-- Extra blocks injected by parent -->
    <slot></slot>

    <div class="flex justify-end mt-2">
      <BaseButton
        type="primary"
        icon="save"
        :label="$t('Save')"
        @click.prevent="$emit('submit')"
      />
    </div>

    <DocumentAiMediaDialog
      v-model:visible="showAiMediaDialog"
      :parent-resource-node-id="effectiveParentResourceNodeId"
      :selected-paragraph-text="selectedParagraphText"
      :course-title="courseContextTitle"
      :course-language="courseContextLanguage"
      :suggested-file-name="item.title"
      @accepted="handleAiMediaAccepted"
    />
  </form>
</template>

<script>
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { ref } from "vue"
import axios from "axios"
import { usePlatformConfig } from "../../store/platformConfig"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import DocumentAiMediaDialog from "./DocumentAiMediaDialog.vue"
import { ENTRYPOINT } from "../../config/entrypoint"

export default {
  name: "DocumentsForm",
  components: {
    BaseButton,
    BaseTinyEditor,
    BaseInputTextWithVuelidate,
    BaseAdvancedSettingsButton,
    BaseCheckbox,
    DocumentAiMediaDialog,
  },
  props: {
    values: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    initialValues: { type: Object, default: () => ({}) },
    searchEnabled: { type: Boolean, default: false },
  },
  setup() {
    const platformConfigStore = usePlatformConfig()
    const extraPlugins = ref("")
    const { t, locale } = useI18n()

    if ("true" === platformConfigStore.getSetting("editor.translate_html")) {
      extraPlugins.value = "translatehtml"
    }

    return { v$: useVuelidate(), extraPlugins, t, locale }
  },
  data() {
    return {
      contentFile: this.initialValues ? this.initialValues.contentFile : "",
      showAdvancedSettings: false,
      searchFields: [],
      searchValuesLoaded: false,
      showAiMediaDialog: false,
      selectedParagraphText: "",
      selectedParagraphBookmark: null,
      aiEditorMessage: "",
      courseContextTitle: "",
      courseContextLanguage: "",
    }
  },
  computed: {
    item() {
      return this.initialValues && Object.keys(this.initialValues).length > 0 ? this.initialValues : this.values
    },
    violations() {
      return this.errors || {}
    },
    effectiveParentResourceNodeId() {
      const routeNode = this.normalizeNodeId(this.$route?.params?.node ?? this.$route?.params?.id)
      const itemNode = this.normalizeNodeId(this.item?.parentResourceNodeId)
      const resourceNodeId = this.getResourceNodeId()
      return itemNode || routeNode || resourceNodeId || null
    },
    tinyEditorConfig() {
      return {
        appendToolbar: "chamiloAiMedia",
        setup: (editor) => {
          editor.ui.registry.addIcon(
            "chamiloRobot",
            `
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <rect x="7" y="8" width="10" height="8" rx="2.2" fill="#60A5FA" stroke="#1E3A8A" stroke-width="1.8"/>
              <path d="M12 5V8" stroke="#1E3A8A" stroke-width="1.8" stroke-linecap="round"/>
              <circle cx="12" cy="4" r="1.2" fill="#1E3A8A"/>
              <circle cx="10" cy="11.6" r="1.35" fill="#FFFFFF"/>
              <circle cx="14" cy="11.6" r="1.35" fill="#FFFFFF"/>
              <circle cx="10" cy="11.6" r="0.45" fill="#1E3A8A"/>
              <circle cx="14" cy="11.6" r="0.45" fill="#1E3A8A"/>
              <path d="M10 14.4H14" stroke="#1E3A8A" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M5.6 10.2V13.8" stroke="#1E3A8A" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M18.4 10.2V13.8" stroke="#1E3A8A" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M9.2 16.2V18" stroke="#1E3A8A" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M14.8 16.2V18" stroke="#1E3A8A" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          `,
          )
          editor.ui.registry.addButton("chamiloAiMedia", {
            icon: "chamiloRobot",
            tooltip: "Generate AI media",
            onAction: () => {
              this.openAiMediaFromEditor(editor)
            },
          })
          editor.ui.registry.addMenuItem("chamiloAiMedia", {
            text: "Generate AI media",
            icon: "chamiloRobot",
            onAction: () => {
              this.openAiMediaFromEditor(editor)
            },
          })
        },
      }
    },
  },
  async created() {
    if (!this.item.searchFieldValues || typeof this.item.searchFieldValues !== "object") {
      this.item.searchFieldValues = {}
    }

    if (undefined === this.item.indexDocumentContent) {
      this.item.indexDocumentContent = true
    }

    await this.loadCourseContext()

    if (!this.searchEnabled) {
      return
    }

    await this.loadSearchEngineFields()
    await this.loadSearchEngineFieldValuesForEdit()
  },
  methods: {
    normalizeCode(code) {
      return String(code || "")
        .trim()
        .toLowerCase()
    },
    normalizeNodeId(value) {
      if (value == null) return null
      if (typeof value === "number" && Number.isFinite(value) && value > 0) return value

      if (typeof value === "string") {
        const trimmed = value.trim()
        if (!trimmed) return null
        if (/^\d+$/.test(trimmed)) return Number(trimmed)

        const iriMatch = trimmed.match(/\/api\/resource_nodes\/(\d+)/)
        if (iriMatch) return Number(iriMatch[1])
      }

      if (typeof value === "object") {
        const raw = value?.id ?? value?.["@id"] ?? null
        return this.normalizeNodeId(raw)
      }

      return null
    },
    extractIdFromIri(iri) {
      if (!iri || "string" !== typeof iri) return null
      const parts = iri.split("/")
      const last = parts[parts.length - 1]
      const n = Number(last)
      return Number.isFinite(n) ? n : null
    },
    getResourceNodeId() {
      const rn = this.item?.resourceNode
      if (!rn) return null
      if ("number" === typeof rn) return rn
      if ("string" === typeof rn) return this.extractIdFromIri(rn)
      if (rn.id) return Number(rn.id)
      if (rn["@id"]) return this.extractIdFromIri(rn["@id"])
      return null
    },
    async loadCourseContext() {
      const cid = Number(this.$route?.query?.cid || 0)
      this.courseContextTitle = String(this.$route?.query?.course_title || "").trim()
      this.courseContextLanguage = String(this.$route?.query?.course_language || this.locale || "en").trim()

      if (!cid) {
        return
      }

      try {
        const response = await axios.get(`/api/courses/${cid}`)
        const data = response?.data || {}

        const apiTitle = String(data?.title || data?.name || "").trim()
        const apiLanguage = String(data?.language || "").trim()

        if (apiTitle) {
          this.courseContextTitle = apiTitle
        }

        if (apiLanguage) {
          this.courseContextLanguage = apiLanguage
        }
      } catch (e) {
        console.warn("[DocumentsForm] Failed to load course context.", e)
      }
    },
    async loadSearchEngineFields() {
      try {
        const response = await fetch(ENTRYPOINT + "search_engine_fields", { credentials: "same-origin" })
        if (!response.ok) {
          console.error("[Search] Failed to load search engine fields:", response.status)
          return
        }

        const json = await response.json()
        const rawFields = Array.isArray(json) ? json : json["hydra:member"] || []
        if (!Array.isArray(rawFields)) {
          console.error("[Search] Unexpected search engine fields payload:", json)
          return
        }

        this.searchFields = rawFields
          .map((f) => ({ id: f.id, code: this.normalizeCode(f.code), title: f.title }))
          .filter((f) => f.code)

        for (const field of this.searchFields) {
          const code = field.code
          if (!code) continue

          if (this.item.searchFieldValues[field.id] && !this.item.searchFieldValues[code]) {
            this.item.searchFieldValues[code] = this.item.searchFieldValues[field.id]
            delete this.item.searchFieldValues[field.id]
          }

          if (undefined === this.item.searchFieldValues[code]) {
            this.item.searchFieldValues[code] = ""
          }
        }
      } catch (e) {
        console.error("[Search] Failed to fetch search engine fields:", e)
      }
    },
    async fetchFieldValues(resourceNodeId) {
      const iri = `/api/resource_nodes/${resourceNodeId}`

      const tryUrls = [
        `${ENTRYPOINT}search_engine_field_values?resourceNode=${encodeURIComponent(iri)}&pagination=false`,
        `${ENTRYPOINT}search_engine_field_values?resourceNodeId=${encodeURIComponent(resourceNodeId)}&pagination=false`,
      ]

      for (const url of tryUrls) {
        try {
          const response = await fetch(url, { credentials: "same-origin" })
          if (!response.ok) {
            console.warn("[Search] Field values request failed:", response.status, url)
            continue
          }

          const json = await response.json()
          const items = Array.isArray(json) ? json : json["hydra:member"] || []
          if (!Array.isArray(items)) {
            console.warn("[Search] Unexpected field values payload:", json)
            continue
          }

          return items
        } catch (e) {
          console.warn("[Search] Field values request error:", e)
        }
      }

      return []
    },
    async loadSearchEngineFieldValuesForEdit() {
      if (this.searchValuesLoaded) return

      const resourceNodeId = this.getResourceNodeId()
      if (!resourceNodeId) return

      const items = await this.fetchFieldValues(resourceNodeId)
      if (!items.length) return

      const fieldIdToCode = new Map(this.searchFields.map((f) => [Number(f.id), f.code]))

      for (const v of items) {
        let fieldId = null

        if (v.field) {
          if ("string" === typeof v.field) fieldId = this.extractIdFromIri(v.field)
          else if (v.field["@id"]) fieldId = this.extractIdFromIri(v.field["@id"])
          else if (v.field.id) fieldId = Number(v.field.id)
        }

        if (!fieldId && v.field_id) fieldId = Number(v.field_id)
        if (!fieldId) continue

        const code = fieldIdToCode.get(Number(fieldId))
        if (!code) continue

        this.item.searchFieldValues[code] = String(v.value ?? "")
      }

      this.searchValuesLoaded = true
      console.log("[Search] Loaded search field values for resourceNodeId=", resourceNodeId)
    },

    /* --------------------------------------------------------- */
    /* Legacy browser helper kept for compatibility               */
    /* --------------------------------------------------------- */
    browser(callback, value, meta) {
      const nodeId = this.$route.params["node"] ?? this.$route.params["id"]
      const folderParams = this.$route.query
      let url = this.$router.resolve({
        name: "DocumentForHtmlEditor",
        params: { node: nodeId },
        query: folderParams,
      })
      url = url.fullPath

      if (meta.filetype === "image") url = url + "&type=images"
      else if (meta.filetype === "media") url = url + "&type=media"
      else url = url + "&type=files"

      window.addEventListener("message", function (event) {
        const data = event.data
        if (data.url) callback(data.url)
      })

      tinymce.activeEditor.windowManager.openUrl(
        { url, title: "file manager" },
        {
          oninsert: function (file, fm) {
            const absoluteUrl = fm.convAbsUrl(file.url)
            const info = file.name + " (" + fm.formatSize(file.size) + ")"

            if (meta.filetype === "file") callback(absoluteUrl, { text: info, title: info })
            if (meta.filetype === "image") callback(absoluteUrl, { alt: info })
            if (meta.filetype === "media") callback(absoluteUrl)
          },
        },
      )
      return false
    },
    getTinyEditor() {
      try {
        return window.tinymce.get("item_content") || window.tinymce.activeEditor || null
      } catch {
        return null
      }
    },
    getClosestSupportedBlock(node) {
      let current = node

      while (current) {
        const nodeName = String(current.nodeName || "").toLowerCase()
        if (["p", "li", "blockquote", "div"].includes(nodeName)) {
          return current
        }

        current = current.parentNode
      }

      return null
    },
    openAiMediaFromEditor(editorInstance = null) {
      this.aiEditorMessage = ""

      const editor = editorInstance || this.getTinyEditor()
      if (!editor) {
        this.aiEditorMessage = this.$t("The editor is not ready yet.")
        return
      }

      editor.focus()

      const selectedNode = editor.selection?.getNode?.()
      const selectedBlock = this.getClosestSupportedBlock(selectedNode)

      if (!selectedBlock) {
        this.aiEditorMessage = this.$t("Please place the cursor inside a paragraph before generating AI media.")
        return
      }

      const paragraphText = String(selectedBlock.innerText || selectedBlock.textContent || "").trim()
      if (!paragraphText) {
        this.aiEditorMessage = this.$t("The selected paragraph is empty.")
        return
      }

      this.selectedParagraphBookmark = editor.selection.getBookmark(2, true)
      this.selectedParagraphText = paragraphText
      this.showAiMediaDialog = true
    },
    handleAiMediaAccepted(payload) {
      this.aiEditorMessage = ""
      this.insertMediaAfterSelectedBlock(payload)
    },
    insertMediaAfterSelectedBlock(payload) {
      const editor = this.getTinyEditor()
      if (!editor) {
        this.aiEditorMessage = this.$t("The editor is not ready yet.")
        return
      }
      const mediaType = String(payload?.type || "image").toLowerCase()
      const safeUrl = String(payload?.url || "").trim()
      const safeAlt = String(payload?.title || "Generated media").trim()

      if (!safeUrl) {
        this.aiEditorMessage = this.$t("The generated media URL is empty.")
        return
      }

      let html = ""
      if (mediaType === "video") {
        html = `<p><video controls src="${safeUrl}"></video></p>`
      } else {
        html = `<p><img src="${safeUrl}" alt="${safeAlt}" /></p>`
      }

      editor.focus()

      try {
        editor.undoManager.transact(() => {
          if (this.selectedParagraphBookmark) {
            editor.selection.moveToBookmark(this.selectedParagraphBookmark)
          }

          const node = editor.selection?.getNode?.()
          const block = this.getClosestSupportedBlock(node)

          if (block && block.parentNode) {
            const wrapper = editor.dom.create("div", {}, html)
            const newNode = wrapper.firstChild
            editor.dom.insertAfter(newNode, block)
            editor.nodeChanged()
          } else {
            editor.insertContent(html)
          }
        })

        this.item.contentFile = editor.getContent()
      } catch (e) {
        console.error("[DocumentsForm] Failed to insert AI media into TinyMCE.", e)
        this.aiEditorMessage = this.$t("Failed to insert the generated media into the editor.")
      }
    },
    updateContent(content) {
      this.contentFile = content
      this.item.contentFile = content
    },
  },
  validations: {
    item: {
      title: { required },
      contentFile: {},
      parentResourceNodeId: {},
      resourceNode: {},
    },
  },
  emits: ["submit"],
}
</script>
