<template>
  <a
    v-if="preview"
    :href="preview.url"
    class="link-preview-card"
    rel="noopener noreferrer"
    target="_blank"
  >
    <img
      v-if="preview.image"
      :alt="preview.title"
      :src="preview.image"
      class="link-preview-card__image"
      @error="onImageError"
    />
    <div class="link-preview-card__body">
      <div
        v-if="preview.title"
        class="link-preview-card__title"
      >
        {{ preview.title }}
      </div>
      <div
        v-if="preview.description"
        class="link-preview-card__description"
      >
        {{ preview.description }}
      </div>
      <div
        v-if="preview.domain"
        class="link-preview-card__domain"
      >
        {{ preview.domain }}
      </div>
    </div>
  </a>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import axios from "axios"

const props = defineProps({
  url: {
    type: String,
    required: true,
  },
})

const preview = ref(null)

async function fetchPreview() {
  if (!props.url) return

  try {
    const { data } = await axios.post("/social-network/opengraph", { url: props.url })
    if (data && !data.error) {
      preview.value = data
    }
  } catch {
    preview.value = null
  }
}

function onImageError(event) {
  event.target.style.display = "none"
}

onMounted(fetchPreview)

watch(() => props.url, fetchPreview)
</script>

<style scoped>
.link-preview-card {
  display: flex;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  max-width: 100%;
  transition: box-shadow 0.15s;
}

.link-preview-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.link-preview-card__image {
  width: 120px;
  min-height: 80px;
  object-fit: cover;
  flex-shrink: 0;
}

.link-preview-card__body {
  padding: 8px 12px;
  overflow: hidden;
  min-width: 0;
}

.link-preview-card__title {
  font-weight: 600;
  font-size: 0.9rem;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.link-preview-card__description {
  font-size: 0.8rem;
  color: #666;
  margin-top: 2px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.link-preview-card__domain {
  font-size: 0.75rem;
  color: #999;
  margin-top: 4px;
}
</style>
