<template>
  <form>
    <BaseInputText
      id="event-title"
      v-model="item.title"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$invalid"
      :label="t('Title')"
    />

    <BaseCalendar
      id="calendar-id"
      v-model="dateRange"
      :is-invalid="v$.item.startDate.$invalid || v$.item.endDate.$invalid"
      :label="t('Date')"
      show-time
      type="range"
    />

    <BaseTinyEditor
      v-model="item.content"
      :required="false"
      editor-id="calendar-event-content"
    />

    <div class="m-4 flex flex-col gap-2">
      <label
        for="color-picker"
        class="font-semibold text-sm"
      >
        {{ t("Color") }}
      </label>
      <input
        id="color-picker"
        v-model="item.color"
        class="w-14 h-10 cursor-pointer border rounded"
        type="color"
      />
    </div>

    <BaseSelect
      v-if="showRoomField && roomOptions.length > 0"
      id="calendar-room"
      v-model="item.room"
      :allow-clear="true"
      :label="t('Room')"
      :options="roomOptions"
      :option-label="'name'"
      :option-value="'id'"
      name="room"
    />

    <BaseSelect
      v-if="showCareerPromotionFields && normalizedCareerOptions.length > 0"
      id="calendar-career"
      v-model="item.career"
      :allow-clear="true"
      :label="t('Career')"
      :options="normalizedCareerOptions"
      :option-label="'name'"
      :option-value="'id'"
      name="career"
    />

    <BaseSelect
      v-if="showCareerPromotionFields && filteredPromotionOptions.length > 0"
      id="calendar-promotion"
      v-model="item.promotion"
      :allow-clear="true"
      :label="t('Promotion')"
      :options="filteredPromotionOptions"
      :option-label="'name'"
      :option-value="'id'"
      name="promotion"
    />

    <CalendarInvitations v-model="item" />

    <CalendarRemindersEditor
      v-if="!isGlobal"
      v-model="item"
    />

    <slot />
  </form>
</template>

<script setup>
import { computed, ref, watch, onMounted, watchEffect } from "vue"
import { useRoute } from "vue-router"
import { useVuelidate } from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import CalendarInvitations from "./CalendarInvitations.vue"
import CalendarRemindersEditor from "./CalendarRemindersEditor.vue"
import roomService from "../../services/roomService"
import baseService from "../../services/baseService"

const { t } = useI18n()
const route = useRoute()

const roomOptions = ref([])

const props = defineProps({
  values: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  isGlobal: Boolean,
  allowCareerPromotionFields: {
    type: Boolean,
    default: false,
  },
  careerOptions: {
    type: Array,
    default: () => [],
  },
  promotionOptions: {
    type: Array,
    default: () => [],
  },
})

const item = computed(() => props.values)

const showRoomField = computed(() => {
  const contextType = getContextTypeFromRoute()

  return contextType !== "personal"
})

const showCareerPromotionFields = computed(() => {
  return props.isGlobal && props.allowCareerPromotionFields
})

const normalizedCareerOptions = computed(() => {
  return props.careerOptions.map((career) => ({
    id: normalizeApiReference(career),
    name: career?.title ?? career?.name ?? "",
  }))
})

const normalizedPromotionOptions = computed(() => {
  return props.promotionOptions.map((promotion) => ({
    id: normalizeApiReference(promotion),
    name: promotion?.title ?? promotion?.name ?? "",
    career: normalizeRelatedCareerReference(promotion),
  }))
})

const filteredPromotionOptions = computed(() => {
  const selectedCareer = normalizeSelectedReference(item.value?.career)

  if (!selectedCareer) {
    return normalizedPromotionOptions.value
  }

  return normalizedPromotionOptions.value.filter((promotion) => {
    return !promotion.career || promotion.career === selectedCareer
  })
})

const rules = computed(() => ({
  item: {
    title: {
      required,
    },
    startDate: {
      required,
    },
    endDate: {
      required,
    },
    color: {
      required,
    },
  },
}))

const v$ = useVuelidate(rules, { item })

defineExpose({
  v$,
})

const dateRange = ref([item.value?.startDate, item.value?.endDate])

watchEffect(() => {
  item.value.startDate = dateRange.value[0] ?? null
  item.value.endDate = dateRange.value[1] ?? null
})

watch(
  () => item.value?.room,
  (room) => {
    if (room && typeof room === "object" && room["@id"]) {
      item.value.room = room["@id"]
    }
  },
  { immediate: true },
)

watch(
  () => item.value?.career,
  (career) => {
    if (career && typeof career === "object" && career["@id"]) {
      item.value.career = career["@id"]
    }
  },
  { immediate: true },
)

watch(
  () => item.value?.promotion,
  (promotion) => {
    if (promotion && typeof promotion === "object" && promotion["@id"]) {
      item.value.promotion = promotion["@id"]
    }
  },
  { immediate: true },
)

watch(
  () => item.value?.career,
  () => {
    if (!showCareerPromotionFields.value) {
      return
    }

    const selectedCareer = normalizeSelectedReference(item.value?.career)
    const selectedPromotionCareer = getPromotionCareerReference(item.value?.promotion)

    if (selectedCareer && selectedPromotionCareer && selectedPromotionCareer !== selectedCareer) {
      item.value.promotion = null
    }
  },
)

watch(
  () => showCareerPromotionFields.value,
  (visible) => {
    if (!visible) {
      item.value.career = null
      item.value.promotion = null
    }
  },
  { immediate: true },
)

function getContextTypeFromRoute() {
  if (route.query.type === "global") return "global"
  if (route.query.sid && route.query.sid !== "0") return "session"
  if (route.query.cid && (!route.query.sid || route.query.sid === "0")) return "course"
  return "personal"
}

function getDefaultColorByType(type) {
  const defaultColors = {
    global: "#FF0000",
    course: "#458B00",
    session: "#00496D",
    personal: "#4682B4",
  }

  return defaultColors[type] || defaultColors.personal
}

function normalizeApiReference(value) {
  if (!value) {
    return null
  }

  if (typeof value === "number") {
    return value
  }

  if (typeof value === "string") {
    return value
  }

  if (typeof value === "object" && value["@id"]) {
    return value["@id"]
  }

  if (typeof value === "object" && value.id) {
    return value.id
  }

  return null
}

function normalizeSelectedReference(value) {
  return normalizeApiReference(value)
}

function normalizeRelatedCareerReference(promotion) {
  if (!promotion) {
    return null
  }

  if (typeof promotion.career === "number") {
    return promotion.career
  }

  if (typeof promotion.career === "string") {
    return promotion.career
  }

  if (promotion.career && typeof promotion.career === "object" && promotion.career["@id"]) {
    return promotion.career["@id"]
  }

  if (promotion.career && typeof promotion.career === "object" && promotion.career.id) {
    return promotion.career.id
  }

  return null
}

function getPromotionCareerReference(value) {
  const selectedPromotionId = normalizeSelectedReference(value)

  if (!selectedPromotionId) {
    return null
  }

  const promotion = normalizedPromotionOptions.value.find((option) => option.id === selectedPromotionId)

  return promotion?.career ?? null
}

const HEX6 = /^#([0-9a-f]{6})$/i
const HEX3 = /^#([0-9a-f]{3})$/i

function toHex(c) {
  if (!c) return null

  const s = String(c).trim()

  if (HEX6.test(s)) return s.toUpperCase()

  const m3 = s.match(HEX3)
  if (m3) {
    const [r, g, b] = m3[1].toUpperCase().split("")
    return `#${r}${r}${g}${g}${b}${b}`
  }

  const rgb = s.match(/rgba?\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i)
  if (rgb) {
    const r = Math.min(255, +rgb[1])
    const g = Math.min(255, +rgb[2])
    const b = Math.min(255, +rgb[3])

    return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b
      .toString(16)
      .padStart(2, "0")}`.toUpperCase()
  }

  const names = {
    YELLOW: "#FFFF00",
    BLUE: "#0000FF",
    RED: "#FF0000",
    GREEN: "#008000",
    STEELBLUE: "#4682B4",
    "STEEL BLUE": "#4682B4",
  }

  return names[s.toUpperCase()] || null
}

function ensureValidColor() {
  const normalized = toHex(item.value?.color)
  if (normalized) {
    item.value.color = normalized
    return
  }

  const type = getContextTypeFromRoute()
  item.value.color = getDefaultColorByType(type)
}

onMounted(async () => {
  ensureValidColor()

  if (showRoomField.value) {
    try {
      const hasRooms = await roomService.exists()
      if (hasRooms) {
        const { items } = await baseService.getCollection("/api/rooms")
        roomOptions.value = items.map((r) => {
          const branch = r.branch
          const branchTitle = branch && typeof branch === "object" ? branch.title : null
          const label = branchTitle ? `${branchTitle} - ${r.title}` : r.title
          return { name: label, id: r["@id"] }
        })
      }
    } catch (e) {
      console.error("Failed to load rooms", e)
    }
  }
})

watch(
  () => item.value,
  () => {
    ensureValidColor()
  },
  { immediate: true },
)
</script>
