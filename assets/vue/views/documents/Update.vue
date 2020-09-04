<template>
  <div>
    <DocumentsForm
      v-if="item"
      ref="updateForm"
      :values="item"
      :errors="violations"
    />

    <ResourceLinkForm
      v-if="item"
      ref="resourceLinkForm"
      :values="item"
    />

    <Toolbar
      :handle-submit="onSendForm"
      :handle-reset="resetForm"
      :handle-delete="del"
    />

    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/Form.vue';
import ResourceLinkForm from '../../components/documents/ResourceLinkForm';
import Loading from '../../components/Loading';
import Toolbar from '../../components/Toolbar';
import UpdateMixin from '../../mixins/UpdateMixin';

const servicePrefix = 'Documents';

export default {
  name: 'DocumentsUpdate',
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    DocumentsForm,
    ResourceLinkForm
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
    ...mapGetters('documents', ['find'])
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
