<template>
  <div class="space-y-4">
    <BaseToolbar>
      <template #start><h3 class="text-lg font-semibold m-0">{{ t("Members") }}</h3></template>
      <template #end>
        <div class="flex items-center gap-2">
          <BaseInputText v-model="q" :placeholder="t('Search for members...')" id="m-q" label=""/>
          <BaseButton type="primary" icon="plus" :label="t('Add')" @click="openPicker" />
        </div>
      </template>
    </BaseToolbar>

    <!-- Current members -->
    <div class="rounded-lg border bg-white shadow-sm">
      <div class="p-3 border-b text-sm font-medium">{{ t("Current members") }}</div>

      <div v-if="loadingMembers" class="p-4">
        <div class="animate-pulse h-4 w-40 bg-gray-100 rounded"></div>
      </div>

      <div v-else-if="!filteredMembers.length" class="p-6 text-center text-gray-500">
        {{ t("No members found.") }}
      </div>

      <ul v-else class="divide-y">
        <li v-for="m in filteredMembers" :key="m.relId" class="p-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <!-- Show avatar when available; otherwise draw initials -->
            <img
              v-if="m.avatar"
              :src="m.avatar"
              alt=""
              class="h-8 w-8 rounded-full object-cover"
            />
            <div
              v-else
              class="h-8 w-8 rounded-full bg-gray-200 grid place-items-center text-xs font-semibold text-gray-700"
            >
              {{ initials(m.name) }}
            </div>

            <div class="text-sm">
              <div class="font-medium">{{ m.name }}</div>
              <div class="text-gray-500">{{ m.role }}</div>
            </div>
          </div>

          <BaseButton
            type="danger"
            icon="trash"
            :label="t('Remove')"
            :disabled="removing === m.relId"
            @click="removeMember(m)"
          />
        </li>
      </ul>
    </div>

    <!-- Add members picker (modal) -->
    <div v-if="showPicker" class="fixed inset-0 z-30">
      <div class="absolute inset-0 bg-black/30" @click="closePicker"></div>
      <div class="absolute inset-0 grid place-items-center">
        <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl border p-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-base font-semibold m-0">{{ t("Add members") }}</h4>
            <button class="text-gray-500 hover:text-gray-700" @click="closePicker">
              <i class="mdi mdi-close"></i>
            </button>
          </div>

          <div class="flex items-center gap-2 mb-3">
            <BaseInputText v-model="poolQ" :placeholder="t('Search for users in course/session...')" id="pool-q" label=""/>
            <span class="text-xs text-gray-500">
              {{ t("Total") }}: {{ filteredPool.length }}
            </span>
          </div>

          <div class="border rounded">
            <div v-if="loadingPool" class="p-4">
              <div class="animate-pulse h-4 w-56 bg-gray-100 rounded"></div>
            </div>
            <div v-else-if="!filteredPool.length" class="p-6 text-center text-gray-500">
              {{ t("No users available.") }}
            </div>
            <ul v-else class="max-h-[360px] overflow-auto divide-y">
              <li
                v-for="u in filteredPool"
                :key="u.id"
                class="p-3 flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <!-- Avatar or initials for users in the picker -->
                  <img
                    v-if="u.avatar"
                    :src="u.avatar"
                    alt=""
                    class="h-8 w-8 rounded-full object-cover"
                  />
                  <div
                    v-else
                    class="h-8 w-8 rounded-full bg-gray-200 grid place-items-center text-xs font-semibold text-gray-700"
                  >
                    {{ initials(u.name) }}
                  </div>
                  <div class="text-sm">
                    <div class="font-medium">{{ u.name }}</div>
                    <div class="text-gray-500">{{ u.meta }}</div>
                  </div>
                </div>

                <BaseButton
                  type="primary"
                  icon="plus"
                  :label="t('Add')"
                  :disabled="addingUserId === u.id"
                  @click="addUser(u)"
                />
              </li>
            </ul>
          </div>

          <div class="mt-3 flex justify-end">
            <BaseButton type="black" icon="close" :label="t('Close')" @click="closePicker" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Blog members manager:
 * - Lists current blog members (from c_blog_rel_user)
 * - Lets you add users enrolled in the course or session
 * - Simple modal "picker" with search
 *
 * Only required changes:
 * - Show full name and avatar (fallback to initials).
 */
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import service from "../../services/blogs"

const { t } = useI18n()
const route = useRoute()
const blogId = Number(route.params.blogId)

const loadingMembers = ref(true)
const members = ref([])          // { relId, userId, name, avatar, role }
const removing = ref(null)

const showPicker = ref(false)
const loadingPool = ref(false)
const pool = ref([])             // [{ id, name, avatar, meta }]
const addingUserId = ref(null)

const q = ref("")      // search in current members
const poolQ = ref("")  // search in pool

const filteredMembers = computed(() => {
  const v = q.value.trim().toLowerCase()
  if (!v) return members.value
  return members.value.filter(m => m.name.toLowerCase().includes(v))
})

const filteredPool = computed(() => {
  const v = poolQ.value.trim().toLowerCase()
  if (!v) return pool.value
  return pool.value.filter(u => u.name.toLowerCase().includes(v))
})

function initials(name) {
  // Build simple initials from first two words of the name
  const parts = String(name || "").trim().split(/\s+/).slice(0, 2)
  return parts.map(s => s[0]?.toUpperCase() || "").join("")
}

async function loadMembers() {
  loadingMembers.value = true
  try {
    members.value = await service.listBlogMembers(blogId)
  } finally {
    loadingMembers.value = false
  }
}

async function openPicker() {
  showPicker.value = true
  await loadPool()
}

function closePicker() {
  showPicker.value = false
  pool.value = []
  poolQ.value = ""
}

async function loadPool() {
  loadingPool.value = true
  try {
    // Load all course/session users and subtract the ones already in the blog
    const [allUsers, current] = await Promise.all([
      service.listCourseOrSessionUsers(),
      service.listBlogMembers(blogId),
    ])
    const currentIds = new Set(current.map(m => m.userId))
    pool.value = allUsers.filter(u => !currentIds.has(u.id))
  } finally {
    loadingPool.value = false
  }
}

async function addUser(u) {
  addingUserId.value = u.id
  try {
    await service.addBlogMember(blogId, u.id)
    await loadMembers()
    pool.value = pool.value.filter(x => x.id !== u.id)
  } finally {
    addingUserId.value = null
  }
}

async function removeMember(m) {
  removing.value = m.relId
  try {
    await service.removeBlogMember(m.relId)
    await loadMembers()
  } finally {
    removing.value = null
  }
}

onMounted(loadMembers)
</script>
