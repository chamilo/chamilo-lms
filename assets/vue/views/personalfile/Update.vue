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
import DocumentsForm from '../../components/personalfile/Form.vue';
import ResourceLinkForm from '../../components/personalfile/ResourceLinkForm.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UpdateMixin from '../../mixins/UpdateMixin';

const servicePrefix = 'PersonalFile';

export default {
  name: 'PersonalFileUpdate',
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    DocumentsForm,
    ResourceLinkForm
  },
  mixins: [UpdateMixin],
  computed: {
    ...mapFields('personalfile', {
      deleteLoading: 'isLoading',
      isLoading: 'isLoading',
      error: 'error',
      updated: 'updated',
      violations: 'violations'
    }),
    ...mapGetters('personalfile', ['find']),
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
  methods: {
    ...mapActions('personalfile', {
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
