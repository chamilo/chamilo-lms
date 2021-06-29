<template>
  <div>
    <dashboard
        :uppy="uppy"
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          proudlyDisplayPoweredByUppy: false,
          width: '100%'
        }"
    />
  </div>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import UploadMixin from '../../mixins/UploadMixin';
import { ref, onMounted, computed } from 'vue'
import isEmpty from 'lodash/isEmpty';

import '@uppy/core/dist/style.css'
import '@uppy/dashboard/dist/style.css'
import '@uppy/image-editor/dist/style.css'

import Uppy from '@uppy/core'
import Webcam from '@uppy/webcam'
const XHRUpload = require('@uppy/xhr-upload');
import { Dashboard } from '@uppy/vue'
import {useRoute} from "vue-router";
const ImageEditor = require('@uppy/image-editor');

const servicePrefix = 'PersonalFile';

const { mapFields } = createHelpers({
  getterType: 'personalfile/getField',
  mutationType: 'personalfile/updateField'
});

export default {
  name: 'PersonalFileUploadFile',
  servicePrefix,
  components: {
    Dashboard
  },
  setup() {
    const parentResourceNodeId = ref(null);
    const resourceLinkList = ref(null);
    const route = useRoute();

    const store = useStore();
    const user = computed(() => store.getters['security/getUser']);

    parentResourceNodeId.value = Number(route.params.node);
    resourceLinkList.value = JSON.stringify([{
      uid: route.query.gid,
      visibility: 2,
    }]);

    let uppy = ref();
    uppy.value = new Uppy()
        .use(Webcam)
        .use(ImageEditor, {
          cropperOptions: {
            viewMode: 1,
            background: false,
            autoCropArea: 1,
            responsive: true
          },
          actions: {
            revert: true,
            rotate: true,
            granularRotate: true,
            flip: true,
            zoomIn: true,
            zoomOut: true,
            cropSquare: true,
            cropWidescreen: true,
            cropWidescreenVertical: true
          }
        })
        .use(
            XHRUpload, {
              endpoint: '/api/personal_files',
              formData: true,
              fieldName: 'uploadFile'
            }
        )
    ;

    uppy.value.setMeta({
      filetype: 'file',
      parentResourceNodeId: user.value.resourceNode['id'],
    });

    return {
      uppy
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
      visibility: 2,
    }]);
    this.files = [];
  },
  methods: {
    async processFiles(files) {
      return new Promise((resolve) => {
        for (let i = 0; i < files.length; i++) {
          files[i].title = files[i].name;
          files[i].parentResourceNodeId = this.parentResourceNodeId;
          files[i].resourceLinkList = this.resourceLinkList;
          files[i].uploadFile = files[i];
          this.createFile(files[i]);
        }

        resolve(files);
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
    ...mapActions('personalfile', ['uploadMany', 'createFile'])
  }
};
</script>
