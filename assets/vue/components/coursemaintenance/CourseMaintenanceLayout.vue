<template>
  <div class="cm-root cm-layout bg-white">
    <!-- Header -->
    <div class="w-full border-b border-gray-25">
      <div class="px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <i class="mdi mdi-wrench-cog text-xl text-gray-90"></i>
          <div>
            <h1
              id="page_title"
              class="text-lg font-semibold tracking-tight"
            >
              {{ t("Course maintenance") }}
            </h1>
            <p class="text-caption text-gray-50">
              {{ t("Manage backups, restore, copy, recycle and delete course data.") }}
            </p>
          </div>
        </div>

        <!-- Course info -->
        <div
          v-if="course"
          class="text-right"
        >
          <div class="text-body-2 text-gray-90">{{ course.title }}</div>
          <div class="text-tiny text-gray-50">({{ course.code }})</div>
        </div>
      </div>

      <!-- Tabs (preserve query context) -->
      <nav class="px-6 pb-3">
        <ul class="flex flex-wrap gap-3 text-body-2">
          <li>
            <BaseButton
              :label="t('Import backup')"
              icon="import"
              :type="isActive('CMImportBackup') ? 'primary-alternative' : 'tertiary-alternative-text'"
              :route="tabTo('CMImportBackup')"
            />
          </li>
          <li>
            <BaseButton
              :label="t('Create a backup')"
              icon="save"
              :type="isActive('CMCreateBackup') ? 'primary-alternative' : 'tertiary-alternative-text'"
              :route="tabTo('CMCreateBackup')"
            />
          </li>
          <li>
            <BaseButton
              :label="t('Copy course')"
              icon="copy"
              :type="isActive('CMCopyCourse') ? 'primary-alternative' : 'tertiary-alternative-text'"
              :route="tabTo('CMCopyCourse')"
            />
          </li>
          <li>
            <BaseButton
              label="IMS CC 1.3"
              icon="package"
              :type="isActive('CMCc13') ? 'primary-alternative' : 'tertiary-alternative-text'"
              :route="tabTo('CMCc13')"
            />
          </li>
          <li>
            <BaseButton
              :label="t('Recycle course')"
              icon="delete"
              :type="isActive('CMRecycle') ? 'primary-alternative' : 'tertiary-alternative-text'"
              :route="tabTo('CMRecycle')"
            />
          </li>
          <li>
            <BaseButton
              :label="t('Completely delete this course')"
              icon="delete-forever"
              :type="isActive('CMDelete') ? 'danger' : 'danger-text'"
              :route="tabTo('CMDelete')"
            />
          </li>
        </ul>
      </nav>
    </div>

    <!-- Body -->
    <div class="px-6 py-6">
      <router-view :key="$route.fullPath" />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"
import BaseButton from "../basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const node = Number(route.params.node)

const cidReq = useCidReqStore()
const { course, session, group } = storeToRefs(cidReq)

/** Build tab links preserving query context */
function tabTo(name) {
  return {
    name,
    params: { node },
    query: route.query,
  }
}

/** Whether the given route name is the currently active tab */
function isActive(name) {
  return route.name === name
}

/** Optionally ensure query context if missing but store knows it */
onMounted(() => {
  const q = { ...route.query }
  let changed = false
  const ensureNum = (val) => (val === null || val === undefined ? null : Number(val))

  const storeCid = ensureNum(course?.value?.id)
  const storeSid = ensureNum(session?.value?.id)
  const storeGid = ensureNum(group?.value?.id)

  if (!q.cid && storeCid) {
    q.cid = String(storeCid)
    changed = true
  }
  if (!q.sid && storeSid) {
    q.sid = String(storeSid)
    changed = true
  }
  if (!q.gid && storeGid) {
    q.gid = String(storeGid)
    changed = true
  }

  if (changed) {
    router.replace({ name: route.name, params: route.params, query: q })
  }
})
</script>
