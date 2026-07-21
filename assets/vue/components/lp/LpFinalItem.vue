<template>
  <div class="w-full min-w-0 p-4 md:p-6 lg:p-8">
    <div
      v-if="data.preview"
      class="mb-6 flex items-center justify-center gap-2 rounded-lg bg-blue-10 px-4 py-3 text-sm font-semibold text-blue-90"
    >
      <BaseIcon icon="eye-on" />
      {{ t("Preview") }}
    </div>

    <template
      v-for="(block, index) in blocks"
      :key="`${block.type}-${index}`"
    >
      <div
        v-if="block.type === 'html'"
        class="lp-final-item-content w-full"
        v-html="sanitizeHtml(block.content)"
      />

      <section
        v-else-if="block.type === 'certificate' && certificate.available"
        class="my-6 w-full rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-20 md:p-6"
      >
        <h3 class="text-center text-xl font-semibold text-gray-90">
          {{ t("Certificate") }}
        </h3>

        <div
          v-if="certificate.preview && certificate.templateHtml"
          class="lp-final-item-certificate-preview mt-4 w-full overflow-x-auto rounded-lg border border-gray-20 bg-white p-2 md:p-4"
          v-html="sanitizeHtml(certificate.templateHtml)"
        />

        <div
          v-else
          class="mt-4 flex flex-wrap items-center justify-center gap-2"
        >
          <BaseButton
            v-if="certificate.viewUrl"
            :label="t('View')"
            icon="eye-on"
            type="primary"
            @click="openExternal(certificate.viewUrl)"
          />
          <BaseButton
            v-if="certificate.downloadUrl"
            :label="t('Download')"
            icon="download"
            type="primary"
            @click="openExternal(certificate.downloadUrl)"
          />
        </div>
      </section>

      <section
        v-else-if="block.type === 'skills' && skills.length"
        class="my-6 w-full rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-20 md:p-6"
      >
        <h3 class="text-center text-xl font-semibold text-gray-90">
          {{ t("Skills") }}
        </h3>

        <div class="mt-4 divide-y divide-gray-20">
          <article
            v-for="skill in skills"
            :key="skill.id"
            class="grid grid-cols-1 gap-4 py-6 sm:grid-cols-12 sm:items-start"
          >
            <div class="flex justify-center sm:col-span-3 sm:justify-start">
              <img
                :alt="skill.title"
                :src="skill.iconUrl"
                class="h-24 w-24 rounded-xl bg-white object-contain shadow-sm ring-1 ring-gray-25 sm:h-28 sm:w-28"
                height="112"
                loading="lazy"
                width="112"
              />
            </div>

            <div class="sm:col-span-6">
              <a
                v-if="skill.shareUrl"
                :href="skill.shareUrl"
                class="text-lg font-semibold text-primary hover:underline"
                rel="noopener noreferrer"
                target="_blank"
              >
                {{ skill.title }}
              </a>

              <div
                v-else
                class="text-lg font-semibold text-gray-90"
              >
                {{ skill.title }}
              </div>

              <p
                v-if="skill.description"
                class="mt-1 whitespace-pre-line text-sm text-gray-70"
              >
                {{ skill.description }}
              </p>
            </div>

            <div
              v-if="skill.acquired"
              class="flex items-center justify-center gap-3 sm:col-span-3 sm:justify-end"
            >
              <a
                v-if="skill.facebookUrl"
                :href="skill.facebookUrl"
                class="font-semibold text-primary hover:underline"
                rel="noopener noreferrer"
                target="_blank"
              >
                Facebook
              </a>

              <a
                v-if="skill.xUrl"
                :href="skill.xUrl"
                class="font-semibold text-primary hover:underline"
                rel="noopener noreferrer"
                target="_blank"
              >
                X
              </a>
            </div>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import DOMPurify from "dompurify"
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const props = defineProps({
  data: {
    type: Object,
    default: () => ({}),
  },
})

const { t } = useI18n()

const blocks = computed(() => (Array.isArray(props.data?.blocks) ? props.data.blocks : []))
const certificate = computed(() => props.data?.certificate || {})
const skills = computed(() => (Array.isArray(props.data?.skills) ? props.data.skills : []))

function sanitizeHtml(content) {
  return DOMPurify.sanitize(String(content || ""), {
    USE_PROFILES: { html: true },
  })
}

function openExternal(url) {
  const target = String(url || "").trim()

  if (!target.startsWith("/") && !target.startsWith("https://") && !target.startsWith("http://")) {
    return
  }

  const popup = window.open(target, "_blank", "noopener,noreferrer")

  if (popup) {
    popup.opener = null
  }
}
</script>

<style>
.lp-final-item-content,
.lp-final-item-certificate-preview {
  box-sizing: border-box;
  min-width: 0;
  width: 100%;
}

.lp-final-item-certificate-preview > * {
  box-sizing: border-box;
  max-width: 100% !important;
  width: 100% !important;
}

.lp-final-item-certificate-preview > img,
.lp-final-item-certificate-preview > p > img:only-child {
  display: block;
  height: auto !important;
  max-width: 100% !important;
  width: 100% !important;
}
</style>
