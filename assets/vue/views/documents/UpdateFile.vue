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
              @click="insertCertificateTag(tag)"
              :title="$t('Click to insert')"
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
import { mapActions, mapGetters } from "vuex"
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
  name: "DocumentsUpdate",
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
      checkEditPermissions()
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
      isAllowedToEdit: ref(false),
      filetype,

      // Certificate tags (same list as CreateFile.vue)
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
      return (
        (sidFromResourceLink &&
          sidFromResourceLink === `/api/sessions/${this.$route.query.sid}` &&
          this.isAllowedToEdit) ||
        this.isCurrentTeacher
      )
    },
  },
  mounted() {
    this.fetchTemplates()
    this.checkEditPermissions()

    // Ensure container exists for advanced search fields (same idea as CreateFile.vue)
    if (this.item && typeof this.item === "object" && !this.item.searchFieldValues) {
      this.item.searchFieldValues = {}
    }
  },
  methods: {
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
      // Use DocumentsForm helper if available (keeps current editor state consistent)
      if (this.$refs.updateForm && typeof this.$refs.updateForm.updateContent === "function") {
        this.$refs.updateForm.updateContent(templateContent)
        return
      }

      // Fallback: update bound field
      this.item.contentFile = templateContent
    },

    // ----------------------------
    // Certificate tag helpers (same UX as CreateFile.vue)
    // ----------------------------

    /**
     * Insert text into TinyMCE at cursor position (preferred).
     * Fallback: append to item.contentFile.
     */
    insertIntoEditor(text) {
      try {
        if (window.tinymce) {
          // BaseTinyEditor usually uses: editor-id="item_content"
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
      this.insertIntoEditor(tag)

      // Optional: also try to copy (non-blocking UX)
      await this.writeToClipboard(tag)
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

    // Legacy helper kept (was used by old UI with v-html)
    getCertificateTags() {
      let finalTags = ""
      const tags = [
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
      ]

      for (const tag of tags) {
        finalTags += '<p class="m-0">' + tag + "</p>"
      }

      return finalTags
    },
    ...mapActions("documents", {
      createReset: "resetCreate",
      deleteItem: "del",
      delReset: "resetDelete",
      retrieve: "load",
      updateWithFormData: "updateWithFormData",
      updateReset: "resetUpdate",
    }),
  },
}
</script>
