<template>
  <form>
    <BaseInputText
      v-model="item.title"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$invalid"
      :label="t('Title')"
    />

    <BaseCalendar
      id="calendar-id"
      v-model="dateRange"
      :initial-value="[item.startDate, item.endDate]"
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
        type="color"
        v-model="item.color"
        class="w-14 h-10 cursor-pointer border rounded"
      />
    </div>

    <CalendarInvitations v-model="item" />

    <CalendarRemindersEditor
      v-if="!isGlobal"
      v-model="item"
    />

    <slot />
  </form>
</template>

<script setup>
import { computed, ref, watch, onMounted } from "vue"
import { useRoute } from "vue-router"
import { useVuelidate } from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import CalendarInvitations from "./CalendarInvitations.vue"
import CalendarRemindersEditor from "./CalendarRemindersEditor.vue"

const { t } = useI18n()
const route = useRoute()

const props = defineProps({
  values: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  initialValues: {
    type: Object,
    default: () => ({}),
  },
  isGlobal: Boolean,
})

function isNonEmptyObject(v) {
  return v && typeof v === "object" && !Array.isArray(v) && Object.keys(v).length > 0
}

const item = computed(() => {
  if (isNonEmptyObject(props.initialValues)) {
    return props.initialValues
  }
  return props.values
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

const dateRange = ref([null, null])

watch(
  () => [item.value?.startDate, item.value?.endDate],
  ([start, end]) => {
    dateRange.value = [start ?? null, end ?? null]
  },
  { immediate: true },
)

watch(dateRange, (newValue) => {
  if (!Array.isArray(newValue)) {
    return
  }

  item.value.startDate = newValue[0] ?? null
  item.value.endDate = newValue[1] ?? null
})

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

onMounted(() => {
  ensureValidColor()
})

watch(
  () => item.value,
  () => {
    ensureValidColor()
  },
  { immediate: true },
)
</script>
