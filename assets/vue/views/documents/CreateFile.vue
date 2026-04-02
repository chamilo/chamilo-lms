<template>
  <Toolbar
    :handle-back="handleBack"
    :handle-reset="resetForm"
  />

  <!-- Quota warning banner -->
  <div
    v-if="quotaWarningMessage"
    class="mb-4 rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900"
    role="alert"
  >
    {{ quotaWarningMessage }}
  </div>

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
            :title="$t('Click to insert')"
            @click="insertCertificateTag(tag)"
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
const QUOTA_WARNING_THRESHOLD_PERCENT = 2

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
        newDocument: true,
        filetype,
        parentResourceNodeId: null,
        resourceLinkList: null,

        // Search-related flag
        indexDocumentContent: this.defaultIndexDocumentContent,
        searchFieldValues: {},

        // AI disclosure flags
        ai_assisted: 0,
        ai_assisted_raw: 0,
      },

      templates: [],
      isLoading: false,
      errors: {},
      quotaWarningMessage: "",

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
    this.item.parentResourceNodeId = this.getRouteNodeId()
    this.item.resourceLinkList = JSON.stringify([
      {
        gid: this.$route.query.gid,
        sid: this.$route.query.sid,
        cid: this.$route.query.cid,
        visibility: RESOURCE_LINK_PUBLISHED,
      },
    ])

    this.showQuotaWarningIfNeeded()
  },

  mounted() {
    this.fetchTemplates()
  },

  methods: {
    getRouteNodeId() {
      return this.$route.params.node ?? this.$route.params.id ?? null
    },

    normalizeBoolean(value) {
      const v = String(value ?? "")
        .trim()
        .toLowerCase()

      return ["1", "true", "yes", "on"].includes(v)
    },

    normalizeAiAssistedState() {
      const currentRaw = this.item?.ai_assisted_raw
      const current = this.item?.ai_assisted
      const enabled = this.normalizeBoolean(currentRaw) || this.normalizeBoolean(current)

      this.item.ai_assisted = enabled ? 1 : 0
      this.item.ai_assisted_raw = enabled ? 1 : 0
    },

    toInt(value, fallback = 0) {
      const n = Number(value)
      return Number.isFinite(n) ? n : fallback
    },

    async showQuotaWarningIfNeeded() {
      const courseId = this.toInt(this.$route.query.cid, 0)
      if (!courseId) return

      const sid = this.toInt(this.$route.query.sid, 0)
      const gid = this.toInt(this.$route.query.gid, 0)

      try {
        const msg = await documentsService.fetchQuotaWarningMessage(this.$t.bind(this), courseId, {
          sid,
          gid,
          force: true,
          thresholdPercent: QUOTA_WARNING_THRESHOLD_PERCENT,
        })

        if (msg) {
          this.quotaWarningMessage = msg
          if (typeof this.showMessage === "function") {
            this.showMessage(msg)
          }
        }
      } catch (e) {
        console.error("[DocumentsCreateFile] Failed to show quota warning:", e)
      }
    },

    handleBack() {
      this.$router.back()
    },

    addTemplateToEditor(templateContent) {
      if (this.$refs.createForm && typeof this.$refs.createForm.updateContent === "function") {
        this.$refs.createForm.updateContent(templateContent)
        return
      }

      this.item.contentFile = templateContent
    },

    insertIntoEditor(text) {
      try {
        if (window.tinymce) {
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

      this.item.contentFile = String(this.item.contentFile || "") + text
      return false
    },

    async writeToClipboard(text) {
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(text)
          return true
        }
      } catch (e) {
        console.warn("[Certificate] Clipboard API failed, using fallback:", e)
      }

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

    async insertCertificateTag(tag) {
      this.insertIntoEditor(tag)
      await this.writeToClipboard(tag)
    },

    async copyAllCertificateTags() {
      const text = this.certificateTags.join("\n")
      const ok = await this.writeToClipboard(text)

      if (ok) {
        this.showMessage("All tags copied to clipboard")
        return
      }

      this.showMessage("Failed to copy tags")
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

    onSendFormData() {
      this.normalizeAiAssistedState()

      if (CreateMixin?.methods?.onSendFormData) {
        return CreateMixin.methods.onSendFormData.call(this)
      }

      console.error("[Documents] CreateMixin.onSendFormData is missing.")
      return null
    },
  },
}
</script>
