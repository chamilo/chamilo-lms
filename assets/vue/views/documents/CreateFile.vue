<template>
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
        ref="createForm"
        :errors="errors"
        :values="item"
      />
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
  data() {
    const filetype = this.$route.query.filetype === "certificate" ? "certificate" : "file"
    const finalTags = this.getCertificateTags()
    return {
      item: {
        title: "",
        contentFile: "",
        newDocument: true, // Used in FormNewDocument.vue to show the editor
        filetype: filetype,
        parentResourceNodeId: null,
        resourceLinkList: null,
      },
      templates: [],
      isLoading: false,
      errors: {},
      finalTags,
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
        let data = await documentsService.getTemplates(courseId)
        this.templates = data
      } catch (error) {
        console.error(error)
        this.errors = error.errors
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
    async createWithFormData(payload) {
      this.isLoading = true
      this.errors = {}
      try {
        let response = await documentsService.createWithFormData(payload);
        let data = await response.json();
        console.log(data);
        this.onCreated(data);
      } catch (error) {
        console.error(error)
        this.errors = error.errors
      } finally {
        this.isLoading = false
      }
    },
    onCreated(item) {
      let message;
      if (item["resourceNode"]) {
        message =
          this.$i18n && this.$i18n.t
            ? this.$t("{resource} created", { resource: item["resourceNode"].title })
            : `${item["resourceNode"].title} created`;
      } else {
        message =
          this.$i18n && this.$i18n.t ? this.$t("{resource} created", { resource: item.title }) : `${item.title} created`;
      }

      this.showMessage(message);
      let folderParams = this.$route.query;

      this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: { id: item["@id"] },
        query: folderParams,
      });
    },
  },
  mounted() {
    this.fetchTemplates()
  },
}
</script>
