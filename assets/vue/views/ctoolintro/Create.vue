<template>
  <Toolbar :handle-submit="onSendForm" />

  <ToolIntroForm
    ref="createForm"
    :errors="violations"
    :values="item"
  />
  <Loading :visible="isLoading" />
</template>

<script>
import { mapActions } from "vuex"
import { createHelpers } from "vuex-map-fields"
import ToolIntroForm from "../../components/ctoolintro/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import CreateMixin from "../../mixins/CreateMixin"
import { ref } from "vue"
import useVuelidate from "@vuelidate/core"
import { useRoute, useRouter } from "vue-router"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink.js"
import cToolIntroService from "../../services/cToolIntroService"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const servicePrefix = "ctoolintro"

const { mapFields } = createHelpers({
  getterType: "ctoolintro/getField",
  mutationType: "ctoolintro/updateField",
})

export default {
  name: "ToolIntroCreate",
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    ToolIntroForm,
  },
  mixins: [CreateMixin],
  setup() {
    const users = ref([])
    const isLoadingSelect = ref(false)
    const item = ref({})
    const route = useRoute()
    const router = useRouter()
    const securityStore = useSecurityStore()

    const { isAuthenticated, user } = storeToRefs(securityStore)

    const courseId = route.query.cid
    const sessionId = route.query.sid
    const tool = route.query.tool || "course_homepage"
    const ctoolId = route.params.courseTool

    item.value.parentResourceNodeId = Number(route.query.parentResourceNodeId)
    item.value.courseTool = `/api/c_tools/${ctoolId}`
    item.value.resourceLinkList = [
      {
        sid: sessionId,
        cid: courseId,
        visibility: RESOURCE_LINK_PUBLISHED,
      },
    ]

    async function getIntro() {
      try {
        const response = await cToolIntroService.getToolIntro(courseId, {
          cid: courseId,
          sid: sessionId,
          tool,
        })

        const intro = response.data || response

        if (intro?.introText) {
          item.value.introText = intro.introText
        }
      } catch (error) {
        console.error("Error loading tool introduction:", error)
      }
    }

    getIntro()

    function onCreated(createdItem) {
      cToolIntroService
        .addToolIntro(courseId, {
          tool,
          iid: createdItem.iid,
          introText: item.value.introText || "",
          cid: courseId,
          sid: sessionId,
          resourceLinkList: item.value.resourceLinkList,
        })
        .then(() => {
          router.go(-1)
        })
        .catch((error) => {
          console.error("Error creating tool introduction:", error)
        })
    }

    return {
      v$: useVuelidate(),
      users,
      isLoadingSelect,
      item,
      onCreated,
      currentUser: user,
      isAuthenticated,
    }
  },
  computed: {
    ...mapFields(["error", "isLoading", "created", "violations"]),
  },
  methods: {
    ...mapActions("ctoolintro", ["create", "createWithFormData"]),
  },
}
</script>
