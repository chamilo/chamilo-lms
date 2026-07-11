<template>
  <section
    class="lp-impress-runtime"
    tabindex="0"
  >
    <div
      v-if="runtime.canEdit"
      class="lp-impress-breadcrumb"
    >
      <a :href="runtime.listUrl">{{ t("Learning paths") }}</a>
      <span>/</span>
      <span>{{ runtime.title }}</span>
      <span>/</span>
      <span>{{ t("Preview") }}</span>
    </div>

    <button
      :aria-label="t('Previous')"
      :disabled="!canGoPrevious || isChangingItem"
      class="lp-impress-edge lp-impress-edge--previous"
      type="button"
      @click="navigate(-1)"
    />

    <div class="lp-impress-stage">
      <Transition
        :name="transitionName"
        mode="out-in"
      >
        <article
          v-if="activeItem"
          :key="activeItem.id"
          :class="[
            'lp-impress-step',
            {
              'lp-impress-step--section': activeItem.isSection,
              'lp-impress-step--content': !activeItem.isSection,
            },
          ]"
        >
          <div
            v-if="activeItem.isSection"
            class="lp-impress-title"
          >
            <h1>{{ activeItem.title }}</h1>
          </div>

          <template v-else>
            <h2>{{ activeItem.title }}</h2>

            <div
              v-if="isChangingItem || iframeLoading || !contentReady"
              class="lp-impress-loader"
            >
              {{ t("Loading") }}…
            </div>

            <iframe
              v-if="contentReady"
              ref="contentFrame"
              :key="`${activeItem.id}-${runtime.scorm?.itemViewId || runtime.currentItemAttempt}-${iframeReloadKey}`"
              :src="runtime.contentUrl"
              :title="activeItem.title"
              allowfullscreen
              class="lp-impress-frame"
              @load="handleIframeLoad"
            />
          </template>
        </article>
      </Transition>
    </div>

    <button
      :aria-label="t('Next')"
      :disabled="!canGoNext || isChangingItem"
      class="lp-impress-edge lp-impress-edge--next"
      type="button"
      @click="navigate(1)"
    />

    <div class="lp-impress-hint">
      {{
        isTouchDevice
          ? t("Tap on the left or right to navigate")
          : t("Use a spacebar or arrow keys to navigate")
      }}
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"

const props = defineProps({
  runtime: {
    type: Object,
    required: true,
  },
  iframeLoading: {
    type: Boolean,
    default: false,
  },
  iframeReloadKey: {
    type: Number,
    default: 0,
  },
  isChangingItem: {
    type: Boolean,
    default: false,
  },
  resumeAtCurrent: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(["active-change", "iframe-load", "open-item"])
const { t } = useI18n()

const activeItemId = ref(0)
const contentFrame = ref(null)
const direction = ref("next")
const initialized = ref(false)
const pendingContentId = ref(0)
const touchStartX = ref(null)
const isTouchDevice = ref(false)
let frameDocument = null

const presentationItems = computed(() =>
  (props.runtime?.items || []).filter((item) => item.isSection || item.available),
)
const activeIndex = computed(() =>
  presentationItems.value.findIndex((item) => Number(item.id) === Number(activeItemId.value)),
)
const activeItem = computed(() => presentationItems.value[activeIndex.value] || null)
const canGoPrevious = computed(() => activeIndex.value > 0)
const canGoNext = computed(() => activeIndex.value >= 0 && activeIndex.value < presentationItems.value.length - 1)
const transitionName = computed(() => (direction.value === "previous" ? "lp-impress-backward" : "lp-impress-forward"))
const contentReady = computed(
  () =>
    activeItem.value &&
    !activeItem.value.isSection &&
    Number(activeItem.value.id) === Number(props.runtime.currentItemId) &&
    Boolean(props.runtime.contentUrl),
)

watch(
  () => [props.runtime?.currentItemId, props.runtime?.items],
  () => initializeActiveItem(),
  { immediate: true, deep: true },
)

watch(
  activeItem,
  (item) => {
    emit("active-change", item || null)
    if (!item?.isSection) {
      return
    }

    pendingContentId.value = 0
    detachFrameKeyboardNavigation()
  },
  { immediate: true },
)

function initializeActiveItem() {
  if (!presentationItems.value.length) {
    activeItemId.value = 0
    return
  }

  const currentId = Number(props.runtime?.currentItemId || 0)
  const currentExists = presentationItems.value.some((item) => Number(item.id) === currentId)

  if (!initialized.value) {
    activeItemId.value = props.resumeAtCurrent && currentExists ? currentId : Number(presentationItems.value[0].id)
    initialized.value = true
    return
  }

  if (pendingContentId.value > 0 && currentId === pendingContentId.value && currentExists) {
    activeItemId.value = currentId
    pendingContentId.value = 0
  }
}

function navigate(offset) {
  if (props.isChangingItem) {
    return
  }

  const targetIndex = activeIndex.value + Number(offset)
  const target = presentationItems.value[targetIndex]
  if (!target) {
    return
  }

  direction.value = offset < 0 ? "previous" : "next"

  if (target.isSection) {
    activeItemId.value = Number(target.id)
    return
  }

  pendingContentId.value = Number(target.id)
  emit("open-item", Number(target.id))
}

function isInteractiveTarget(target) {
  if (!target || typeof target.closest !== "function") {
    return false
  }

  return Boolean(target.closest("input, textarea, select, button, a, [contenteditable='true']"))
}

function handleKeydown(event) {
  if (event.defaultPrevented || isInteractiveTarget(event.target)) {
    return
  }

  if (["ArrowRight", "ArrowDown", "PageDown", " "].includes(event.key)) {
    event.preventDefault()
    navigate(1)
    return
  }

  if (["ArrowLeft", "ArrowUp", "PageUp"].includes(event.key)) {
    event.preventDefault()
    navigate(-1)
    return
  }

  if (event.key === "Home") {
    event.preventDefault()
    navigateToBoundary(0)
    return
  }

  if (event.key === "End") {
    event.preventDefault()
    navigateToBoundary(presentationItems.value.length - 1)
  }
}

function navigateToBoundary(index) {
  const target = presentationItems.value[index]
  if (!target || Number(target.id) === Number(activeItemId.value)) {
    return
  }

  direction.value = index < activeIndex.value ? "previous" : "next"
  if (target.isSection) {
    activeItemId.value = Number(target.id)
    return
  }

  pendingContentId.value = Number(target.id)
  emit("open-item", Number(target.id))
}

function handleTouchStart(event) {
  touchStartX.value = Number(event.changedTouches?.[0]?.clientX ?? 0)
}

function handleTouchEnd(event) {
  const endX = Number(event.changedTouches?.[0]?.clientX ?? 0)
  const startX = touchStartX.value
  touchStartX.value = null

  if (startX === null || Math.abs(endX - startX) < 48) {
    return
  }

  navigate(endX < startX ? 1 : -1)
}

function handleIframeLoad(event) {
  emit("iframe-load", event)
  attachFrameKeyboardNavigation()
}

function attachFrameKeyboardNavigation() {
  detachFrameKeyboardNavigation()

  nextTick(() => {
    try {
      frameDocument = contentFrame.value?.contentDocument || null
      frameDocument?.addEventListener("keydown", handleKeydown)
    } catch (error) {
      frameDocument = null
    }
  })
}

function detachFrameKeyboardNavigation() {
  frameDocument?.removeEventListener("keydown", handleKeydown)
  frameDocument = null
}

onMounted(() => {
  isTouchDevice.value = "ontouchstart" in document.documentElement
  window.addEventListener("keydown", handleKeydown)
  window.addEventListener("touchstart", handleTouchStart, { passive: true })
  window.addEventListener("touchend", handleTouchEnd, { passive: true })
})

onBeforeUnmount(() => {
  detachFrameKeyboardNavigation()
  window.removeEventListener("keydown", handleKeydown)
  window.removeEventListener("touchstart", handleTouchStart)
  window.removeEventListener("touchend", handleTouchEnd)
})
</script>

<style scoped>
.lp-impress-runtime {
  position: absolute;
  inset: 0;
  overflow: hidden;
  background: radial-gradient(circle at center, #f0f0f0 0%, #bebebe 100%);
  font-family: "PT Sans", Arial, sans-serif;
  outline: none;
  perspective: 1000px;
}


.lp-impress-breadcrumb {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: rgb(255 255 255 / 92%);
  color: #1f2937;
  font-family: Arial, sans-serif;
  font-size: 13px;
  pointer-events: auto;
}

.lp-impress-breadcrumb a {
  color: #1f78b4;
  text-decoration: none;
}

.lp-impress-breadcrumb a:hover {
  text-decoration: underline;
}

.lp-impress-stage {
  position: absolute;
  inset: 0;
  display: grid;
  padding: 48px;
  place-items: center;
}

.lp-impress-step {
  position: relative;
  width: min(900px, calc(100vw - 120px));
  height: min(680px, calc(100vh - 120px));
  padding: 40px;
  overflow: hidden;
  border-radius: 8px;
  background: rgb(255 255 255 / 94%);
  box-shadow: 0 18px 60px rgb(0 0 0 / 18%);
  box-sizing: border-box;
  color: #222222;
  font-family: Georgia, "Times New Roman", serif;
  transform-style: preserve-3d;
}

.lp-impress-step--section {
  display: grid;
  background: rgb(255 255 255 / 88%);
  place-items: center;
  text-align: center;
}

.lp-impress-title h1 {
  max-width: 760px;
  margin: 0;
  font-size: clamp(42px, 6vw, 88px);
  line-height: 1.1;
}

.lp-impress-step--content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.lp-impress-step--content h2 {
  flex: 0 0 auto;
  margin: 0;
  overflow: hidden;
  font-size: clamp(28px, 3.5vw, 48px);
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lp-impress-frame {
  width: 100%;
  min-height: 0;
  flex: 1 1 auto;
  border: 0;
  background: #ffffff;
}

.lp-impress-loader {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 20;
  padding: 6px 10px;
  border: 1px solid #e6d95c;
  background: #fffbd2;
  color: #667085;
  font-family: Arial, sans-serif;
  font-size: 12px;
  transform: translate(-50%, -50%);
}

.lp-impress-edge {
  position: absolute;
  top: 0;
  bottom: 0;
  z-index: 15;
  width: 72px;
  border: 0;
  background: transparent;
  cursor: pointer;
}

.lp-impress-edge:disabled {
  cursor: default;
  pointer-events: none;
}

.lp-impress-edge--previous {
  left: 0;
}

.lp-impress-edge--next {
  right: 0;
}

.lp-impress-hint {
  position: absolute;
  right: 0;
  bottom: 32px;
  left: 0;
  z-index: 12;
  color: rgb(255 255 255 / 90%);
  font-size: 18px;
  text-align: center;
  text-shadow: 0 1px 4px rgb(0 0 0 / 55%);
}

.lp-impress-forward-enter-active,
.lp-impress-forward-leave-active,
.lp-impress-backward-enter-active,
.lp-impress-backward-leave-active {
  transition: opacity 420ms ease, transform 620ms ease;
}

.lp-impress-forward-enter-from,
.lp-impress-backward-leave-to {
  opacity: 0;
  transform: translate3d(60%, 0, -240px) scale(0.72);
}

.lp-impress-forward-leave-to,
.lp-impress-backward-enter-from {
  opacity: 0;
  transform: translate3d(-60%, 0, -240px) scale(0.72);
}

@media (max-width: 768px) {
  .lp-impress-stage {
    padding: 20px;
  }

  .lp-impress-step {
    width: calc(100vw - 40px);
    height: calc(100vh - 72px);
    padding: 24px;
  }

  .lp-impress-hint {
    bottom: 12px;
    font-size: 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .lp-impress-forward-enter-active,
  .lp-impress-forward-leave-active,
  .lp-impress-backward-enter-active,
  .lp-impress-backward-leave-active {
    transition-duration: 1ms;
  }
}
</style>
