<template>
  <BaseCard
    :class="id"
    class="admin-index__block-container"
  >
    <template #title>
      <BaseIcon :icon="icon" />
      {{ title }}
    </template>

    <template
      #subtitle
      v-if="description"
    >
      {{ description }}
    </template>

    <template #footer>
      <AdminBlockExtraContent
        :id="id"
        v-model="modelExtraContent"
        :editable="editable"
      />
    </template>

    <form
      v-if="props.searchUrl"
      :action="props.searchUrl"
      method="get"
    >
      <BaseInputGroup
        :id="inputId"
        :button-id="buttonId"
        :button-label="t('Search')"
        :input-placeholder="t('Keyword')"
        button-icon="search"
        input-name="keyword"
      />
    </form>

    <div class="p-menu p-component p-ripple-disabled">
      <ul
        class="p-menu-list p-reset"
        role="menu"
      >
        <li
          v-for="(item, index) in visibleItems"
          :key="index"
          :aria-label="t(item.label)"
          :class="item.class"
          class="p-menuitem"
          role="menuitem"
        >
          <div class="p-menuitem-content">
            <BaseAppLink
              :to="item.route"
              :url="item.url"
              class="p-menuitem-link"
            >
              <span
                class="p-menuitem-text"
                v-text="item.label"
              />
            </BaseAppLink>
          </div>
        </li>
      </ul>
    </div>
  </BaseCard>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseInputGroup from "../basecomponents/BaseInputGroup.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import AdminBlockExtraContent from "./AdminBlockExtraContent.vue"

const { t } = useI18n()

const modelExtraContent = defineModel("extraContent", {
  type: Object,
  default: null,
})

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  editable: {
    type: Boolean,
    required: false,
    default: false,
  },
  icon: { type: String, required: false, default: () => "admin-settings" },
  title: { type: String, require: true, default: () => "" },
  description: { type: String, required: false, default: () => null },
  searchUrl: { type: String, required: false, default: () => null },
  items: { type: Array, required: true, default: () => [] },
})

// computed IDs for search input and button derived from the title
const inputId = computed(() => {
  const raw = (props.title || "").toString().trim()
  const normalized = raw.replace(/\s+/g, "_").toLowerCase()
  return (normalized || "search") + "_search"
})

const buttonId = computed(() => `${inputId.value}_button`)

const visibleItems = computed(() =>
  props.items
    .map((item) => {
      if (!Object.keys(item).includes("visible")) {
        item.visible = true
      }

      return item
    })
    .filter((item) => item.visible),
)
</script>
