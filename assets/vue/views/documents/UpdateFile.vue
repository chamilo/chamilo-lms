<template>
  <div v-if="!isLoading && item && canEditItem">
    <!--    :handle-delete="del"-->
    <Toolbar
      :handle-back="handleBack"
      :handle-reset="resetForm"
      :handle-submit="onSendFormData"
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
        >
          <EditLinks
            v-model="item"
            :show-share-with-user="false"
            links-type="users"
          />
        </DocumentsForm>
        <Panel
          v-if="$route.query.filetype === 'certificate'"
          :header="
            $t(
              'Create your certificate copy-pasting the following tags. They will be replaced in the document by their student-specific value:',
            )
          "
        >
          <div v-html="finalTags" />
        </Panel>
      </div>
    </div>

    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
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
import { useRoute } from 'vue-router'
import { useSecurityStore } from "../../store/securityStore"
import { checkIsAllowedToEdit } from '../../composables/userPermissions'

const servicePrefix = "Documents"

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
      checkEditPermissions
    }
  },
  data() {
    const finalTags = this.getCertificateTags()
    return {
      templates: [],
      finalTags,
      isAllowedToEdit: ref(false)
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
      const sidFromResourceLink = resourceLink?.session?.['@id']
      return (
        (sidFromResourceLink && sidFromResourceLink === `/api/sessions/${this.$route.query.sid}` && this.isAllowedToEdit) ||
        this.isCurrentTeacher
      )
    }
  },
  mounted() {
    this.fetchTemplates()
    this.checkEditPermissions()
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
          console.log("Templates fetched successfully:", this.templates)
        })
        .catch((error) => {
          console.error("Error fetching the templates:", error)
        })
    },
    addTemplateToEditor(templateContent) {
      if (this.$refs.updateForm && typeof this.$refs.updateForm.updateContent === "function") {
        this.$refs.updateForm.updateContent(templateContent)
      }
    },
    getCertificateTags() {
      let finalTags = ""
      let tags = [
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
  }
}
</script>
