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
      class="max-w-sm w-full"
      show-icon
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
import { computed, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useVuelidate } from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import CalendarInvitations from "./CalendarInvitations.vue"
import CalendarRemindersEditor from "./CalendarRemindersEditor.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const { t } = useI18n()
const route = useRoute()

const props = defineProps({
  values: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => {},
  },
  initialValues: {
    type: Object,
    default: () => {},
  },
  isGlobal: Boolean,
})

const item = computed(() => props.initialValues || props.values)

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

const dateRange = ref()

if (item.value?.startDate || item.value?.endDate) {
  dateRange.value = [item.value?.startDate, item.value?.endDate]
}

watch(dateRange, (newValue) => {
  item.value.startDate = newValue[0]
  item.value.endDate = newValue[1]
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

if (!item.value.color) {
  const type = getContextTypeFromRoute()
  item.value.color = getDefaultColorByType(type)
}
</script>
