<template>
  <form class="grid gap-3 md:grid-cols-4 items-end" @submit.prevent="apply">
    <div v-if="allowTitle">
      <label class="block text-sm font-medium mb-1">{{ $t('Title') }}</label>
      <InputText v-model="model.title" :placeholder="$t('Search by title')" />
    </div>

    <template v-for="f in fields" :key="f.variable">
      <!-- SELECT simple -->
      <div v-if="isSelect(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <Dropdown
          v-model="model.extra[f.variable]"
          :options="toDropdown(f.options)"
          optionLabel="label"
          optionValue="value"
          showClear
          :placeholder="$t('Select')"
          class="w-full"
        />
      </div>

      <!-- MULTISELECT -->
      <div v-else-if="isMulti(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <MultiSelect
          v-model="model.extra[f.variable]"
          :options="toDropdown(f.options)"
          optionLabel="label"
          optionValue="value"
          display="chip"
          :placeholder="$t('Select')"
          class="w-full"
        />
      </div>

      <!-- RADIO -->
      <div v-else-if="isRadio(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <div class="flex flex-wrap gap-3">
          <div v-for="opt in toDropdown(f.options)" :key="opt.value" class="flex items-center gap-2">
            <RadioButton :inputId="f.variable + opt.value" :value="opt.value" v-model="model.extra[f.variable]" />
            <label :for="f.variable + opt.value">{{ opt.label }}</label>
          </div>
        </div>
      </div>

      <!-- CHECKBOX -->
      <div v-else-if="isCheckbox(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <div v-if="hasOptions(f)" class="flex flex-col gap-1">
          <div v-for="opt in toDropdown(f.options)" :key="opt.value" class="flex items-center gap-2">
            <Checkbox :inputId="f.variable + opt.value"
                      :value="opt.value"
                      v-model="model.extra[f.variable]" binary="false" />
            <label :for="f.variable + opt.value">{{ opt.label }}</label>
          </div>
        </div>
        <div v-else class="flex items-center gap-2">
          <Checkbox :inputId="f.variable" v-model="model.extra[f.variable]" binary />
          <label :for="f.variable">{{ $t('Yes') }}</label>
        </div>
      </div>

      <!-- DATE -->
      <div v-else-if="isDate(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <Calendar v-model="model.extra[f.variable]" dateFormat="yy-mm-dd" showIcon class="w-full" />
      </div>

      <!-- DATETIME -->
      <div v-else-if="isDateTime(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <Calendar v-model="model.extra[f.variable]" showTime hourFormat="24" dateFormat="yy-mm-dd" showIcon class="w-full" />
      </div>

      <!-- SELECT + TEXT -->
      <div v-else-if="isSelectWithText(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <div class="grid grid-cols-2 gap-2">
          <Dropdown
            v-model="model.extra[f.variable]"
            :options="toDropdown(level1(f))"
            optionLabel="label" optionValue="id" showClear class="w-full"
          />
          <InputText v-model="model.extra[`${f.variable}_second`]" :placeholder="$t('Enter a value here')" />
        </div>
      </div>

      <!-- DOUBLE SELECT -->
      <div v-else-if="isDouble(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <div class="grid grid-cols-2 gap-2">
          <Dropdown
            v-model="model.extra[f.variable]"
            :options="toDropdown(level1(f))"
            optionLabel="label" optionValue="id" showClear class="w-full"
            @change="onDoubleChange(f)"
          />
          <Dropdown
            v-model="model.extra[`${f.variable}_second`]"
            :options="toDropdown(level2(f, model.extra[f.variable]))"
            optionLabel="label" optionValue="id" showClear class="w-full"
            :disabled="!model.extra[f.variable]"
          />
        </div>
      </div>

      <!-- TRIPLE SELECT -->
      <div v-else-if="isTriple(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <div class="grid grid-cols-3 gap-2">
          <Dropdown
            v-model="model.extra[f.variable]"
            :options="toDropdown(level1(f))"
            optionLabel="label" optionValue="id" showClear class="w-full"
            @change="onTripleL1Change(f)"
          />
          <Dropdown
            v-model="model.extra[`${f.variable}_second`]"
            :options="toDropdown(level2(f, model.extra[f.variable]))"
            optionLabel="label" optionValue="id" showClear class="w-full"
            :disabled="!model.extra[f.variable]"
            @change="onTripleL2Change(f)"
          />
          <Dropdown
            v-model="model.extra[`${f.variable}_third`]"
            :options="toDropdown(level3(f, model.extra[`${f.variable}_second`]))"
            optionLabel="label" optionValue="id" showClear class="w-full"
            :disabled="!model.extra[`${f.variable}_second`]"
          />
        </div>
      </div>

      <!-- TAGS -->
      <div v-else-if="isTag(f)">
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <Chips v-model="model.extra[f.variable]" :placeholder="$t('Add tags')" />
      </div>

      <!-- TEXT / INTEGER / FLOAT / DURATION  -->
      <div v-else>
        <label class="block text-sm font-medium mb-1">{{ f.title }}</label>
        <InputText v-model="model.extra[f.variable]" />
      </div>
    </template>

    <div class="md:col-span-4 flex flex-wrap gap-2 justify-end">
      <Button type="button" class="p-button-outlined" :label="$t('Clear')" @click="clear" />
      <Button type="submit" :label="$t('Apply advanced filters')" icon="pi pi-filter" />
    </div>
  </form>
</template>

<script setup>
import { reactive, computed } from 'vue'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import RadioButton from 'primevue/radiobutton'
import Chips from 'primevue/chips'
import Button from 'primevue/button'

const TYPE = {
  TEXT: 1, TEXTAREA: 2, RADIO: 3, SELECT: 4, SELECT_MULTI: 5,
  DATE: 6, DATETIME: 7, DOUBLE: 8, DIVIDER: 9, TAG: 10,
  TIMEZONE: 11, SOCIAL: 12, CHECKBOX: 13, MOBILE: 14, INTEGER: 15,
  FILE_IMAGE: 16, FLOAT: 17, FILE: 18, VIDEO_URL: 19, LETTERS_ONLY: 20,
  ALPHANUM: 21, LETTERS_SPACE: 22, ALPHANUM_SPACE: 23, GEO: 24,
  GEO_COORD: 25, SELECT_WITH_TEXT: 26, TRIPLE: 27, DURATION: 28,
}

// Props
const props = defineProps({
  fields: { type: Array, default: () => [] },
  allowTitle: { type: Boolean, default: true },
})
const emit = defineEmits(['apply', 'clear'])

const model = reactive({
  title: '',
  extra: {},
})

const isSelect        = f => f.value_type === TYPE.SELECT
const isMulti         = f => f.value_type === TYPE.SELECT_MULTI
const isRadio         = f => f.value_type === TYPE.RADIO
const isCheckbox      = f => f.value_type === TYPE.CHECKBOX
const isDate          = f => f.value_type === TYPE.DATE
const isDateTime      = f => f.value_type === TYPE.DATETIME
const isDouble        = f => f.value_type === TYPE.DOUBLE
const isTriple        = f => f.value_type === TYPE.TRIPLE
const isSelectWithText= f => f.value_type === TYPE.SELECT_WITH_TEXT
const isTag           = f => f.value_type === TYPE.TAG
const hasOptions      = f => Array.isArray(f.options) && f.options.length > 0

const toDropdown = opts => (opts || []).map(o => ({
  id: o.id, label: o.label, value: o.value ?? o.id, parent: o.parent ?? 0,
}))

const level1 = f => (f.options || []).filter(o => Number(o.parent) === 0)
const level2 = (f, parentId) => (f.options || []).filter(o => String(o.parent) === String(parentId))
const level3 = (f, parentId) => (f.options || []).filter(o => String(o.parent) === String(parentId))

function onDoubleChange(f) {
  model.extra[`${f.variable}_second`] = ''
}
function onTripleL1Change(f) {
  model.extra[`${f.variable}_second`] = ''
  model.extra[`${f.variable}_third`] = ''
}
function onTripleL2Change(f) {
  model.extra[`${f.variable}_third`] = ''
}

function clear() {
  model.title = ''
  model.extra = {}
  emit('clear')
}

function apply() {
  const payload = {}
  if (model.title?.trim()) payload.title = model.title.trim()

  Object.entries(model.extra).forEach(([k, v]) => {
    if (v === null || v === undefined) return
    if (Array.isArray(v) && v.length === 0) return
    if (typeof v === 'string' && v.trim() === '') return
    payload[`extra_${k}`] = v
  })

  emit('apply', payload)
}
</script>
