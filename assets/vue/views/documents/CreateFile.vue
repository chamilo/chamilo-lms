<template>
  <div>
    <Toolbar :handle-submit="onSendForm" :handle-reset="resetForm"></Toolbar>
    <DocumentsForm ref="createForm" :values="item" :errors="violations" :type="type" />
    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/Form';
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
      item: {},
      type: 'file',
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations'])
  },
  methods: {
    ...mapActions('documents', ['create', 'reset'])
  }
};
</script>
