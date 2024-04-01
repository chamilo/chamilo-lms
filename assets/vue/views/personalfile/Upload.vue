<template>
  <div>
    <dashboard
      :plugins="['Webcam', 'ImageEditor']"
      :props="{
        proudlyDisplayPoweredByUppy: false,
        width: '100%',
      }"
      :uppy="uppy"
    />
  </div>
</template>

<script>
import { mapActions } from "vuex"
import { createHelpers } from "vuex-map-fields"
import UploadMixin from "../../mixins/UploadMixin"
import { ref } from "vue"
import isEmpty from "lodash/isEmpty"

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"

import Uppy from "@uppy/core"
import Webcam from "@uppy/webcam"
import { Dashboard } from "@uppy/vue"
import { useRoute, useRouter } from "vue-router"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const XHRUpload = require("@uppy/xhr-upload")

const ImageEditor = require("@uppy/image-editor")

const servicePrefix = "PersonalFile"

const { mapFields } = createHelpers({
  getterType: "personalfile/getField",
  mutationType: "personalfile/updateField",
})

export default {
  name: "PersonalFileUploadFile",
  servicePrefix,
  components: {
    Dashboard,
  },
  setup() {
    const parentResourceNodeId = ref(null)
    const route = useRoute()
    const router = useRouter();
    const securityStore = useSecurityStore()

    const { user, isAuthenticated, isAdmin } = storeToRefs(securityStore)

    parentResourceNodeId.value = user.value.resourceNode["id"]

    if (route.params.node) {
      parentResourceNodeId.value = Number(route.params.node)
    }

    let uppy = ref()
    uppy.value = new Uppy()
      .use(Webcam)
      .use(ImageEditor, {
        cropperOptions: {
          viewMode: 1,
          background: false,
          autoCropArea: 1,
          responsive: true,
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
          cropWidescreenVertical: true,
        },
      })
      .use(XHRUpload, {
        endpoint: ENTRYPOINT + "personal_files",
        formData: true,
        fieldName: "uploadFile",
      })

    uppy.value.setMeta({
      filetype: "file",
      parentResourceNodeId: parentResourceNodeId.value,
    })

    uppy.value.on("complete", (result) => {
      router.push({ name: "PersonalFileList" });
    });

    return {
      uppy,
      currentUser: user,
      isAdmin,
      isAuthenticated,
    }
  },
  mixins: [UploadMixin],
  data() {
    return {
      files: [],
      parentResourceNodeId: 0,
    }
  },
  computed: {
    ...mapFields(["error", "isLoading", "created", "violations"]),
  },
  created() {
    let nodeId = this.$route.params.node
    if (isEmpty(nodeId)) {
      nodeId = this.currentUser.resourceNode["id"]
    }
    this.parentResourceNodeId = Number(nodeId)
  },
  methods: {
    ...mapActions("personalfile", ["uploadMany", "createFile"]),
  },
}
</script>
