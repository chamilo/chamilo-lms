<template>
  <Toolbar :handle-submit="onSendForm" />
  <ToolIntroForm
    v-if="item"
    ref="updateForm"
    :errors="violations"
    :values="item"
  />
  <Loading :visible="isLoading || deleteLoading" />
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import ToolIntroForm from "../../components/ctoolintro/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import UpdateMixin from "../../mixins/UpdateMixin"
import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import useVuelidate from "@vuelidate/core"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useCidReq } from "../../composables/cidReq"
import cToolIntroService from "../../services/cToolIntroService"

const servicePrefix = "ctoolintro"

export default {
  name: "ToolIntroUpdate",
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    ToolIntroForm,
  },
  mixins: [UpdateMixin],
  setup() {
    const route = useRoute()
    const router = useRouter()
    const item = ref({})
    const { cid, sid } = useCidReq()

    let toolId = route.query.ctoolId
    let ctoolintroId = route.query.ctoolintroIid

    // Get the current intro text.
    cToolIntroService
      .findById(ctoolintroId)
      .then((toolIntroInfo) => {
        item.value["introText"] = toolIntroInfo.introText
        item.value["parentResourceNodeId"] = Number(route.query.parentResourceNodeId)
      })
      .catch(function (error) {
        console.error(error)
      })

    item.value["courseTool"] = "/api/c_tools/" + toolId
    item.value["resourceLinkList"] = [
      {
        sid,
        cid,
        visibility: RESOURCE_LINK_PUBLISHED, // visible by default
      },
    ]

    function onUpdated() {
      router.go(-1)
    }

    return { v$: useVuelidate(), item, onUpdated }
  },
  computed: {
    ...mapFields("ctoolintro", {
      deleteLoading: "isLoading",
      isLoading: "isLoading",
      error: "error",
      updated: "updated",
      violations: "violations",
    }),
    ...mapGetters("ctoolintro", ["find"]),
    ...mapGetters({
      isCurrentTeacher: "security/isCurrentTeacher",
    }),
  },
  methods: {
    ...mapActions("ctoolintro", {
      createReset: "resetCreate",
      deleteItem: "del",
      delReset: "resetDelete",
      retrieve: "load",
      update: "update",
      updateWithFormData: "updateWithFormData",
      updateReset: "resetUpdate",
    }),
  },
}
</script>
