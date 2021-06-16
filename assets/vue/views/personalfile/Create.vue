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
import DocumentsForm from '../../components/personalfile/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';

const servicePrefix = 'PersonalFile';

const { mapFields } = createHelpers({
  getterType: 'personal_file/getField',
  mutationType: 'personal_file/updateField'
});

export default {
  name: 'PersonalFileCreate',
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    DocumentsForm
  },
  mixins: [CreateMixin],
  data() {
    return {
      item: {},
      type: 'folder'
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
      visibility: 2, // visible by default
    }]);
  },
  methods: {
    ...mapActions('personalfile', ['create', 'reset'])
  }
};
</script>
