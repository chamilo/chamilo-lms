<template>
  <router-view></router-view>
</template>

<script>
import { mapGetters } from "vuex"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const servicePrefix = "PersonalFile"

export default {
  name: "PersonalFileHome",
  servicePrefix,
  components: {
    Loading,
    Toolbar,
  },
  setup() {
    const securityStore = useSecurityStore()
    const route = useRoute()
    const router = useRouter()

    const { isAuthenticated, isAdmin, user } = storeToRefs(securityStore)

    router.push({ name: `PersonalFileList`, params: { node: user.value.resourceNode["id"] } }).catch(() => {})

    return {
      currentUser: user,
      isAdmin,
      isAuthenticated,
    }
  },
  computed: {
    // From crud.js list function
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),
  },
}
</script>
