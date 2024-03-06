<template>
  <ShowLinks
    :edit-status="editStatus"
    :item="model"
    :show-status="showStatus"
  />

  <div class="field">
    <VueMultiselect
      v-if="showShareWithUser"
      v-model="selectedUsers"
      :internal-search="false"
      :loading="isLoading"
      :multiple="true"
      :options="users"
      :placeholder="$t('Share with User')"
      :searchable="true"
      label="username"
      :limit="3"
      track-by="id"
      @select="addUser"
      @search-change="asyncFind"
    />
  </div>
</template>

<script setup>
import ShowLinks from "../../components/resource_links/ShowLinks.vue"
import { ref } from "vue"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import VueMultiselect from "vue-multiselect"
import isEmpty from "lodash/isEmpty"
import { RESOURCE_LINK_PUBLISHED } from "./visibility.js"
import { useSecurityStore } from "../../store/securityStore"

// eslint-disable-next-line vue/require-prop-types
const model = defineModel()

const props = defineProps({
  editStatus: {
    type: Boolean,
    required: false,
    default: true,
  },
  showStatus: {
    type: Boolean,
    required: false,
    default: true,
  },
  linksType: {
    type: String,
    required: true,
    default: "user",
  },
  showShareWithUser: {
    type: Boolean,
    required: true,
    default: true,
  },
  linkListName: {
    type: String,
    required: false,
    default: "resourceLinkListFromEntity",
  },
})

const users = ref([])
const selectedUsers = ref([])
const isLoading = ref(false)

const securityStore = useSecurityStore()

function addUser(userResult) {
  if (isEmpty(model.value[props.linkListName])) {
    model.value[props.linkListName] = []
  }

  const someLink = model.value[props.linkListName].some((link) => link.user.username === userResult.username)

  if (someLink) {
    return
  }

  model.value[props.linkListName].push({
    uid: userResult.id,
    user: { username: userResult.username },
    visibility: RESOURCE_LINK_PUBLISHED,
  })
}

function findUsers(query) {
  axios
    .get(ENTRYPOINT + "users", {
      params: {
        username: query,
      },
    })
    .then((response) => {
      isLoading.value = false
      let data = response.data
      users.value = data["hydra:member"]
    })
    .catch(function (error) {
      isLoading.value = false
      console.log(error)
    })
}

function findUserRelUsers(query) {
  axios
    .get(ENTRYPOINT + "user_rel_users", {
      params: {
        user: securityStore.user["id"],
        "friend.username": query,
      },
    })
    .then((response) => {
      isLoading.value = false

      users.value = response.data["hydra:member"].map((member) => member.friend)
    })
    .catch(function () {
      isLoading.value = false
    })
}

function findStudentsInCourse(query) {
  const searchParams = new URLSearchParams(window.location.search)
  const cId = parseInt(searchParams.get("cid"))
  const sId = parseInt(searchParams.get("sid"))

  if (!cId && !sId) {
    return
  }

  let endpoint = ENTRYPOINT
  let params = {
    "user.username": query,
  }

  if (sId) {
    params.session = endpoint + `sessions/${sId}`
  }

  if (cId) {
    if (sId) {
      endpoint += `session_rel_course_rel_users`
      params.course = endpoint + `courses/${cId}`
    } else {
      endpoint += `courses/${cId}/users`
    }
  } else {
    endpoint += `session_rel_users`
  }

  axios
    .get(endpoint, {
      params,
    })
    .then((response) => {
      isLoading.value = false

      users.value = response.data["hydra:member"].map((member) => member.user)
    })
    .catch(function () {
      isLoading.value = false
    })
}

function asyncFind(query) {
  if (query.toString().length < 3) {
    return
  }

  isLoading.value = true

  switch (props.linksType) {
    case "users":
      findUsers(query)
      break

    case "user_rel_users":
      findUserRelUsers(query)
      break

    case "course_students":
      findStudentsInCourse(query)
      break
  }
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
