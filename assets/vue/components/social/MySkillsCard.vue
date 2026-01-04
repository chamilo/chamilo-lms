<template>
  <BaseCard
    class="my-skills-card bg-white"
    plain
  >
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t("Skills") }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4" />
    <div v-if="skills.length > 0">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <a
          v-for="skill in skills"
          :key="skill.id"
          :href="issuedAllUrl(skill.id)"
          class="group flex flex-col items-center text-center p-3 rounded-2xl border border-gray-100 bg-white hover:bg-gray-15 hover:border-gray-200 transition"
          :title="skill.name"
        >
          <div
            class="w-16 h-16 sm:w-18 sm:h-18 flex items-center justify-center rounded-2xl bg-gray-15 border border-gray-100 shadow-sm overflow-hidden"
          >
            <img
              :src="skill.image || defaultBadge"
              :alt="skill.name"
              class="w-12 h-12 sm:w-14 sm:h-14 object-contain"
              loading="lazy"
              @error="onBadgeError"
            />
          </div>

          <div class="mt-2 text-sm font-semibold text-gray-900 leading-snug line-clamp-2">
            {{ skill.name }}
          </div>

          <div class="mt-1 text-xs text-gray-600 group-hover:text-gray-700">
            {{ t("View badge") }}
          </div>
        </a>
      </div>
    </div>

    <div
      v-else
      class="py-2"
    >
      <p class="text-sm text-gray-600">{{ t("Without achieved skills") }}</p>
    </div>
    <div class="mt-4 flex flex-col sm:flex-row gap-2">
      <a
        v-if="canSeeSkillWheel"
        :href="skillsWheelUrl"
        class="inline-flex items-center justify-center w-full px-3 py-2 rounded-xl text-sm font-semibold border border-gray-200 bg-white hover:bg-gray-15 transition"
      >
        {{ t("Skills wheel") }}
      </a>

      <a
        :href="skillsRankingUrl"
        class="inline-flex items-center justify-center w-full px-3 py-2 rounded-xl text-sm font-semibold border border-gray-200 bg-white hover:bg-gray-15 transition"
      >
        {{ t("Your skill ranking") }}
      </a>
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useI18n } from "vue-i18n"
import { inject, ref, watch, computed } from "vue"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const { t } = useI18n()
const skills = ref([])
const user = inject("social-user")

const securityStore = useSecurityStore()
const { isAdmin, isHRM, isSessionAdmin } = storeToRefs(securityStore)

const canSeeSkillWheel = computed(() => isAdmin.value || isHRM.value || isSessionAdmin.value)
const defaultBadge = "/img/icons/32/badges-default.png"
const skillsRankingUrl = computed(() => {
  const origin = `${window.location.pathname}${window.location.search}`
  const params = new URLSearchParams({ origin, from: "social" })
  return `/main/social/skills_ranking.php?${params.toString()}`
})

// Wheel URL with origin so the wheel can return back
const skillsWheelUrl = computed(() => {
  // Use current path+query as origin (no hash)
  const origin = `${window.location.pathname}${window.location.search}`
  const params = new URLSearchParams({ origin })
  return `/skill/wheel?${params.toString()}`
})

watch(
  () => user?.value?.id,
  (userId) => {
    if (userId) {
      fetchSkills(userId)
    }
  },
  { immediate: true },
)

function issuedAllUrl(skillId) {
  const userId = user?.value?.id
  const origin = `${window.location.pathname}${window.location.search}`

  return `/main/skills/issued_all.php?skill=${encodeURIComponent(skillId)}&user=${encodeURIComponent(
    userId,
  )}&origin=${encodeURIComponent(origin)}`
}

function normalizeImageUrl(url) {
  if (!url || typeof url !== "string") return ""
  if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("/")) return url
  return `/${url}`
}

function onBadgeError(e) {
  // Prevent infinite loop if default badge is missing
  if (e?.target?.src && !e.target.src.endsWith(defaultBadge)) {
    e.target.src = defaultBadge
  }
}

async function fetchSkills(userId) {
  try {
    const response = await axios.get(`${ENTRYPOINT}users/${userId}/skills`)
    const data = Array.isArray(response.data) ? response.data : []

    skills.value = data.map((s) => {
      const id = s.id ?? s.skillId ?? s.skill_id
      const name = s.name ?? s.title ?? ""
      const image = normalizeImageUrl(s.image ?? s.badge ?? s.illustrationUrl ?? "")

      return { id, name, image }
    })
  } catch (error) {
    console.error("Error fetching skills:", error)
    skills.value = []
  }
}
</script>
