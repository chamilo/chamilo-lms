<template>
  <ButtonToolbar>
    <BaseButton
      icon="back"
      type="black"
      @click="goToBack"
    />
  </ButtonToolbar>

  <div class="flex flex-row pt-2">
    <div class="w-full">
      <div
        v-t="'Search'"
        class="text-h4 q-mb-md"
      />

      <VueMultiselect
        :internal-search="false"
        :loading="isLoadingSelect"
        :multiple="true"
        :options="users"
        :placeholder="t('Add')"
        :searchable="true"
        label="username"
        limit="3"
        limit-text="3"
        track-by="id"
        @select="addFriend"
        @search-change="asyncFind"
      />
    </div>
  </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import { useStore } from "vuex"

import VueMultiselect from "vue-multiselect"
import { computed, ref } from "vue"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import useVuelidate from "@vuelidate/core"
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"

export default {
  name: "UserRelUserAdd",
  servicePrefix: "userreluser",
  components: {
    BaseButton,
    ButtonToolbar,
    VueMultiselect,
  },
  setup() {
    const users = ref([])
    const isLoadingSelect = ref(false)
    const store = useStore()
    const router = useRouter()
    const { t } = useI18n()
    const user = store.getters["security/getUser"]

    function asyncFind(query) {
      if (query.toString().length < 3) {
        return
      }

      isLoadingSelect.value = true
      axios
        .get(ENTRYPOINT + "users", {
          params: {
            username: query,
          },
        })
        .then((response) => {
          isLoadingSelect.value = false
          let data = response.data
          users.value = data["hydra:member"]
        })
        .catch(function (error) {
          isLoadingSelect.value = false
          console.log(error)
        })
    }

    function addFriend(friend) {
      axios
        .post(ENTRYPOINT + "user_rel_users", {
          user: user["@id"],
          friend: friend["@id"],
          relationType: 10,
        })
        .then((response) => {
          console.log(response)
          isLoadingSelect.value = false
        })
        .catch(function (error) {
          isLoadingSelect.value = false
          console.log(error)
        })
    }

    const goToBack = () => {
      router.push({ name: "UserRelUserList" })
    }

    const selectedItems = ref([])
    const itemDialog = ref(false)
    const deleteItemDialog = ref(false)
    const deleteMultipleDialog = ref(false)
    const item = ref({})
    const submitted = ref(false)

    const isAuthenticated = computed(() => store.getters["security/isAuthenticated"])
    const isAdmin = computed(() => store.getters["security/isAdmin"])
    const currentUser = computed(() => store.getters["security/getUser"])

    return {
      v$: useVuelidate(),
      users,
      asyncFind,
      addFriend,
      isLoadingSelect,
      goToBack,
      t,
      selectedItems,
      itemDialog,
      deleteItemDialog,
      deleteMultipleDialog,
      item,
      submitted,
      isAuthenticated,
      isAdmin,
      currentUser,
    }
  },
}
</script>
