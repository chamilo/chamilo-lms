<template>
  <Toolbar
    :handle-back="handleBack"
    :handle-reset="resetForm"
  />

  <div class="documents-layout">
    <div class="template-list-container">
      <TemplateList
        :templates="templates"
        @template-selected="addTemplateToEditor"
      />
    </div>

    <div class="documents-form-container">
      <DocumentsForm
        ref="createForm"
        :errors="errors"
        :values="item"
        :search-enabled="searchEnabled"
        @submit="onSendFormData"
      />

      <Panel
        v-if="item.filetype === 'certificate'"
        :header="$t('Certificate tags')"
        class="mt-4"
      >
        <div class="flex items-start justify-between gap-3 mb-3">
          <p class="text-sm text-gray-600">
            {{
              $t(
                "Click a tag to insert it into the editor. These placeholders will be replaced when generating the certificate.",
              )
            }}
          </p>

          <button
            type="button"
            class="shrink-0 px-3 py-2 rounded-lg border border-gray-25 hover:bg-gray-10 text-sm font-medium"
            @click="copyAllCertificateTags"
          >
            {{ $t("Copy all") }}
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <button
            v-for="tag in certificateTags"
            :key="tag"
            type="button"
            class="text-left px-3 py-2 rounded-lg border border-gray-25 hover:border-gray-20 hover:bg-gray-10"
            @click="insertCertificateTag(tag)"
            :title="$t('Click to insert')"
          >
            <code class="text-sm">{{ tag }}</code>
          </button>
        </div>
      </Panel>
    </div>
  </div>

  <Loading :visible="isLoading" />
</template>

<script>
import DocumentsForm from "../../components/documents/FormNewDocument.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import CreateMixin from "../../mixins/CreateMixin"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import Panel from "primevue/panel"
import TemplateList from "../../components/documents/TemplateList.vue"
import documentsService from "../../services/documents"
import { usePlatformConfig } from "../../store/platformConfig"

const servicePrefix = "Documents"

export default {
  name: "DocumentsCreateFile",
  servicePrefix,
  components: {
    TemplateList,
    Loading,
    Toolbar,
    DocumentsForm,
    Panel,
  },
  mixins: [CreateMixin],

  // Read platform search setting once and expose as simple booleans
  setup() {
    const platformConfigStore = usePlatformConfig()

    const raw = platformConfigStore.getSetting("search.search_enabled")
    const searchEnabled = raw !== "false"
    const defaultIndexDocumentContent = searchEnabled

    return {
      searchEnabled,
      defaultIndexDocumentContent,
    }
  },

  data() {
    const allowedFiletypes = ["file", "video", "certificate"]
    const filetypeQuery = this.$route.query.filetype
    const filetype = allowedFiletypes.includes(filetypeQuery) ? filetypeQuery : "file"

    return {
      item: {
        title: "",
        contentFile: "",
        newDocument: true, // Used in FormNewDocument.vue to show the editor
        filetype,
        parentResourceNodeId: null,
        resourceLinkList: null,

        // Search-related flag: default depends on global search setting
        indexDocumentContent: this.defaultIndexDocumentContent,

        // Ensure container exists for advanced search fields
        searchFieldValues: {},
      },

      templates: [],
      isLoading: false,
      errors: {},

      // Certificate UI helpers
      certificateTags: [
        "((user_firstname))",
        "((user_lastname))",
        "((user_username))",
        "((gradebook_institution))",
        "((gradebook_sitename))",
        "((teacher_firstname))",
        "((teacher_lastname))",
        "((official_code))",
        "((date_certificate))",
        "((date_certificate_no_time))",
        "((course_code))",
        "((course_title))",
        "((gradebook_grade))",
        "((certificate_link))",
        "((certificate_link_html))",
        "((certificate_barcode))",
        "((external_style))",
        "((time_in_course))",
        "((time_in_course_in_all_sessions))",
        "((start_date_and_end_date))",
        "((course_objectives))",
      ],
    }
  },

  created() {
    this.item.parentResourceNodeId = this.$route.params.node
    this.item.resourceLinkList = JSON.stringify([
      {
        gid: this.$route.query.gid,
        sid: this.$route.query.sid,
        cid: this.$route.query.cid,
        visibility: RESOURCE_LINK_PUBLISHED,
      },
    ])
  },

  methods: {
    handleBack() {
      this.$router.back()
    },

    addTemplateToEditor(templateContent) {
      this.item.contentFile = templateContent
    },

    async fetchTemplates() {
      this.errors = {}
      const courseId = this.$route.query.cid
      try {
        const data = await documentsService.getTemplates(courseId)
        this.templates = data
      } catch (error) {
        console.error("[Documents] Failed to fetch templates:", error)
        this.errors = error.errors
      }
    },

    // ----------------------------
    // Certificate tag helpers
    // ----------------------------

    /**
     * Insert text into TinyMCE at cursor position (preferred).
     * Fallback: append to item.contentFile.
     */
    insertIntoEditor(text) {
      try {
        if (window.tinymce) {
          // BaseTinyEditor uses: editor-id="item_content"
          const editor = window.tinymce.get("item_content") || window.tinymce.activeEditor
          if (editor) {
            editor.focus()
            editor.selection.setContent(text)
            return true
          }
        }
      } catch (e) {
        console.warn("[Certificate] Failed to insert into TinyMCE editor:", e)
      }

      // Fallback (not cursor-aware but reliable)
      this.item.contentFile = String(this.item.contentFile || "") + text
      return false
    },

    /**
     * Copy text to clipboard.
     * Uses modern Clipboard API when available (secure context),
     * otherwise falls back to execCommand("copy").
     */
    async writeToClipboard(text) {
      // Modern API (secure context required)
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(text)
          return true
        }
      } catch (e) {
        console.warn("[Certificate] Clipboard API failed, using fallback:", e)
      }

      // Fallback
      try {
        const textarea = document.createElement("textarea")
        textarea.value = text
        textarea.setAttribute("readonly", "")
        textarea.style.position = "fixed"
        textarea.style.top = "-1000px"
        textarea.style.left = "-1000px"
        textarea.style.opacity = "0"
        document.body.appendChild(textarea)
        textarea.focus()
        textarea.select()

        const ok = document.execCommand("copy")
        document.body.removeChild(textarea)
        return ok
      } catch (e) {
        console.warn("[Certificate] Clipboard fallback failed:", e)
        return false
      }
    },

    /**
     * Click on a tag: insert into editor (main behavior).
     * Optionally also tries to copy, but insertion is the priority.
     */
    async insertCertificateTag(tag) {
      const inserted = this.insertIntoEditor(tag)

      // Optional: also try to copy (non-blocking UX)
      const copied = await this.writeToClipboard(tag)
    },

    /**
     * Copy all tags to clipboard (button).
     */
    async copyAllCertificateTags() {
      const text = this.certificateTags.join("\n")
      const ok = await this.writeToClipboard(text)

      if (ok) {
        this.showMessage("All tags copied to clipboard.")
      } else {
        this.showMessage("Copy failed (browser restrictions).")
      }
    },

    // ----------------------------
    // Existing create logic
    // ----------------------------

    async createWithFormData(payload) {
      this.isLoading = true
      this.errors = {}
      try {
        const response = await documentsService.createWithFormData(payload)
        const data = await response.json()
        console.log("[Documents] Create response:", data)
        this.onCreated(data)
      } catch (error) {
        console.error("[Documents] Create failed:", error)
        this.errors = error.errors
      } finally {
        this.isLoading = false
      }
    },

    onCreated(item) {
      let message
      if (item["resourceNode"]) {
        message =
          this.$i18n && this.$i18n.t
            ? this.$t("{resource} created", { resource: item["resourceNode"].title })
            : `${item["resourceNode"].title} created`
      } else {
        message =
          this.$i18n && this.$i18n.t ? this.$t("{resource} created", { resource: item.title }) : `${item.title} created`
      }

      this.showMessage(message)
      const folderParams = this.$route.query

      this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: { id: item["@id"] },
        query: folderParams,
      })
    },
  },

  mounted() {
    this.fetchTemplates()
  },
}
</script>
