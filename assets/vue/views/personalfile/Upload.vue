<template>
  <div>
    <DocumentsForm
      ref="createForm"
      :values="files"
      :parentResourceNodeId="parentResourceNodeId"
      :resourceLinkList="resourceLinkList"
      :errors="violations"
      :process-files="processFiles"
    />

    <Toolbar
      :handle-submit="onUploadForm"
    />
    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import DocumentsForm from '../../components/personalfile/FormUpload.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UploadMixin from '../../mixins/UploadMixin';
import { ref, onMounted } from 'vue'
import isEmpty from 'lodash/isEmpty';

const servicePrefix = 'PersonalFile';

const { mapFields } = createHelpers({
  getterType: 'personalfile/getField',
  mutationType: 'personalfile/updateField'
});

export default {
  name: 'PersonalFileUploadFile',
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    DocumentsForm
  },
  setup() {
    const createForm = ref(null);

    return {
      createForm
    }
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
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),
  },
  created() {
    console.log('created');
    let nodeId = this.$route.params.node;
    if (isEmpty(nodeId)) {
      nodeId = this.currentUser.resourceNode['id']
    }

    console.log(nodeId)
    this.parentResourceNodeId = Number(nodeId);
    this.resourceLinkList = JSON.stringify([{
      gid: this.$route.query.gid,
      sid: this.$route.query.sid,
      cid: this.$route.query.cid,
      visibility: 2,
    }]);
    this.files = [];
  },
  methods: {
    async processFiles(files) {
      /*this.files = [
        ...this.files,
        ...map(files, file => ({
          title: file.name,
          name: file.name,
          size: file.size,
          type: file.type,
          filetype: 'file',
          parentResourceNodeId: this.parentResourceNodeId,
          resourceLinkList: this.resourceLinkList,
          uploadFile: file,
          invalidMessage: this.validate(file),
        }))
      ];*/

      return new Promise((resolve) => {
        for (let i = 0; i < files.length; i++) {
          files[i].title = files[i].name;
          files[i].parentResourceNodeId = this.parentResourceNodeId;
          files[i].resourceLinkList = this.resourceLinkList;
          files[i].uploadFile = files[i];
          this.createFile(files[i]);
        }

        resolve(files);
        /*console.log(file);
        file.title = file.name;
        file.parentResourceNodeId = this.parentResourceNodeId;
        file.resourceLinkList = this.resourceLinkList;
        file.uploadFile = file;
        this.create(file);
        resolve(file);*/


        /*for (let i = 0; i < this.files.length; i++) {
          this.create(this.files[i]);
        }
        resolve(true);*/
      }).then(() => {
        this.files = [];
      });
    },
    validate(file) {
      if (file) {
        return '';
      }

      return 'error';
    },
    ...mapActions('personalfile', ['uploadMany', 'create', 'createFile'])
  }
};
</script>
