<template>
  <div v-if="!isLoading && item && isCurrentTeacher">
    <!--      :handle-delete="del"-->
    <Toolbar
        :handle-submit="onSendForm"
        :handle-reset="resetForm"
    />
    <DocumentsForm
      ref="updateForm"
      :values="item"
      :errors="violations"
    >
      <EditLinks :item="item" />
    </DocumentsForm>
    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UpdateMixin from '../../mixins/UpdateMixin';
import EditLinks from "../../components/resource_links/EditLinks";

const servicePrefix = 'Documents';

export default {
  name: 'DocumentsUpdate',
  servicePrefix,
  components: {
    EditLinks,
    Loading,
    Toolbar,
    DocumentsForm,
  },
  mixins: [UpdateMixin],
  computed: {
    ...mapFields('documents', {
      deleteLoading: 'isLoading',
      isLoading: 'isLoading',
      error: 'error',
      updated: 'updated',
      violations: 'violations'
    }),
    ...mapGetters('documents', ['find']),
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
  methods: {
    ...mapActions('documents', {
      createReset: 'resetCreate',
      deleteItem: 'del',
      delReset: 'resetDelete',
      retrieve: 'load',
      update: 'update',
      updateReset: 'resetUpdate'
    })
  }
};
</script>
