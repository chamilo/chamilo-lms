<template>
  <div>
    <DocumentsForm
      ref="createForm"
      :values="files"
      :parentResourceNodeId="parentResourceNodeId"
      :resourceLinkList="resourceLinkList"
      :errors="violations"
    />
    <Toolbar
      :handle-submit="onUploadForm"
    />
    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/FormUpload';
import Loading from '../../components/Loading';
import Toolbar from '../../components/Toolbar';
import UploadMixin from '../../mixins/UploadMixin';

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
  mixins: [UploadMixin],
  data() {
    return {
      files : [],
      parentResourceNodeId: 0,
      resourceLinkList: '',
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations'])
  },
  created() {
    console.log('created');
    this.parentResourceNodeId = Number(this.$route.params.node);
    this.resourceLinkList = JSON.stringify([{
      c_id: this.$route.query.cid,
      visibility: 2,
    }]);
    this.files = [];
  },
  methods: {
    ...mapActions('documents', ['uploadMany', 'create'])
  }
};
</script>
