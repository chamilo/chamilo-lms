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
import { mapActions, mapGetters } from "vuex"
import { createHelpers } from "vuex-map-fields"
import ToolIntroForm from "../../components/ctoolintro/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import CreateMixin from "../../mixins/CreateMixin"
import { ref } from "vue"
import useVuelidate from "@vuelidate/core"
import { useRoute, useRouter } from "vue-router"
import isEmpty from "lodash/isEmpty"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility.js"
import axios from "axios"
import { useCidReq } from "../../composables/cidReq"

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

    let id = route.params.id
    if (isEmpty(id)) {
      id = route.query.id
    }

    const { cid } = useCidReq()

    let courseId = route.query.cid
    let sessionId = route.query.sid
    let ctoolId = route.params.courseTool

    async function getIntro() {
      axios
        .get("/course/" + courseId + "/getToolIntro", {
          params: {
            cid: courseId,
            sid: sessionId,
          },
        })
        .then((response) => {
          if (response.data) {
            if (response.data.introText) {
              item.value["introText"] = response.data.introText
            }
          }
        })
        .catch(function (error) {
          console.log(error)
        })
    }

    item.value["parentResourceNodeId"] = Number(route.query.parentResourceNodeId)
    item.value["courseTool"] = "/api/c_tools/" + ctoolId

    item.value["resourceLinkList"] = [
      {
        sid: route.query.sid,
        cid: route.query.cid,
        visibility: RESOURCE_LINK_PUBLISHED, // visible by default
      },
    ]

    getIntro()

    function onCreated(item) {
      //showNotification(t("Updated"))
      axios
        .post("/course/" + cid + "/addToolIntro", {
          iid: item.iid,
          cid: route.query.cid,
          sid: route.query.sid,
        })
        .then(() => {
          router.go(-1)
        })
        .catch(function (error) {
          console.log(error)
        })
    }

    return { v$: useVuelidate(), users, isLoadingSelect, item, onCreated }
  },
  computed: {
    ...mapFields(["error", "isLoading", "created", "violations"]),
    ...mapGetters({
      isAuthenticated: "security/isAuthenticated",
      currentUser: "security/getUser",
    }),
  },
  methods: {
    ...mapActions("ctoolintro", ["create", "createWithFormData"]),
  },
}
</script>
