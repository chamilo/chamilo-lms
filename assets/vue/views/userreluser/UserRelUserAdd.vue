<template>
  <h2
    v-t="'Add friends'"
    class="mr-auto"
  />
  <hr />
  <BaseToolbar>
    <BaseButton
      icon="back"
      type="black"
      @click="goToBack"
    />
  </BaseToolbar>

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
<script setup>
import { onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import VueMultiselect from "vue-multiselect"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import userService from "../../services/userService"
import userRelUserService from "../../services/userRelUserService"

const store = useStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const user = store.getters["security/getUser"]
const users = ref([])
const isLoadingSelect = ref(false)
const searchQuery = ref("")

const asyncFind = (query) => {
  if (query.toString().length < 3) return
  isLoadingSelect.value = true

  userService
    .findByUsername(query)
    .then(({ items }) => (users.value = items))
    .catch((error) => {
      console.error("Error fetching users:", error)
    })
    .finally(() => {
      isLoadingSelect.value = false
    })
}

const addFriend = (friend) => {
  isLoadingSelect.value = true

  userRelUserService
    .sendFriendRequest(user["@id"], friend["@id"])
    .then(() => {
      showSuccessNotification(t("Friend request sent successfully"))
    })
    .catch((error) => {
      showErrorNotification(t("Failed to send friend request"))
      console.error("Error adding friend:", error)
    })
    .finally(() => {
      isLoadingSelect.value = false
    })
}

const goToBack = () => {
  router.push({ name: "UserRelUserList" })
}

// Lifecycle hooks
onMounted(() => {
  if (route.query.search) {
    searchQuery.value = route.query.search
    asyncFind(searchQuery.value)
  }
})
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
