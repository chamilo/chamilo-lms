<template>
  <div v-if="!isLoading && item && isCurrentTeacher">
    <!--    :handle-delete="del"-->
    <Toolbar
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
            :item="item"
            :show-share-with-user="false"
            links-type="users"
          />
        </DocumentsForm>
      </div>
    </div>

    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import DocumentsForm from "../../components/documents/FormNewDocument.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import UpdateMixin from "../../mixins/UpdateMixin"
import EditLinks from "../../components/resource_links/EditLinks.vue"
import TemplateList from "../../components/documents/TemplateList.vue"
import axios from "axios";

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
  },
  data() {
    return {
      templates: [],
    };
  },
  mixins: [UpdateMixin],
  computed: {
    ...mapFields("documents", {
      deleteLoading: "isLoading",
      isLoading: "isLoading",
      error: "error",
      updated: "updated",
      violations: "violations",
    }),
    ...mapGetters("documents", ["find"]),
    ...mapGetters({
      isCurrentTeacher: "security/isCurrentTeacher",
    }),
  },
  methods: {
    fetchTemplates() {
      axios.get('/system-templates')
        .then(response => {
          this.templates = response.data;
        })
        .catch(error => {
          console.error('Error fetching the templates:', error);
        });
    },
    addTemplateToEditor(templateContent) {
      if (this.$refs.updateForm && typeof this.$refs.updateForm.updateContent === 'function') {
        this.$refs.updateForm.updateContent(templateContent);
      }
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
  mounted() {
    this.fetchTemplates();
  },
}
</script>
