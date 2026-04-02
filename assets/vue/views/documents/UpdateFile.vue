<template>
  <div v-if="!isLoading && item && canEditItem">
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
          ref="updateForm"
          :errors="violations"
          :values="item"
          :search-enabled="isSearchEnabled"
          @submit="onSendFormData"
        >
          <div
            v-if="isCurrentTeacher"
            class="mt-4 flex items-center gap-2"
          >
            <input
              id="ai-assisted-flag"
              v-model="aiAssistedFlag"
              type="checkbox"
            />
            <label
              for="ai-assisted-flag"
              class="text-sm"
            >
              AI-assisted
            </label>
          </div>

          <EditLinks
            v-model="item"
            :show-share-with-user="false"
            links-type="users"
          />
        </DocumentsForm>

        <Panel
          v-if="filetype === 'certificate'"
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
          <div
            v-if="false"
            v-html="finalTags"
          />
        </Panel>
      </div>
    </div>

    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { computed, onMounted, ref } from "vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import DocumentsForm from "../../components/documents/FormNewDocument.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import UpdateMixin from "../../mixins/UpdateMixin"
import EditLinks from "../../components/resource_links/EditLinks.vue"
import TemplateList from "../../components/documents/TemplateList.vue"
import axios from "axios"
import Panel from "primevue/panel"
import { useRoute } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"

const servicePrefix = "Documents"

const platformConfigStore = usePlatformConfig()
const isSearchEnabled = computed(() => "false" !== platformConfigStore.getSetting("search.search_enabled"))

export default {
  name: "DocumentsUpdateFile",
  servicePrefix,
  components: {
    TemplateList,
    EditLinks,
    Loading,
    Toolbar,
    DocumentsForm,
    Panel,
  },
  mixins: [UpdateMixin],
  setup() {
    const securityStore = useSecurityStore()
    const isAllowedToEdit = ref(false)
    const route = useRoute()

    const checkEditPermissions = async () => {
      isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
    }

    onMounted(() => {
      void checkEditPermissions()
    })

    return {
      securityStore,
      isAllowedToEdit,
      route,
      checkEditPermissions,
      isSearchEnabled,
    }
  },
  data() {
    const allowedFiletypes = ["file", "certificate", "video"]
    const filetypeQuery = this.$route.query.filetype
    const filetype = allowedFiletypes.includes(filetypeQuery) ? filetypeQuery : "file"
    const finalTags = filetype === "certificate" ? this.getCertificateTags() : ""

    return {
      templates: [],
      finalTags,
      filetype,
      aiAssistedFlag: false,

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
  computed: {
    ...mapFields("documents", {
      deleteLoading: "isLoading",
      isLoading: "isLoading",
      error: "error",
      updated: "updated",
      violations: "violations",
    }),
    ...mapGetters("documents", ["find"]),
    isCurrentTeacher() {
      return this.securityStore.isCurrentTeacher || this.isAllowedToEdit
    },
    canEditItem() {
      const resourceLink = this.item?.resourceLinkListFromEntity?.[0]
      const sidFromResourceLink = resourceLink?.session?.["@id"]
      const sid = String(this.$route.query.sid ?? "0")

      return (
        (sidFromResourceLink && sidFromResourceLink === `/api/sessions/${sid}` && this.isAllowedToEdit) ||
        this.isCurrentTeacher
      )
    },
  },

  watch: {
    item: {
      immediate: true,
      handler(val) {
        if (!val || typeof val !== "object") {
          return
        }

        const raw = val.ai_assisted_raw ?? val.ai_assisted
        this.aiAssistedFlag = raw === true || raw === 1 || raw === "1"
      },
    },
  },

  mounted() {
    this.fetchTemplates()
    this.checkEditPermissions()

    if (this.item && typeof this.item === "object" && !this.item.searchFieldValues) {
      this.item.searchFieldValues = {}
    }
  },
  methods: {
    normalizeBoolean(value) {
      const v = String(value ?? "")
        .trim()
        .toLowerCase()

      return ["1", "true", "yes", "on"].includes(v)
    },

    normalizeAiAssistedState() {
      const currentRaw = this.item?.ai_assisted_raw
      const current = this.item?.ai_assisted
      const enabled = this.aiAssistedFlag || this.normalizeBoolean(currentRaw) || this.normalizeBoolean(current)

      this.item.ai_assisted = enabled ? 1 : 0
      this.item.ai_assisted_raw = enabled ? 1 : 0
      this.aiAssistedFlag = enabled
    },

    handleBack() {
      this.$router.back()
    },
    fetchTemplates() {
      const cid = this.$route.query.cid
      axios
        .get(`/template/all-templates/${cid}`)
        .then((response) => {
          this.templates = response.data
          console.log("[Documents] Templates fetched successfully:", this.templates)
        })
        .catch((error) => {
          console.error("[Documents] Error fetching templates:", error)
        })
    },
    addTemplateToEditor(templateContent) {
      if (this.$refs.updateForm && typeof this.$refs.updateForm.updateContent === "function") {
        this.$refs.updateForm.updateContent(templateContent)
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

    onSendFormData() {
      this.normalizeAiAssistedState()

      if (UpdateMixin?.methods?.onSendFormData) {
        return UpdateMixin.methods.onSendFormData.call(this)
      }

      console.error("[Documents] UpdateMixin.onSendFormData is missing.")
      return null
    },
  },
}
</script>
