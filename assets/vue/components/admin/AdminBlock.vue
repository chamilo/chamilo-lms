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
          class="p-menu-item"
          role="menuitem"
        >
          <div class="p-menu-item-content">
            <BaseAppLink
              :to="item.route"
              :url="item.url"
              class="p-menu-item-link"
            >
              <span
                class="p-menu-item-label"
                v-text="item.label"
              />
            </BaseAppLink>
          </div>
        </li>
      </ul>
    </div>
    <div
      v-if="bgImageUrl"
      aria-hidden="true"
      class="admin-block__bg-image"
      :style="{
        backgroundImage: `url('${bgImageUrl}')`,
        backgroundPositionY: `-${props.bgIndex * BG_SPRITE_FRAME_HEIGHT}px`,
      }"
    />
  </BaseCard>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseInputGroup from "../basecomponents/BaseInputGroup.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import AdminBlockExtraContent from "./AdminBlockExtraContent.vue"
import { useVisualTheme } from "../../composables/theme"
const { getThemeAssetUrl } = useVisualTheme()

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
  bgIndex: { type: Number, required: false, default: null },
})

// All admin blocks share one sprite sheet (13 frames stacked vertically, 50px
// each once scaled by .admin-block__bg-image's background-size) to cut 13
// per-block image requests down to 1; bgIndex selects the frame.
const BG_SPRITE_PATH = "images/bg-block-admin-sprite.png"
const BG_SPRITE_FRAME_HEIGHT = 50

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

const bgImageUrl = computed(() => (props.bgIndex !== null ? getThemeAssetUrl(BG_SPRITE_PATH) : null))
</script>
