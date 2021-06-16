<template>
  <div>
    <Toolbar
        :handle-submit="onSendForm"
        :handle-reset="resetForm"
    />
    <DocumentsForm
      ref="createForm"
      :values="item"
      :errors="violations"
    />

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/FormNewDocument.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';

const servicePrefix = 'Documents';

const { mapFields } = createHelpers({
  getterType: 'documents/getField',
  mutationType: 'documents/updateField'
});

export default {
  name: 'DocumentsCreate',
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    DocumentsForm
  },
  mixins: [CreateMixin],
  data() {
    return {
      item: {
        newDocument: true, // Used in FormNewDocument.vue to show the editor
        filetype: 'file',
        parentResourceNodeId: null,
        resourceLinkList: null,
        contentFile: null
      },
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations'])
  },
  created() {
    this.item.parentResourceNodeId = this.$route.params.node;
    this.item.resourceLinkList = JSON.stringify([{
      gid: this.$route.query.gid,
      sid: this.$route.query.sid,
      cid: this.$route.query.cid,
      visibility: 2,
    }]);
  },
  methods: {
    ...mapActions('documents', ['create', 'reset'])
  }
};
</script>
