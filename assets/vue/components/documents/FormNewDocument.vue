<template>
  <form @submit.prevent="$emit('submit')">
    <BaseInputTextWithVuelidate
      id="item_title"
      v-model.trim="item.title"
      :label="$t('Title')"
      :vuelidate-property="v$.item.title"
    />
    <BaseTextArea
      id="item_comment"
      v-model="item.comment"
      label="Description"
      rows="4"
      auto-resize
    />
    <ResourceLanguageSelector
      v-model="item.language"
      class="mt-3"
    />
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
      v-if="editorDrafts.length > 0"
      class="mt-3 rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <div class="mb-3 flex items-center justify-between gap-3">
        <div class="text-sm font-semibold text-gray-90">
          {{ t("Draft") }}
        </div>
        <div class="text-xs text-gray-50">
          {{ editorDrafts.length }}/{{ maxEditorDrafts }}
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <div
          v-for="draft in editorDrafts"
          :key="draft.id"
          class="flex flex-col gap-2 rounded-md border border-gray-25 bg-white p-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0">
            <div class="truncate text-sm font-medium text-gray-90">
              {{ draft.title || t("Untitled") }}
            </div>
            <div class="text-xs text-gray-50">
              {{ t("Saved") }}: {{ formatEditorDraftDate(draft.savedAt) }}
            </div>
          </div>

          <div class="flex shrink-0 gap-2">
            <BaseButton
              icon="restore"
              size="small"
              type="secondary"
              :label="t('Restore')"
              @click.prevent="restoreEditorDraft(draft)"
            />
            <BaseButton
              icon="delete"
              size="small"
              type="danger"
              :label="t('Remove')"
              @click.prevent="removeEditorDraft(draft.id)"
            />
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="aiEditorMessage"
      class="mt-3 rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900"
    >
      {{ aiEditorMessage }}
    </div>

    <BaseAdvancedSettingsButton
      v-if="searchEnabled"
      v-model="showAdvancedSettings"
    >
      <div class="mb-2 flex flex-row">
        <label class="w-40 font-semibold">{{ $t("Options") }}:</label>

        <BaseCheckbox
          id="indexDocumentContent"
          v-model="item.indexDocumentContent"
          :label="$t('Index document content?')"
          name="indexDocumentContent"
        />
      </div>

      <div
        v-if="searchEnabled && searchFields.length > 0"
        class="mt-3 flex flex-col gap-2"
      >
        <div
          v-for="field in searchFields"
          :key="field.id"
          class="flex flex-row items-center gap-3"
        >
          <label
            class="w-40 font-semibold"
            :for="`doc_search_field_${field.code}`"
          >
            {{ field.title }}:
          </label>

          <input
            :id="`doc_search_field_${field.code}`"
            v-model="item.searchFieldValues[field.code]"
            :name="`searchFieldValues[${field.code}]`"
            type="text"
            class="w-full rounded border border-gray-300 px-3 py-2"
            :placeholder="field.title"
            autocomplete="off"
          />
        </div>
      </div>
    </BaseAdvancedSettingsButton>

    <slot></slot>

    <div class="mt-4 flex justify-end">
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
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useSecurityStore } from "../../store/securityStore"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import DocumentAiMediaDialog from "./DocumentAiMediaDialog.vue"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"
import ResourceLanguageSelector from "../resources/ResourceLanguageSelector.vue"

export default {
  name: "DocumentsForm",
  components: {
    BaseTextArea,
    BaseButton,
    BaseCheckbox,
    BaseTinyEditor,
    BaseAdvancedSettingsButton,
    BaseInputTextWithVuelidate,
    DocumentAiMediaDialog,
    ResourceLanguageSelector,
  },
  props: {
    values: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    initialValues: { type: Object, default: () => ({}) },
    searchEnabled: { type: Boolean, default: false },
  },
  setup() {
    const platformConfigStore = usePlatformConfig()
    const courseSettingsStore = useCourseSettings()
    const securityStore = useSecurityStore()
    const extraPlugins = ref("")
    const { t, locale } = useI18n()

    if ("true" === platformConfigStore.getSetting("editor.translate_html")) {
      extraPlugins.value = "translatehtml"
    }

    return {
      v$: useVuelidate(),
      extraPlugins,
      t,
      locale,
      platformConfigStore,
      courseSettingsStore,
      securityStore,
    }
  },
  data() {
    return {
      contentFile: this.initialValues ? this.initialValues.contentFile : "",
      showAdvancedSettings: false,
      searchFields: [],
      searchValuesLoaded: false,
      showAiMediaDialog: false,
      selectedParagraphText: "",
      tinyBookmark: null,
      aiEditorMessage: "",
      courseContextTitle: "",
      courseContextLanguage: "",
      editorDrafts: [],
      editorDraftIntervalId: null,
      lastEditorDraftContent: "",
      maxEditorDrafts: 5,
    }
  },
  validations() {
    return {
      item: {
        title: { required },
        comment: {},
      },
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
    showAiMediaButton() {
      const aiHelpersEnabled = String(this.platformConfigStore.getSetting("ai_helpers.enable_ai_helpers")) === "true"
      const imageGeneratorEnabled = String(this.courseSettingsStore?.getSetting?.("image_generator")) === "true"
      const videoGeneratorEnabled = String(this.courseSettingsStore?.getSetting?.("video_generator")) === "true"

      return aiHelpersEnabled && (imageGeneratorEnabled || videoGeneratorEnabled)
    },
    tinyEditorConfig() {
      if (!this.showAiMediaButton) {
        return {}
      }

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
            tooltip: this.$t("Generate AI media"),
            onAction: () => {
              this.openAiMediaFromEditor(editor)
            },
          })
          editor.ui.registry.addMenuItem("chamiloAiMedia", {
            text: this.$t("Generate AI media"),
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
    if (undefined === this.item.comment || null === this.item.comment) {
      this.item.comment = ""
    }

    if (!this.item.searchFieldValues || typeof this.item.searchFieldValues !== "object") {
      this.item.searchFieldValues = {}
    }

    if (undefined === this.item.indexDocumentContent) {
      this.item.indexDocumentContent = true
    }

    this.ensureResourceLanguage()

    await this.loadCourseContext()

    if (!this.searchEnabled) {
      return
    }

    await this.loadSearchEngineFields()
    await this.loadSearchEngineFieldValuesForEdit()
  },
  mounted() {
    this.refreshEditorDrafts()
    this.lastEditorDraftContent = this.normalizeEditorDraftContent(this.item.contentFile)
    this.editorDraftIntervalId = window.setInterval(this.saveEditorDraft, 30000)
    window.addEventListener("beforeunload", this.saveEditorDraftOnUnload)
  },
  beforeUnmount() {
    if (this.editorDraftIntervalId) {
      window.clearInterval(this.editorDraftIntervalId)
      this.editorDraftIntervalId = null
    }

    window.removeEventListener("beforeunload", this.saveEditorDraftOnUnload)
  },
  watch: {
    item: {
      immediate: true,
      handler() {
        this.ensureResourceLanguage()
      },
    },
  },
  methods: {
    extractResourceLanguageIso(language) {
      if (!language) {
        return ""
      }

      if ("string" === typeof language) {
        const iriMatch = language.match(/\/api\/languages\/(\d+)/)
        if (!iriMatch) {
          return language
        }

        const languages = Array.isArray(window.languages) ? window.languages : []
        const found = languages.find((item) => String(item?.id || "") === iriMatch[1])

        return String(found?.isocode || "")
      }

      return String(language.isocode || language.isoCode || "")
    },
    ensureResourceLanguage() {
      if (!this.item || undefined !== this.item.language) {
        return
      }

      this.item.language = this.extractResourceLanguageIso(
        this.item?.resourceNode?.language ||
          this.item?.resourceNode?.firstResourceFile?.language ||
          this.item?.firstResourceFile?.language,
      )
    },
    getEditorDraftUserId() {
      const user = this.securityStore?.user || {}

      if (user.id) {
        return String(user.id)
      }

      if (user["@id"]) {
        return String(user["@id"]).replace(/[^a-zA-Z0-9_-]/g, "_")
      }

      return "anonymous"
    },
    getEditorDraftRouteKey() {
      const route = this.$route || {}
      const query = route.query || {}
      const params = route.params || {}

      const parts = [
        route.name || "document",
        params.node || params.id || "0",
        query.id || query.node || "new",
        query.cid || "0",
        query.sid || "0",
        query.gid || "0",
        query.filetype || this.item.filetype || "file",
      ]

      return parts.map((part) => String(part ?? "").replace(/[^a-zA-Z0-9_-]/g, "_")).join(":")
    },
    getEditorDraftStorageKey() {
      return `chamilo:document-editor-drafts:${this.getEditorDraftUserId()}:${this.getEditorDraftRouteKey()}`
    },
    normalizeEditorDraftContent(content) {
      return String(content ?? "").trim()
    },
    getCurrentEditorContent() {
      try {
        const editor = window.tinymce?.get("item_content")

        if (editor) {
          return editor.getContent()
        }
      } catch {
        // Ignore TinyMCE access errors.
      }

      return this.item.contentFile
    },
    setCurrentEditorContent(content) {
      this.item.contentFile = content

      try {
        const editor = window.tinymce?.get("item_content")

        if (editor) {
          editor.setContent(content)
        }
      } catch {
        // Ignore TinyMCE access errors.
      }
    },
    readEditorDrafts() {
      try {
        const raw = window.localStorage.getItem(this.getEditorDraftStorageKey())
        const drafts = raw ? JSON.parse(raw) : []

        if (!Array.isArray(drafts)) {
          return []
        }

        return drafts
          .filter((draft) => draft && typeof draft === "object" && this.normalizeEditorDraftContent(draft.content))
          .sort((a, b) => Number(b.savedAt || 0) - Number(a.savedAt || 0))
          .slice(0, this.maxEditorDrafts)
      } catch {
        return []
      }
    },
    writeEditorDrafts(drafts) {
      const safeDrafts = Array.isArray(drafts) ? drafts.slice(0, this.maxEditorDrafts) : []

      try {
        window.localStorage.setItem(this.getEditorDraftStorageKey(), JSON.stringify(safeDrafts))
      } catch {
        try {
          window.localStorage.setItem(
            this.getEditorDraftStorageKey(),
            JSON.stringify(safeDrafts.slice(0, Math.max(this.maxEditorDrafts - 1, 1))),
          )
        } catch {
          // Ignore localStorage quota errors.
        }
      }
    },
    refreshEditorDrafts() {
      this.editorDrafts = this.readEditorDrafts()
    },
    formatEditorDraftDate(savedAt) {
      const timestamp = Number(savedAt || 0)

      if (!timestamp) {
        return ""
      }

      try {
        return new Date(timestamp).toLocaleString()
      } catch {
        return ""
      }
    },
    saveEditorDraftOnUnload() {
      this.saveEditorDraft()
    },
    saveEditorDraft() {
      const content = this.normalizeEditorDraftContent(this.getCurrentEditorContent())

      if (!content || content === this.lastEditorDraftContent) {
        return
      }

      const drafts = this.readEditorDrafts().filter(
        (draft) => this.normalizeEditorDraftContent(draft.content) !== content,
      )
      const draft = {
        id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        title: String(this.item.title || "").trim(),
        content,
        savedAt: Date.now(),
      }

      drafts.unshift(draft)
      this.writeEditorDrafts(drafts.slice(0, this.maxEditorDrafts))
      this.lastEditorDraftContent = content
      this.refreshEditorDrafts()
    },
    restoreEditorDraft(draft) {
      const content = this.normalizeEditorDraftContent(draft?.content)

      if (!content) {
        return
      }

      this.setCurrentEditorContent(content)
      this.lastEditorDraftContent = content

      if (!this.item.title && draft.title) {
        this.item.title = draft.title
      }
    },
    removeEditorDraft(draftId) {
      const drafts = this.readEditorDrafts().filter((draft) => draft.id !== draftId)
      this.writeEditorDrafts(drafts)
      this.refreshEditorDrafts()
    },
    clearEditorDrafts() {
      try {
        window.localStorage.removeItem(this.getEditorDraftStorageKey())
      } catch {
        // Ignore localStorage errors.
      }

      this.editorDrafts = []
      this.lastEditorDraftContent = this.normalizeEditorDraftContent(this.getCurrentEditorContent())
    },
    setAiEditorMessage(message) {
      this.aiEditorMessage = String(message || "").trim()

      if (!this.aiEditorMessage) {
        return
      }

      window.setTimeout(() => {
        if (this.aiEditorMessage === message) {
          this.aiEditorMessage = ""
        }
      }, 5000)
    },

    normalizeCode(code) {
      return String(code || "")
        .trim()
        .toLowerCase()
    },
    normalizeNodeId(value) {
      if (value == null) return null

      if (typeof value === "number" && Number.isFinite(value) && value > 0) {
        return value
      }

      if (typeof value === "string") {
        const trimmed = value.trim()
        if (!trimmed) return null

        if (/^\d+$/.test(trimmed)) {
          return Number(trimmed)
        }

        const iriMatch = trimmed.match(/\/api\/resource_nodes\/(\d+)/)
        if (iriMatch) {
          return Number(iriMatch[1])
        }
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
      } catch (error) {
        console.warn("[DocumentsForm] Failed to load course context.", error)
      }
    },
    getTinyEditor() {
      try {
        return window.tinymce?.get("item_content") || window.tinymce?.activeEditor || null
      } catch {
        return null
      }
    },
    getClosestSupportedBlock(node) {
      let current = node

      while (current) {
        const name = String(current.nodeName || "").toLowerCase()

        if (["p", "li", "blockquote", "div"].includes(name)) {
          return current
        }

        current = current.parentNode
      }

      return null
    },
    openAiMediaFromEditor(editorInstance = null) {
      const editor = editorInstance || this.getTinyEditor()

      if (!editor) {
        this.setAiEditorMessage(this.t("The editor is not available."))
        return
      }

      editor.focus()

      const node = editor.selection?.getNode?.()
      const block = this.getClosestSupportedBlock(node)

      if (!block) {
        this.setAiEditorMessage(this.t("Please place the cursor inside a paragraph first."))
        return
      }

      const text = String(block.innerText || block.textContent || "").trim()

      if (!text) {
        this.setAiEditorMessage(this.t("The selected paragraph is empty."))
        return
      }

      this.tinyBookmark = editor.selection.getBookmark(2, true)
      this.selectedParagraphText = text
      this.aiEditorMessage = ""
      this.showAiMediaDialog = true
    },
    handleAiMediaAccepted(payload) {
      this.showAiMediaDialog = false
      this.insertMediaAfterSelectedBlock(payload)
    },
    insertMediaAfterSelectedBlock(payload) {
      const editor = this.getTinyEditor()

      if (!editor || !payload?.url) {
        return
      }

      const mediaType = String(payload.type || "image").toLowerCase()
      const safeUrl = String(payload.url).trim()
      const safeAlt = String(payload.alt || payload.title || "Generated media").trim()

      let html = ""
      if ("video" === mediaType) {
        html = `<p><video controls src="${safeUrl}"></video></p>`
      } else {
        html = `<p><img src="${safeUrl}" alt="${safeAlt}" /></p>`
      }

      editor.focus()

      if (this.tinyBookmark) {
        editor.selection.moveToBookmark(this.tinyBookmark)
      }

      const node = editor.selection?.getNode?.()
      const block = this.getClosestSupportedBlock(node)

      if (block && block.parentNode) {
        const wrapper = editor.dom.create("div", {}, html)
        const newNode = wrapper.firstChild

        editor.dom.insertAfter(newNode, block)
        editor.nodeChanged()
        this.item.contentFile = editor.getContent()
        return
      }

      editor.insertContent(html)
      this.item.contentFile = editor.getContent()
    },
    async loadSearchEngineFields() {
      try {
        const response = await fetch("/api/search_engine_fields", {
          credentials: "same-origin",
        })

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
          .map((field) => ({
            id: field.id,
            code: this.normalizeCode(field.code),
            title: field.title,
          }))
          .filter((field) => field.code)

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
      } catch (error) {
        console.error("[Search] Failed to fetch search engine fields:", error)
      }
    },
    async fetchFieldValues(resourceNodeId) {
      const iri = `/api/resource_nodes/${resourceNodeId}`

      const tryUrls = [
        `/api/search_engine_field_values?resourceNode=${encodeURIComponent(iri)}&pagination=false`,
        `/api/search_engine_field_values?resourceNodeId=${encodeURIComponent(resourceNodeId)}&pagination=false`,
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
        } catch (error) {
          console.warn("[Search] Field values request error:", error)
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

      const fieldIdToCode = new Map(this.searchFields.map((field) => [Number(field.id), field.code]))

      for (const item of items) {
        let fieldId = null

        if (item.field) {
          if ("string" === typeof item.field) {
            fieldId = this.extractIdFromIri(item.field)
          } else if (item.field["@id"]) {
            fieldId = this.extractIdFromIri(item.field["@id"])
          } else if (item.field.id) {
            fieldId = Number(item.field.id)
          }
        }

        if (!fieldId && item.field_id) {
          fieldId = Number(item.field_id)
        }

        if (!fieldId) continue

        const code = fieldIdToCode.get(Number(fieldId))
        if (!code) continue

        this.item.searchFieldValues[code] = String(item.value ?? "")
      }

      this.searchValuesLoaded = true
      console.log("[Search] Loaded search field values for resource node:", resourceNodeId)
    },

    browser(callback, _value, meta) {
      const nodeId = this.$route.params.node ?? this.$route.params.id
      const folderParams = this.$route.query
      let url = this.$router.resolve({
        name: "DocumentForHtmlEditor",
        params: { id: nodeId },
        query: folderParams,
      }).fullPath

      if (meta.filetype === "image") {
        url += "&type=images"
      } else if (meta.filetype === "media") {
        url += "&type=media"
      } else {
        url += "&type=files"
      }

      const onMessage = (event) => {
        const data = event.data || {}
        const pickedUrl = data?.content?.url || data?.url || null

        if (!pickedUrl) {
          return
        }

        callback(pickedUrl)
        window.removeEventListener("message", onMessage)
      }

      window.addEventListener("message", onMessage)

      try {
        window.tinymce?.activeEditor?.windowManager.openUrl({
          url,
          title: t("File manager"),
          onClose: () => {
            window.removeEventListener("message", onMessage)
          },
        })
      } catch (error) {
        console.warn("[DocumentsForm] Legacy browser fallback failed.", error)
      }
    },
  },
}
</script>
