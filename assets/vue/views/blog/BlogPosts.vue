<template>
  <div class="grid gap-4 md:grid-cols-[280px,1fr] blog-posts">
    <!-- LEFT SIDEBAR -->
    <aside class="space-y-4">
      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-semibold">{{ t("Calendar") }}</div>
        <div class="p-3">
          <CalendarMini
            :year="calendarYear"
            :month="calendarMonth"
            :selected="selectedDate"
            @select="onSelectDate"
            @prev="prevMonth"
            @next="nextMonth"
          />
        </div>
      </div>

      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-semibold">{{ t("Search") }}</div>
        <div class="p-3 space-y-2">
          <BaseInputText
            v-model="q"
            id="blog-q"
            :placeholder="t('Search')"
            class="w-full"
            label=""
          />
          <BaseButton
            type="black"
            icon="search"
            :label="t('Search')"
            class="w-full"
            @click="reload()"
          />
        </div>
      </div>

      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-semibold">{{ t("My tasks") }}</div>
        <div class="p-3">
          <MyTasksPanel :assignments="assignments" :tasks="tasks" />
        </div>
      </div>
    </aside>

    <!-- MAIN PANEL -->
    <section class="space-y-4">
      <BaseToolbar :showTopBorder="true">
        <template #start>
          <div class="flex items-center gap-3">
            <h3 class="font-semibold text-lg m-0">
              {{ t(viewMode === 'posts' ? "Posts" : "Tasks") }}
            </h3>
            <span class="text-gray-400">·</span>
            <span class="text-sm text-gray-500" v-if="viewMode==='posts'">
              {{ t("Browse and publish new posts") }}
            </span>
            <span class="text-sm text-gray-500" v-else>
              {{ t("Create and assign tasks") }}
            </span>
          </div>
        </template>
        <template #end>
          <div class="flex items-center gap-2">
            <div class="segmented">
              <button
                :class="['seg-btn', viewMode==='posts' && 'active']"
                @click="setMode('posts')"
                :title="t('Posts')"
              >
                {{ t("Posts") }}
              </button>
              <button
                :class="['seg-btn', viewMode==='tasks' && 'active']"
                @click="setMode('tasks')"
                :title="t('Tasks')"
              >
                {{ t("Tasks") }}
              </button>
            </div>

            <BaseSelect
              v-if="viewMode==='posts'"
              v-model="sort"
              :options="sortOptions"
              optionLabel="label"
              optionValue="value"
              label=""
            />
            <BaseSelect
              v-else
              v-model="taskSort"
              :options="taskSortOptions"
              optionLabel="label"
              optionValue="value"
              label=""
            />

            <BaseButton
              v-if="viewMode==='posts'"
              type="primary"
              icon="plus"
              :label="t('New post')"
              @click="openCreate"
            />
            <BaseButton
              v-else
              type="primary"
              icon="plus"
              :label="t('New task')"
              @click="showCreateTask = true"
            />
          </div>
        </template>
      </BaseToolbar>

      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b flex items-center justify-between">
          <div class="text-sm text-gray-600">
            <template v-if="loading">{{ t('Loading...') }}</template>
            <template v-else>
              <span v-if="viewMode==='posts'">
                {{ t("Showing {n} posts", { n: total }) }}
                <span v-if="selectedDate" class="text-gray-400">
                  — {{ t("Filtered by {0}", [selectedDate]) }}
                </span>
              </span>
              <span v-else>
                {{ t("Showing {n} tasks", { n: tasksFiltered.length }) }}
              </span>
            </template>
          </div>
          <BaseSelect
            v-if="viewMode==='posts'"
            v-model="pageSize"
            :options="pageSizeOptions"
            optionLabel="label"
            optionValue="value"
            label=""
          />
        </div>

        <!-- POSTS MODE -->
        <template v-if="viewMode==='posts'">
          <div v-if="loading" class="p-4 space-y-3">
            <div v-for="i in 3" :key="i" class="animate-pulse space-y-2">
              <div class="h-4 w-56 bg-gray-20 rounded"></div>
              <div class="h-3 w-80 bg-gray-10 rounded"></div>
            </div>
          </div>

          <div v-else-if="!rows.length" class="p-6 text-center text-gray-500">
            {{ t("No posts") }}
          </div>

          <ul v-else class="divide-y">
            <li
              v-for="row in rows"
              :key="row.id"
              class="p-4 hover:bg-gray-20 cursor-pointer"
              @click="go(row)"
            >
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-base font-semibold text-blue-700 hover:underline">
                    {{ row.title }}
                  </div>
                  <div class="text-sm text-gray-500 mt-0.5 flex items-center gap-3">
                    <span>{{ t("By") }} {{ row.author }} · {{ row.date }}</span>
                    <span v-if="row.tags?.length" class="text-xs text-gray-400">
                      — {{ row.tags.join(", ") }}
                    </span>

                    <!-- Attachments indicator -->
                    <span
                      v-if="(attachCount[row.id] ?? row.attachmentsCount ?? 0) > 0"
                      class="text-xs text-gray-500 flex items-center gap-1"
                    >
                      <i class="mdi mdi-paperclip"></i>
                      {{ attachCount[row.id] ?? row.attachmentsCount }}
                    </span>
                  </div>
                  <div class="text-sm mt-2 text-gray-700">{{ row.excerpt }}</div>
                </div>
                <div class="text-xs text-gray-500 text-right">
                  <div>{{ t("{0} comments", [row.comments]) }}</div>
                  <div class="mt-1 flex items-center gap-1 justify-end">
                    <i class="mdi mdi-star text-amber-500"></i>
                    <span>{{ (ratings[row.id]?.average ?? 0).toFixed(1) }}</span>
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <div class="p-3 border-t flex items-center justify-between">
            <div class="text-xs text-gray-500">
              {{ t("Page {p} of {pages}", { p: page, pages }) }}
            </div>
            <div class="flex gap-2">
              <BaseButton
                type="black"
                icon="arrow-left"
                :label="t('Prev')"
                :disabled="page<=1"
                @click="page--"
              />
              <BaseButton
                type="black"
                icon="arrow-right"
                :label="t('Next')"
                :disabled="page>=pages"
                @click="page++"
              />
            </div>
          </div>
        </template>

        <!-- TASKS MODE -->
        <template v-else>
          <div v-if="!tasksFiltered.length" class="p-6 text-center text-gray-500">
            {{ t("No tasks") }}
          </div>
          <div v-else class="p-4 space-y-3">
            <div
              v-for="task in tasksFiltered"
              :key="task.id"
              class="p-4 border rounded-lg bg-gray-20 flex items-start justify-between"
            >
              <div>
                <div class="font-semibold">{{ task.title }}</div>
                <div class="text-sm text-gray-600">{{ task.description }}</div>
              </div>
              <span class="px-2 py-1 text-white text-xs rounded" :style="{ background: task.color }">
                {{ task.status }}
              </span>
            </div>
          </div>
        </template>
      </div>
    </section>

    <!-- DIALOGS -->
    <PostCreateDialog
      v-if="showCreate"
      @close="showCreate = false"
      @created="onCreated"
    />
    <TaskCreateDialog
      v-if="showCreateTask"
      @close="showCreateTask=false"
      @created="reloadTasks"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter, useRoute } from "vue-router"
import { storeToRefs } from "pinia"

import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import CalendarMini from "../../components/blog/CalendarMini.vue"
import MyTasksPanel from "../../components/blog/MyTasksPanel.vue"
import PostCreateDialog from "../../components/blog/PostCreateDialog.vue"
import TaskCreateDialog from "../../components/blog/TaskCreateDialog.vue"

import service from "../../services/blogs"
import { useSecurityStore } from "../../store/securityStore"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

// Course/session context + current user
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const securityStore = useSecurityStore()
const currentUser = computed(() => securityStore.user)

// Blog metadata
const blogTitle = ref("")
const blogSubtitle = ref("")

async function loadBlogMeta() {
  const blogId = Number(route.params.blogId)
  try {
    if (typeof service.getProject === "function") {
      const meta = await service.getProject(blogId)
      blogTitle.value = meta?.title || ""
      blogSubtitle.value = meta?.subtitle || ""
      return
    }
  } catch (e) {
    console.warn("getProject() failed, falling back to getBlog()", e)
  }

  try {
    const meta = await service.getBlog(blogId)
    blogTitle.value = meta?.title || ""
    blogSubtitle.value = meta?.subtitle || ""
  } catch (e) {
    // eslint-disable-next-line no-console
    console.warn("loadBlogMeta() failed", e)
    blogTitle.value = ""
    blogSubtitle.value = ""
  }
}

// Sidebar state
const today = new Date()
const calendarYear = ref(today.getFullYear())
const calendarMonth = ref(today.getMonth() + 1) // 1-12
const selectedDate = ref("") // YYYY-MM-DD
function onSelectDate(iso){ selectedDate.value = iso; page.value = 1; reload() }
function prevMonth(){ const d=new Date(calendarYear.value, calendarMonth.value-2, 1); calendarYear.value=d.getFullYear(); calendarMonth.value=d.getMonth()+1 }
function nextMonth(){ const d=new Date(calendarYear.value, calendarMonth.value, 1); calendarYear.value=d.getFullYear(); calendarMonth.value=d.getMonth()+1 }

// Top filters
const viewMode = ref("posts") // 'posts' | 'tasks'
function setMode(m){ viewMode.value = m }
const q = ref("")
const sort = ref("dateCreation:desc") // API field is dateCreation
const taskSort = ref("title:asc")

// Pagination
const page = ref(1)
const pageSize = ref(10)
const total = ref(0)
const pages = computed(() => Math.max(1, Math.ceil(total.value / pageSize.value)))

// Posts data
const rows = ref([])
const loading = ref(false)
const ratings = ref({})         // { [postId]: { average, count } }
const attachCount = ref({})     // { [postId]: number }

const sortOptions = [
  { label: "Newest first", value: "dateCreation:desc" },
  { label: "Oldest first", value: "dateCreation:asc" },
  { label: "Title A–Z", value: "title:asc" },
  { label: "Title Z–A", value: "title:desc" },
]
const taskSortOptions = [
  { label: "Title A–Z", value: "title:asc" },
  { label: "Title Z–A", value: "title:desc" },
]
const pageSizeOptions = [
  { label: "10 / page", value: 10 },
  { label: "20 / page", value: 20 },
  { label: "50 / page", value: 50 },
]

// Load posts from API
async function loadPosts(){
  loading.value = true
  const blogId = Number(route.params.blogId)
  try {
    const { rows: data, total: tot } = await service.listPostsApi({
      blogId,
      page: page.value,
      pageSize: pageSize.value,
      q: q.value,
      order: sort.value,
    })

    // Optional date filter (client-side)
    const filtered = selectedDate.value
      ? data.filter(p => (p.date || "").startsWith(selectedDate.value))
      : data

    rows.value = filtered
    total.value = selectedDate.value ? filtered.length : tot

    // Ratings prefetch
    const ids = filtered.map(p => p.id)
    ratings.value = await service.getManyPostRatingsApi(blogId, ids)

    // Attachments count (use provided count if present, otherwise fetch)
    attachCount.value = {}
    await Promise.all(
      filtered.map(async (p) => {
        if (typeof p.attachmentsCount === 'number') {
          attachCount.value[p.id] = p.attachmentsCount
          return
        }
        const list = await service.listPostAttachmentsApi(p.id)
        attachCount.value[p.id] = Array.isArray(list) ? list.length : 0
      })
    )
  } catch (e) {
    // Keep UI clean when API fails: no mock data.
    // eslint-disable-next-line no-console
    console.warn("loadPosts() error", e)
    rows.value = []
    total.value = 0
    ratings.value = {}
    attachCount.value = {}
  } finally {
    loading.value = false
  }
}

async function reload(){ if (viewMode.value==='posts') await loadPosts() }

watch([q, sort, page, pageSize], () => { if(viewMode.value==='posts') loadPosts() })
watch(viewMode, () => {
  if (viewMode.value === 'posts') {
    loadPosts()
  } else {
    reloadTasks()
  }
})

function go(row){
  router.push({
    name: "BlogPostDetail",
    params: { blogId: route.params.blogId, postId: row.id },
    query: route.query
  })
}

// Create post dialog
const showCreate = ref(false)
function openCreate(){ showCreate.value = true }
async function onCreated(){ showCreate.value = false; page.value = 1; await loadPosts() }

// Tasks (only for current user and current session)
const tasks = ref([])
const assignments = ref([])

async function reloadTasks(){
  const blogId = Number(route.params.blogId)
  try {
    tasks.value = await service.listTasks(blogId)
  } catch (e) {
    // eslint-disable-next-line no-console
    console.warn("reloadTasks() error", e)
    tasks.value = []
  }
}

const tasksFiltered = computed(() => {
  let out = [...tasks.value]
  const [f,d] = (taskSort.value || "title:asc").split(":")
  out.sort((a,b) => (a[f] > b[f] ? 1 : -1) * (d==="desc" ? -1 : 1))
  return out
})

async function reloadAssignments(){
  const blogId = Number(route.params.blogId)
  const sid = session.value?.id || 0
  try {
    // If the endpoint supports filtering by user, pass it too
    if (typeof service.listMyAssignments === "function" && currentUser.value?.id) {
      assignments.value = await service.listMyAssignments({
        blogId,
        sessionId: sid,
        userId: currentUser.value.id,
      })
    } else {
      assignments.value = await service.listAssignments({ blogId, sessionId: sid })
    }
  } catch (e) {
    // eslint-disable-next-line no-console
    console.warn("reloadAssignments() error", e)
    assignments.value = []
  }
}

onMounted(async () => {
  await loadBlogMeta()
  await reloadTasks()
  await reloadAssignments()
  await loadPosts()
})
</script>
