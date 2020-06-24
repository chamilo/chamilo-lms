<template>
  <div>
    <Toolbar :handle-submit="onSendForm" :handle-reset="resetForm"></Toolbar>
    <DocumentsForm ref="createForm" :values="item" :errors="violations" />
    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/FormNewDocument';
import Loading from '../../components/Loading';
import Toolbar from '../../components/Toolbar';
import CreateMixin from '../../mixins/CreateMixin';

const servicePrefix = 'Documents';

const { mapFields } = createHelpers({
  getterType: 'documents/getField',
  mutationType: 'documents/updateField'
});

export default {
  name: 'DocumentsCreate',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    Loading,
    Toolbar,
    DocumentsForm
  },
  data() {
    return {
      item: {
        filetype: 'file',
        parentResourceNodeId: null,
        resourceLinkList: null,
        content: null
      },
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations'])
  },
  created() {
    this.item.parentResourceNodeId = this.$route.params.node;
    this.item.resourceLinkList = JSON.stringify([{
      c_id: this.$route.query.cid,
      visibility: 2,
    }]);
  },
  methods: {
    ...mapActions('documents', ['create', 'reset'])
  }
};
</script>
