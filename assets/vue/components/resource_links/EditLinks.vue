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
      :limit="3"
      :loading="isLoading"
      :multiple="true"
      :options="users"
      :placeholder="$t('Share with User')"
      :searchable="true"
      label="username"
      track-by="id"
      @select="addUser"
      @search-change="asyncFind"
    />
  </div>
</template>

<script setup>
import ShowLinks from "../../components/resource_links/ShowLinks.vue"
import { ref } from "vue"
import VueMultiselect from "vue-multiselect"
import isEmpty from "lodash/isEmpty"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink.js"
import { useSecurityStore } from "../../store/securityStore"
import userService from "../../services/userService"
import userRelUserService from "../../services/userRelUserService"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import sessionRelCourseRelUserService from "../../services/sessionRelCourseRelUserService"
import sessionRelUserService from "../../services/sessionRelUserService"
import courseRelUserService from "../../services/courseRelUserService"

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
const cidReqStore = useCidReqStore()

const { course, session } = storeToRefs(cidReqStore)

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
  userService
    .findBySearchTerm(query)
    .then(({ items }) => (users.value = items))
    .finally(() => (isLoading.value = false))
}

function findUserRelUsers(query) {
  userRelUserService
    .searchRelationshipByUsername(securityStore.user["@id"], query)
    .then(({ items }) => (users.value = items.map((relationship) => relationship.friend)))
    .finally(() => (isLoading.value = false))
}

function findStudentsInCourse(query) {
  const searchParams = new URLSearchParams(window.location.search)
  const cId = parseInt(searchParams.get("cid"))
  const sId = parseInt(searchParams.get("sid"))

  if (!course.value && !session.value) {
    return
  }

  let params = {
    "user.username": query,
  }

  if (session.value) {
    params.session = session.value["@id"]
  }

  let service

  if (cId) {
    params.course = course.value["@id"]

    if (sId) {
      service = sessionRelCourseRelUserService.findAll
    } else {
      service = courseRelUserService.findAll
    }
  } else {
    service = sessionRelUserService.findAll
  }

  service(params)
    .then(({ items }) => (users.value = items.map((membership) => membership.user)))
    .finally(() => (isLoading.value = false))
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
