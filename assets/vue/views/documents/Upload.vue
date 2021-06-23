<template>
  <div>
<!--    <FormUpload-->
<!--      ref="createForm"-->
<!--      :values="files"-->
<!--      :parentResourceNodeId="parentResourceNodeId"-->
<!--      :resourceLinkList="resourceLinkList"-->
<!--      :errors="violations"-->
<!--      :process-files="processFiles"-->
<!--    />-->

    <dashboard
        :uppy="uppy"
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          //metaFields: [{id: 'name', name: 'Name', placeholder: 'file name'}],
          proudlyDisplayPoweredByUppy: false,
        }"
    />

<!--    <Toolbar-->
<!--      :handle-submit="onUploadForm"-->
<!--    />-->
<!--    <Loading :visible="isLoading" />-->
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import FormUpload from '../../components/documents/FormUpload.vue';
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
const ImageEditor = require('@uppy/image-editor');

export default {
  name: 'DocumentsCreate',
  servicePrefix,
  mixins: [UploadMixin],
  components: {
    Loading,
    Toolbar,
    FormUpload,
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
      visibility: 2,
    }]);

    let uppy = ref();
    uppy.value = new Uppy()
        .use(Webcam)
        .use(ImageEditor, {
          //target: Dashboard,
          //quality: 0.8
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

        /*.on('file-added', (file) => {
          console.log(file.id);
          console.log(parentResourceNodeId.value);
          uppy.value.setFileMeta(file.id, {
            size: file.size,
            filetype: 'file',
            parentResourceNodeId: parentResourceNodeId.value,
            resourceLinkList: resourceLinkList.value,
          })
        })*/
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
    ...mapActions('documents', ['uploadMany', 'create', 'createFile'])
  }
};
</script>
