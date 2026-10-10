<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import { useRoute, useRouter } from "vue-router"

import Dialog from "primevue/dialog"

import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import SkillProfileDialog from "../../components/skill/SkillProfileDialog.vue"
import SkillProfileMatches from "../../components/skill/SkillProfileMatches.vue"
import SkillWheelGraph from "../../components/skill/SkillWheelGraph.vue"
import SkillWheelProfileList from "../../components/skill/SkillWheelProfileList.vue"

import { useNotification } from "../../composables/notification"
import * as skillService from "../../services/skillService"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification } = useNotification()

const securityStore = useSecurityStore()
const { isAdmin, isHRM } = storeToRefs(securityStore)

const canUseProfiles = computed(() => isAdmin.value || isHRM.value)

const profileListEL = ref()
const wheelEl = ref()
const profileMatchesEl = ref()

const foundSkills = ref([])
const showSearchTools = ref(false)
const showHelp = ref(false)
const showProfiles = ref(false)
const showSkilProfileForm = ref(false)
const showProfileMatches = ref(false)
const showSkillDetail = ref(false)
const focusMode = ref(false)
const skillDetail = ref(null)
const skillGradebookLinks = ref([])
const isLoadingDetail = ref(false)

let previousBodyOverflow = ""

async function onSkillDetail(detail) {
  skillDetail.value = detail
  skillGradebookLinks.value = []
  showSkillDetail.value = true
  isLoadingDetail.value = true

  try {
    const data = await skillService.getSkillDetail(detail.id)
    skillGradebookLinks.value = data.gradebookLinks || []
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoadingDetail.value = false
  }
}

function closeSkillDetail() {
  showSkillDetail.value = false
}

function addSkillToSearch(skillId, skillName) {
  const alreadyAdded = foundSkills.value.some((s) => s.id === skillId)
  if (!alreadyAdded) {
    foundSkills.value = [...foundSkills.value, { id: skillId, name: skillName, value: `/api/skills/${skillId}` }]
  }
  closeSkillDetail()
}

function gradebookUrl(link) {
  const query = new URLSearchParams({
    view: "overview",
    cid: String(link.courseId),
    sid: String(link.sessionId || 0),
    gid: "0",
  })

  return `/gradebook/redirect?${query.toString()}`
}

/**
 * @param {string} query
 * @returns {Promise<Object[]>}
 */
async function findSkills(query) {
  try {
    const { items } = await skillService.findAll({ title: query })
    return items.map((item) => ({ name: item.title, value: item["@id"], ...item }))
  } catch (e) {
    showErrorNotification(e)
    return []
  }
}

function locateSkill(skillId) {
  showProfileMatches.value = false
  showSearchTools.value = false
  closeSkillDetail()
  wheelEl.value?.showSkill(skillId)
}

async function onClickSearchProfileMatches() {
  if (!canUseProfiles.value) {
    showErrorNotification(new Error("Access denied: profile matches are restricted to HR/Admin users."))
    return
  }

  showSearchTools.value = false
  showProfileMatches.value = true
  closeSkillDetail()
  await profileMatchesEl.value.searchProfileMatches(foundSkills.value)
}

async function onClickViewSkillWheel() {
  showSearchTools.value = false
  showProfileMatches.value = false
  closeSkillDetail()
  wheelEl.value?.showRoot()
}

async function onWheelReady() {
  const skillId = Number(route.query.skillId)
  if (!Number.isInteger(skillId) || skillId <= 0) {
    return
  }

  wheelEl.value?.showSkill(skillId)

  const nextQuery = { ...route.query }
  delete nextQuery.skillId
  await router.replace({ query: nextQuery })
}

function onProfileSaved() {
  profileListEL.value?.loadProfiles()
}

function openSearchFromProfiles() {
  showProfiles.value = false
  showSearchTools.value = true
}

async function onSearchProfile(profile) {
  if (!canUseProfiles.value) {
    showErrorNotification(new Error("Access denied: profiles are restricted to HR/Admin users."))
    return
  }

  const profileSkills = profile.skills.map((skillRelProfile) => skillRelProfile.skill)

  showProfiles.value = false
  showSearchTools.value = false
  showProfileMatches.value = true
  closeSkillDetail()

  await profileMatchesEl.value.searchProfileMatches(profileSkills)
}

async function toggleFocusMode() {
  focusMode.value = !focusMode.value
  await nextTick()
  wheelEl.value?.fitGraphToViewport()
}

function onGlobalKeydown(event) {
  if ("Escape" === event.key && focusMode.value) {
    event.preventDefault()
    focusMode.value = false
    return
  }

  const target = event.target
  const isTyping =
    target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target?.isContentEditable

  if (isTyping) {
    return
  }

  const isCommandSearch = (event.ctrlKey || event.metaKey) && "k" === event.key.toLowerCase()
  if ("/" !== event.key && !isCommandSearch) {
    return
  }

  event.preventDefault()
  showSearchTools.value = true
}

watch(focusMode, async (enabled) => {
  if (enabled) {
    previousBodyOverflow = document.body.style.overflow
    document.body.style.overflow = "hidden"
  } else {
    document.body.style.overflow = previousBodyOverflow
  }

  await nextTick()
  window.requestAnimationFrame(() => wheelEl.value?.fitGraphToViewport())
})

onMounted(() => {
  window.addEventListener("keydown", onGlobalKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener("keydown", onGlobalKeydown)
  document.body.style.overflow = previousBodyOverflow
})
</script>

<template>
  <div
    data-testid="skill-wheel-workspace"
    :data-focus-mode="focusMode ? 'true' : 'false'"
    :class="['relative', focusMode ? 'fixed inset-0 z-[60] bg-white p-2 md:p-3' : '']"
  >
    <div class="absolute right-4 top-16 z-40 flex flex-col gap-2 md:right-6 md:top-20">
      <BaseButton
        :aria-label="t('What skills are you looking for?')"
        :title="t('What skills are you looking for?')"
        icon="search"
        only-icon
        size="small"
        type="primary"
        @click="showSearchTools = true"
      />
      <BaseButton
        :aria-label="t('View skills wheel')"
        :title="t('View skills wheel')"
        icon="wheel"
        only-icon
        size="small"
        type="secondary"
        @click="onClickViewSkillWheel"
      />
      <BaseButton
        v-if="canUseProfiles"
        data-testid="skill-wheel-profiles-button"
        :aria-label="t('Skill profiles')"
        :title="t('Skill profiles')"
        icon="card-account-details-outline"
        only-icon
        size="small"
        type="primary"
        @click="showProfiles = true"
      />
      <BaseButton
        :aria-label="focusMode ? 'Exit focus mode' : 'Focus mode'"
        :title="focusMode ? 'Exit focus mode' : 'Focus mode'"
        :icon="focusMode ? 'eye-off' : 'eye-on'"
        only-icon
        size="small"
        type="plain"
        @click="toggleFocusMode"
      />
      <BaseButton
        aria-label="Help"
        title="Help"
        icon="information"
        only-icon
        size="small"
        type="plain"
        @click="showHelp = true"
      />
    </div>

    <aside
      v-if="showSkillDetail && skillDetail"
      data-testid="skill-wheel-inspector"
      class="absolute right-16 top-24 z-40 max-h-[calc(100%-7rem)] w-[min(24rem,calc(100%-5rem))] overflow-auto rounded-2xl border border-gray-20 bg-white/95 shadow-xl backdrop-blur md:right-20 md:top-24"
    >
      <div
        class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-gray-20 bg-white/95 p-4 backdrop-blur"
      >
        <div class="min-w-0">
          <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-50">
            {{ t("Skills") }}
          </div>
          <h3 class="truncate text-lg font-semibold text-gray-90">
            {{ skillDetail.name }}
          </h3>
        </div>
        <BaseButton
          aria-label="Close"
          title="Close"
          icon="close"
          only-icon
          size="small"
          type="plain"
          @click="closeSkillDetail"
        />
      </div>

      <div class="flex flex-col gap-4 p-4 text-sm text-gray-70">
        <div
          v-if="skillDetail.parentPath"
          class="rounded-xl border border-blue-20 bg-blue-10/70 p-3"
        >
          <div class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-50">
            {{ t("Parent") }}
          </div>
          <div class="font-medium text-gray-90">
            {{ skillDetail.parentPath }}
          </div>
        </div>

        <div v-if="skillDetail.shortCode">
          <span class="font-semibold text-gray-90">{{ t("Code") }}:</span>
          {{ skillDetail.shortCode }}
        </div>

        <div v-if="skillDetail.description">
          <div class="mb-1 font-semibold text-gray-90">
            {{ t("Description") }}
          </div>
          <div
            dir="auto"
            class="leading-relaxed"
            v-html="skillDetail.description"
          />
        </div>

        <div class="border-t border-gray-20 pt-4">
          <h4 class="mb-2 font-semibold text-gray-90">
            {{ t("This skill can be obtained through:") }}
          </h4>
          <div
            v-if="isLoadingDetail"
            class="text-gray-500"
          >
            {{ t("Loading") }}...
          </div>
          <ul
            v-else-if="skillGradebookLinks.length"
            class="list-disc ps-4"
          >
            <li
              v-for="link in skillGradebookLinks"
              :key="`${link.courseId}-${link.sessionId}`"
            >
              <a
                :href="gradebookUrl(link)"
                class="text-primary hover:underline"
              >
                {{ link.courseTitle }}
                <span v-if="link.sessionTitle">({{ link.sessionTitle }})</span>
              </a>
            </li>
          </ul>
          <p
            v-else
            class="text-gray-500"
          >
            {{ t("No course currently allows you to obtain this skill.") }}
          </p>
        </div>

        <div
          v-if="canUseProfiles"
          class="flex flex-wrap gap-2 border-t border-gray-20 pt-4"
        >
          <a
            :href="`/main/skills/skill_edit.php?id=${skillDetail.id}&origin=skill-wheel&return_skill=${skillDetail.id}`"
          >
            <BaseButton
              :label="t('Edit')"
              icon="edit"
              type="secondary"
            />
          </a>
          <a
            :href="`/main/skills/skill_create.php?parent=${skillDetail.id}&origin=skill-wheel&return_skill=${skillDetail.id}`"
          >
            <BaseButton
              :label="t('Create child skill')"
              icon="add"
              type="success"
            />
          </a>
          <BaseButton
            :label="t('Add skill to search profile')"
            icon="search"
            type="secondary"
            @click="addSkillToSearch(skillDetail.id, skillDetail.name)"
          />
        </div>
      </div>
    </aside>

    <SkillWheelGraph
      v-show="!showProfileMatches"
      ref="wheelEl"
      @ready="onWheelReady"
      @skill-detail="onSkillDetail"
    />

    <div
      v-if="canUseProfiles"
      v-show="showProfileMatches"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
    >
      <div class="mb-4 flex justify-end">
        <BaseButton
          :label="t('View skills wheel')"
          icon="wheel"
          type="secondary"
          @click="onClickViewSkillWheel"
        />
      </div>
      <SkillProfileMatches ref="profileMatchesEl" />
    </div>
  </div>

  <Dialog
    v-model:visible="showSearchTools"
    :header="t('What skills are you looking for?')"
    modal
    class="w-full max-w-2xl"
    data-testid="skill-wheel-search-dialog"
  >
    <div class="flex flex-col gap-5">
      <BaseAutocomplete
        id="skill_id"
        v-model="foundSkills"
        :label="t('Enter the skill name to search')"
        :search="findSkills"
        is-multiple
      >
        <template #chip="{ value }">
          {{ value.name }}

          <span
            class="p-autocomplete-token-icon"
            @click="locateSkill(value.id)"
          >
            <BaseIcon
              icon="crosshairs"
              size="small"
            />
          </span>
        </template>
      </BaseAutocomplete>

      <div class="flex flex-wrap gap-2">
        <BaseButton
          :label="t('View skills wheel')"
          icon="wheel"
          type="secondary"
          @click="onClickViewSkillWheel"
        />
        <BaseButton
          v-if="canUseProfiles"
          :disabled="!foundSkills.length"
          :label="t('Search profile matches')"
          icon="search"
          type="primary"
          @click="onClickSearchProfileMatches"
        />
        <BaseButton
          v-if="canUseProfiles"
          :disabled="!foundSkills.length"
          :label="t('Save this search')"
          icon="search"
          type="secondary"
          @click="showSkilProfileForm = true"
        />
      </div>
    </div>
  </Dialog>

  <Dialog
    v-if="canUseProfiles"
    v-model:visible="showProfiles"
    :header="t('Skill profiles')"
    modal
    class="w-full max-w-2xl"
    data-testid="skill-wheel-profiles-dialog"
  >
    <div class="flex flex-col gap-4">
      <SkillWheelProfileList
        ref="profileListEL"
        @search-profile="onSearchProfile"
      />

      <div class="flex justify-end border-t border-gray-20 pt-4">
        <BaseButton
          :label="t('What skills are you looking for?')"
          icon="search"
          type="primary"
          @click="openSearchFromProfiles"
        />
      </div>
    </div>
  </Dialog>

  <Dialog
    v-model:visible="showHelp"
    header="Help"
    modal
    class="w-full max-w-lg"
  >
    <div class="flex flex-col gap-3 text-sm text-gray-70">
      <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
        <div class="font-semibold text-gray-90">Click</div>
        <div>Explore a branch or open the selected skill.</div>
      </div>
      <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
        <div class="font-semibold text-gray-90">Center</div>
        <div>Use the center of the wheel to return to the parent branch.</div>
      </div>
      <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
        <div class="font-semibold text-gray-90">Ctrl + K / Cmd + K /</div>
        <div>Open the skill search from anywhere in the explorer.</div>
      </div>
      <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
        <div class="font-semibold text-gray-90">3D</div>
        <div>Move the pointer across the wheel to inspect depth and hierarchy.</div>
      </div>

      <div class="border-t border-gray-20 pt-4">
        <div class="mb-3 font-semibold text-gray-90">
          {{ t("Legend") }}
        </div>
        <ul class="flex flex-col gap-2">
          <li class="flex items-center gap-2">
            <BaseIcon
              icon="square"
              style="color: #3182bd"
            />
            {{ t("Basic skills") }}
          </li>
          <li class="flex items-center gap-2">
            <BaseIcon
              icon="square"
              style="color: #f89406"
            />
            {{ t("Skills you can learn") }}
          </li>
          <li class="flex items-center gap-2">
            <BaseIcon
              icon="square"
              style="color: #b94a48"
            />
            {{ t("Skills searched for") }}
          </li>
        </ul>
      </div>
    </div>
  </Dialog>

  <SkillProfileDialog
    v-if="canUseProfiles"
    v-model:skills="foundSkills"
    v-model:visible="showSkilProfileForm"
    @saved="onProfileSaved"
  />
</template>
