<template>
  <div>
    <dashboard
        :uppy="uppy"
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          //metaFields: [{id: 'name', name: 'Name', placeholder: 'file name'}],
          proudlyDisplayPoweredByUppy: false,
          width: '100%'
        }"
    />
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
//import FormUpload from '../../components/documents/FormUpload.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UploadMixin from '../../mixins/UploadMixin';
import { ref, onMounted } from 'vue'

const servicePrefix = 'Documents';

const { mapFields } = createHelpers({
  getterType: 'documents/getField',
  mutationType: 'documents/updateField'
});

import '@uppy/core/dist/style.css'
import '@uppy/dashboard/dist/style.css'
import '@uppy/image-editor/dist/style.css'

import Uppy from '@uppy/core'
import Webcam from '@uppy/webcam'
const XHRUpload = require('@uppy/xhr-upload');
import { Dashboard } from '@uppy/vue'
import {useRoute} from "vue-router";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility";
const ImageEditor = require('@uppy/image-editor');

export default {
  name: 'DocumentsUpload',
  servicePrefix,
  mixins: [UploadMixin],
  components: {
    Loading,
    Toolbar,
    //FormUpload,
    Dashboard
  },
  setup() {
    const createForm = ref(null);
    const parentResourceNodeId = ref(null);
    const resourceLinkList = ref(null);
    const route = useRoute();

    parentResourceNodeId.value = Number(route.params.node);
    resourceLinkList.value = JSON.stringify([{
      gid: route.query.gid,
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED,
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
              endpoint: '/api/documents',
              formData: true,
              fieldName: 'uploadFile'
            }
        )
    ;

    uppy.value.setMeta({
      filetype: 'file',
      parentResourceNodeId: parentResourceNodeId.value,
      resourceLinkList: resourceLinkList.value,
    });

    return {
      createForm,
      uppy
    }
  },
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
      gid: this.$route.query.gid,
      sid: this.$route.query.sid,
      cid: this.$route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED,
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
    ...mapActions('documents', ['uploadMany', 'createFile'])
  }
};
</script>
