<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Back to group list')"
          :route="listRoute"
          icon="back"
          only-icon
          type="plain"
        />
      </template>
      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            v-if="data.canManage"
            :label="t('Edit this group')"
            :route="editRoute"
            icon="pencil"
            only-icon
            :tooltip="t('Edit this group')"
            type="secondary"
          />
          <BaseButton
            v-if="data.canManage"
            :label="t('Group members')"
            :route="membersRoute"
            icon="join-group"
            only-icon
            :tooltip="t('Group members')"
            type="primary"
          />
          <BaseButton
            v-if="data.canManage"
            :label="t('Tutors')"
            :route="tutorsRoute"
            icon="human-male-board"
            only-icon
            :tooltip="t('Tutors')"
            type="primary"
          />
          <BaseButton
            v-if="data.canSelfRegister"
            :label="t('Add me to this group')"
            icon="user-add"
            :is-loading="saving"
            type="success"
            @click="confirmSelfAction('self-register')"
          />
          <BaseButton
            v-if="data.canSelfUnregister"
            :label="t('Unsubscribe me from this group.')"
            icon="user-delete"
            :is-loading="saving"
            type="danger"
            @click="confirmSelfAction('self-unregister')"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <header
      v-if="!loading"
      class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <h1 class="text-2xl font-semibold text-gray-90">{{ data.title }}</h1>
      <p
        v-if="data.description"
        class="mt-2 text-sm text-gray-50"
      >
        {{ data.description }}
      </p>
    </header>

    <article
      v-if="data.tools.length > 0"
      class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <h2 class="text-lg font-semibold text-gray-90">{{ t("Tools") }}</h2>
      <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a
          v-for="tool in data.tools"
          :key="tool.url"
          class="flex items-center gap-3 rounded-xl border border-gray-20 bg-gray-10 p-4 hover:bg-white hover:shadow-sm"
          :href="tool.url"
        >
          <BaseIcon
            :icon="tool.icon"
            size="big"
          />
          <span class="min-w-0">
            <span class="block font-medium text-gray-90">{{ t(tool.label) }}</span>
            <span
              v-if="tool.subtitle"
              class="block truncate text-xs text-gray-50"
            >
              {{ tool.subtitle }}
            </span>
          </span>
        </a>
      </div>
    </article>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Tutors") }}</h2>
          <BaseButton
            v-if="data.canManage"
            :label="t('Edit')"
            :route="tutorsRoute"
            icon="pencil"
            only-icon
            size="small"
            type="secondary-text"
          />
        </div>
        <ul
          v-if="data.tutors.length > 0"
          class="divide-y divide-gray-20"
        >
          <li
            v-for="user in data.tutors"
            :key="user.id"
            class="flex items-center gap-3 py-3"
          >
            <BaseUserAvatar
              :alt="user.name"
              :image-url="user.pictureUrl"
            />
            <div>
              <div class="font-medium text-gray-90">{{ user.name }}</div>
              <div class="text-xs text-gray-50">{{ user.username }}</div>
            </div>
          </li>
        </ul>
        <p
          v-else
          class="text-sm text-gray-50"
        >
          {{ t("No tutors") }}
        </p>
      </article>

      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Group members") }}</h2>
          <BaseButton
            v-if="data.canManage"
            :label="t('Edit')"
            :route="membersRoute"
            icon="pencil"
            only-icon
            size="small"
            type="secondary-text"
          />
        </div>
        <ul
          v-if="data.members.length > 0"
          class="divide-y divide-gray-20"
        >
          <li
            v-for="user in data.members"
            :key="user.id"
            class="flex items-center gap-3 py-3"
          >
            <BaseUserAvatar
              :alt="user.name"
              :image-url="user.pictureUrl"
            />
            <div>
              <div class="font-medium text-gray-90">{{ user.name }}</div>
              <div class="text-xs text-gray-50">{{ user.username }}</div>
            </div>
          </li>
        </ul>
        <p
          v-else
          class="text-sm text-gray-50"
        >
          {{ t("No users") }}
        </p>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()
const { cid, sid, contextQuery } = useRouteCourseContext()
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const data = reactive({
  title: "",
  description: "",
  canManage: false,
  canSelfRegister: false,
  canSelfUnregister: false,
  tools: [],
  tutors: [],
  members: [],
})
const groupId = computed(() => Number(route.params.groupId))
const requestParams = computed(() => ({ cid: cid.value, sid: sid.value, gid: groupId.value }))
const listRoute = computed(() => ({
  name: "CourseUserGroups",
  params: parentParams(),
  query: { ...contextQuery.value, gid: 0 },
}))
const editRoute = computed(() => ({
  name: "CourseGroupEdit",
  params: route.params,
  query: { ...contextQuery.value, gid: groupId.value },
}))
const membersRoute = computed(() => ({
  name: "CourseGroupMembers",
  params: route.params,
  query: { ...contextQuery.value, gid: groupId.value },
}))
const tutorsRoute = computed(() => ({
  name: "CourseGroupTutors",
  params: route.params,
  query: { ...contextQuery.value, gid: groupId.value },
}))

function parentParams() {
  const params = { ...route.params }
  delete params.groupId
  return params
}

async function load() {
  loading.value = true
  errorMessage.value = ""
  try {
    Object.assign(data, await courseGroupService.getDetail(groupId.value, requestParams.value))
  } catch (error) {
    console.error("[CourseGroup] Failed to load group area", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function confirmSelfAction(action) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () => runSelfAction(action),
  })
}

async function runSelfAction(action) {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ""
  try {
    await courseGroupService.action(action, { groupId: groupId.value }, requestParams.value)
    await load()
  } catch (error) {
    console.error("[CourseGroup] Self-registration action failed", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
