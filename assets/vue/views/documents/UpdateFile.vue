<template>
  <div>
    <!--    :handle-delete="del"-->
    <Toolbar
        v-if="item && !isLoading"
        :handle-submit="onSendForm"
        :handle-reset="resetForm"
    />
    <DocumentsForm
      v-if="item && !isLoading"
      ref="updateForm"
      :values="item"
      :errors="violations"
    >
      <ResourceLinkForm
          v-if="item && !isLoading"
          ref="resourceLinkForm"
          :values="item"
      />
    </DocumentsForm>
    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/FormNewDocument.vue';
import ResourceLinkForm from '../../components/documents/ResourceLinkForm.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
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
  data() {
    return {
    };
  },
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
