<template>
  <BaseCard
    plain
    class="overflow-hidden bg-white"
  >
    <template #header>
      <div class="border-b border-gray-25 bg-gray-15 px-4 py-3">
        <h2 class="text-xl font-semibold text-gray-90">{{ t("Skills") }}</h2>
      </div>
    </template>

    <div class="px-4 py-4">
      <div
        v-if="skills.length > 0"
        class="grid grid-cols-2 gap-3"
      >
        <a
          v-for="skill in skills"
          :key="skill.id"
          :href="issuedAllUrl(skill.id)"
          :title="skill.name"
          class="group flex flex-col items-center rounded-2xl border border-gray-25 bg-white p-3 text-center transition hover:bg-support-2"
        >
          <span
            class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border border-gray-25 bg-gray-15 shadow-sm"
          >
            <img
              :src="skill.image || defaultBadge"
              :alt="skill.name"
              class="h-12 w-12 object-contain"
              loading="lazy"
              @error="onBadgeError"
            />
          </span>

          <span class="mt-2 line-clamp-2 text-body-2 font-semibold leading-snug text-gray-90">
            {{ skill.name }}
          </span>

          <span class="mt-1 text-tiny text-gray-50 transition group-hover:text-gray-90">
            {{ t("View badge") }}
          </span>
        </a>
      </div>

      <div
        v-else
        class="rounded-2xl border border-dashed border-gray-25 bg-gray-15 px-4 py-7 text-center"
      >
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-gray-25 bg-white shadow-sm"
        >
          <i
            class="mdi mdi-star-circle-outline text-2xl text-gray-50"
            aria-hidden="true"
          ></i>
        </div>

        <p class="mt-3 text-body-2 text-gray-50">
          {{ t("Without achieved skills") }}
        </p>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-3">
        <a
          v-if="canSeeSkillWheel"
          :href="skillsWheelUrl"
          :title="t('Skills wheel')"
          class="group flex min-h-[84px] items-center gap-3 rounded-2xl border border-gray-25 bg-gray-15 px-4 py-3 transition hover:bg-support-2"
        >
          <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-primary shadow-sm">
            <i
              class="mdi mdi-chart-donut text-xl"
              aria-hidden="true"
            ></i>
          </span>

          <span class="min-w-0 text-left">
            <span class="block text-body-2 font-semibold leading-snug text-gray-90">
              {{ t("Skills wheel") }}
            </span>
            <span class="mt-1 block text-tiny text-gray-50">
              {{ t("Visual overview of your skills") }}
            </span>
          </span>
        </a>

        <a
          :href="skillsRankingUrl"
          :title="t('Your skill ranking')"
          class="group flex min-h-[84px] items-center gap-3 rounded-2xl border border-gray-25 bg-gray-15 px-4 py-3 transition hover:bg-support-2"
        >
          <span
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-secondary shadow-sm"
          >
            <i
              class="mdi mdi-podium-gold text-xl"
              aria-hidden="true"
            ></i>
          </span>

          <span class="min-w-0 text-left">
            <span class="block text-body-2 font-semibold leading-snug text-gray-90">
              {{ t("Your skill ranking") }}
            </span>
            <span class="mt-1 block text-tiny text-gray-50">
              {{ t("Compare your progress") }}
            </span>
          </span>
        </a>
      </div>
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
const skillsRankingUrl = computed(() => "/skill/ranking")

const skillsWheelUrl = computed(() => {
  const origin = `${window.location.pathname}${window.location.search}`
  const params = new URLSearchParams({ origin })
  return `/skill/wheel?${params.toString()}`
})

watch(
  () => user?.value?.id,
  (userId) => {
    if (userId) {
      fetchSkills(userId)
    } else {
      skills.value = []
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
  if (!url || typeof url !== "string") {
    return ""
  }

  if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("/")) {
    return url
  }

  return `/${url}`
}

function onBadgeError(event) {
  if (event?.target?.src && !event.target.src.endsWith(defaultBadge)) {
    event.target.src = defaultBadge
  }
}

async function fetchSkills(userId) {
  try {
    const response = await axios.get(`${ENTRYPOINT}users/${userId}/skills`)
    const data = Array.isArray(response.data) ? response.data : []

    skills.value = data.map((skill) => {
      const id = skill.id ?? skill.skillId ?? skill.skill_id
      const name = skill.name ?? skill.title ?? ""
      const image = normalizeImageUrl(skill.image ?? skill.badge ?? skill.illustrationUrl ?? "")

      return { id, name, image }
    })
  } catch (error) {
    console.error("Error fetching skills:", error)
    skills.value = []
  }
}
</script>
