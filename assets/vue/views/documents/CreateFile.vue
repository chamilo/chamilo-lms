<template>
  <Toolbar
    :handle-reset="resetForm"
    :handle-submit="onSendFormData"
    :handle-back="handleBack"
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
        :errors="violations"
        :values="item"
      />
      <Panel
        v-if="$route.query.filetype === 'certificate' "
        :header="$t('Create your certificate copy-pasting the following tags. They will be replaced in the document by their student-specific value:')"
      >
        <div v-html="finalTags" />
      </Panel>
    </div>
  </div>
  <Loading :visible="isLoading" />
</template>

<script>
import { mapActions } from "vuex"
import { createHelpers } from "vuex-map-fields"
import DocumentsForm from "../../components/documents/FormNewDocument.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import CreateMixin from "../../mixins/CreateMixin"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import Panel from "primevue/panel"
import TemplateList from "../../components/documents/TemplateList.vue"
import axios from "axios"

const servicePrefix = "Documents"

const { mapFields } = createHelpers({
  getterType: "documents/getField",
  mutationType: "documents/updateField",
})

export default {
  name: "DocumentsCreateFile",
  servicePrefix,
  components: {
    TemplateList,
    Loading,
    Toolbar,
    DocumentsForm,
    Panel
  },
  mixins: [CreateMixin],
  data() {
    const filetype = this.$route.query.filetype === 'certificate' ? 'certificate' : 'file';
    const finalTags = this.getCertificateTags();
    return {
      item: {
        newDocument: true, // Used in FormNewDocument.vue to show the editor
        filetype: filetype,
        parentResourceNodeId: null,
        resourceLinkList: null,
        contentFile: null,
      },
      templates: [],
      finalTags,
    };
  },
  computed: {
    ...mapFields(["error", "isLoading", "created", "violations"]),
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
        this.$router.back();
      },
      addTemplateToEditor(templateContent) {
        this.item.contentFile = templateContent;
      },
      fetchTemplates() {
        axios.get('/system-templates')
          .then(response => {
            console.log(response.data);
            this.templates = response.data;
          })
          .catch(error => {
            console.error('There was an error fetching the templates:', error);
          });
      },
      getCertificateTags(){
          let finalTags = "";
          let tags = [
            '((user_firstname))',
            '((user_lastname))',
            '((user_username))',
            '((gradebook_institution))',
            '((gradebook_sitename))',
            '((teacher_firstname))',
            '((teacher_lastname))',
            '((official_code))',
            '((date_certificate))',
            '((date_certificate_no_time))',
            '((course_code))',
            '((course_title))',
            '((gradebook_grade))',
            '((certificate_link))',
            '((certificate_link_html))',
            '((certificate_barcode))',
            '((external_style))',
            '((time_in_course))',
            '((time_in_course_in_all_sessions))',
            '((start_date_and_end_date))',
            '((course_objectives))',
          ];

          for (const tag of tags){
              finalTags += "<p class=\"m-0\">"+tag+"</p>"
          }

          return finalTags;
      },
    ...mapActions('documents', ['createWithFormData', 'reset'])
  },
  mounted() {
    this.fetchTemplates();
  },
};
</script>
