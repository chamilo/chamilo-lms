<template>
  <div
    v-if="isAdminOrTeacher"
    class="blog-admin"
  >
    <SectionHeader :title="t('Projects')">
      <BaseInputText
        id="search"
        v-model="q"
        :label="t('Search')"
        :placeholder="t('Search for projects...')"
        class="search-input"
        label-position="invisible"
      />
      <BaseButton
        :label="t('New project')"
        icon="plus"
        type="primary"
        @click="openCreate()"
      />
    </SectionHeader>

    <!-- CONTROLS -->
    <BaseToolbar>
      <template #start>
        <BaseCheckbox
          id="visible-filter"
          v-model="onlyVisible"
          :label="t('Only visible')"
          name="visible-filter"
        />
      </template>
      <template #end>
        <BaseSelect
          v-model="sort"
          :label="t('Sort by')"
          :options="sortOptions"
          class="w-44"
          optionLabel="label"
          optionValue="value"
        />
      </template>
    </BaseToolbar>

    <!-- GRID -->
    <div class="cards-grid">
      <div
        v-for="proj in filtered"
        :key="proj.id"
        :class="{ 'card--hidden': !proj.visible }"
        class="card"
      >
        <div class="card-head">
          <div>
            <div class="meta">{{ t("Created") }}: {{ formatDate(proj.createdAt) }}</div>

            <BaseAppLink
              :to="{ name: 'BlogPosts', params: { ...$route.params, blogId: proj.id }, query: $route.query }"
              class="title-link"
            >
              <h4 class="title">{{ proj.title }}</h4>
            </BaseAppLink>

            <div class="subtitle">{{ proj.subtitle }}</div>
          </div>
        </div>

        <div class="owner">
          <i class="mdi mdi-account-outline"></i>
          <span>{{ proj.owner.name }}</span>
        </div>

        <div class="actions icons">
          <BaseAppLink
            :to="{ name: 'BlogPosts', params: { ...$route.params, blogId: proj.id }, query: $route.query }"
            class="no-underline"
          >
            <BaseButton
              :label="t('Open')"
              icon="link-external"
              only-icon
            />
          </BaseAppLink>

          <BaseButton
            :label="t('Rename')"
            aria-label="rename"
            icon="edit"
            only-icon
            type="black"
            @click="openRename(proj)"
          />

          <BaseButton
            :icon="proj.visible ? 'eye-on' : 'eye-off'"
            :label="proj.visible ? t('Hide') : t('Show')"
            only-icon
            type="black"
            @click="toggleVisibility(proj)"
          />

          <BaseButton
            :label="t('Delete')"
            icon="trash"
            only-icon
            type="danger"
            @click="remove(proj)"
          />
        </div>
      </div>
    </div>

    <div class="footer">
      <span class="text-sm text-gray-500">{{ t("Total") }}: {{ total }}</span>
    </div>

    <!-- DIALOGS -->
    <ProjectCreateDialog
      :key="createDialogKey"
      v-model:isVisible="showCreate"
      @submitted="createProject"
    />
    <BaseDialog
      v-model:isVisible="showRename"
      :title="t('Rename project')"
      :width="'520px'"
      header-icon="pencil"
    >
      <BaseInputText
        id="rename"
        v-model="renameTitle"
        :form-submitted="renameSubmitted"
        :is-invalid="!renameTitle"
        :label="t('New title')"
      />
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="closeRename"
        />
        <BaseButton
          :label="t('Save')"
          icon="check"
          type="primary"
          @click="saveRename"
        />
      </template>
    </BaseDialog>
  </div>

  <!-- FALLBACK FOR UNAUTHORIZED USERS -->
  <div
    v-else
    class="p-8 text-center text-gray-600"
  >
    <div class="inline-flex items-center gap-2 text-red-600 font-semibold mb-2">
      <i class="mdi mdi-lock-alert-outline text-2xl"></i>
      <span>{{ t("Access denied") }}</span>
    </div>
    <div class="text-sm">
      {{ t("You need to be a admin or teacher to manage projects.") }}
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import ProjectCreateDialog from "../../components/blog/ProjectCreateDialog.vue"
import service from "../../services/blogs"
import { useSecurityStore } from "../../store/securityStore"
import { RESOURCE_LINK_DRAFT } from "../../constants/entity/resourcelink"
import { useCidReq } from "../../composables/cidReq"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"

const { cid, sid } = useCidReq()
const { t } = useI18n()
const route = useRoute()

// Access control
const securityStore = useSecurityStore()
const isAdminOrTeacher = computed(() => securityStore.isAdmin || securityStore.isTeacher)

// Parent node and default link list (created as draft)
const parentResourceNodeId = ref(Number(route.params.node))
const resourceLinkList = ref([{ sid, cid, visibility: RESOURCE_LINK_DRAFT }])

// UI state
const q = ref("")
const sort = ref("createdAt:desc")
const onlyVisible = ref(false)
const rows = ref([])
const total = ref(0)
const showCreate = ref(false)
const showRename = ref(false)
const renameTarget = ref(null)
const renameTitle = ref("")
const renameSubmitted = ref(false)
const createDialogKey = ref(0)

const sortOptions = [
  { label: "Newest first", value: "createdAt:desc" },
  { label: "Oldest first", value: "createdAt:asc" },
  { label: "Title (A–Z)", value: "title:asc" },
  { label: "Title (Z–A)", value: "title:desc" },
]

function formatDate(iso) {
  try {
    return new Date(iso).toLocaleDateString()
  } catch {
    return iso
  }
}

const filtered = computed(() => {
  let out = [...rows.value]
  if (q.value) out = out.filter((r) => r.title.toLowerCase().includes(q.value.toLowerCase()))
  if (onlyVisible.value) out = out.filter((r) => r.visible)
  const [f, d] = (sort.value || "createdAt:desc").split(":")
  out.sort((a, b) => (a[f] > b[f] ? 1 : -1) * (d === "desc" ? -1 : 1))
  return out
})

async function load() {
  if (!isAdminOrTeacher.value) return
  const res = await service.listProjects()
  rows.value = res.rows
  total.value = res.total
}

// Open/close dialogs
function openCreate() {
  // ensure a fresh form every time dialog opens
  createDialogKey.value++
  showCreate.value = true
}

async function createProject({ title, subtitle }) {
  await service.createProject({
    title,
    subtitle,
    parentResourceNode: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  })
  showCreate.value = false
  await load()
}

function openRename(p) {
  renameTarget.value = p
  renameTitle.value = p?.title || ""
  showRename.value = true
}
function closeRename() {
  showRename.value = false
  renameSubmitted.value = false
  renameTarget.value = null
  renameTitle.value = ""
}
async function saveRename() {
  renameSubmitted.value = true
  if (!renameTitle.value.trim() || !renameTarget.value) return
  await service.renameProject(renameTarget.value.id, renameTitle.value.trim())
  closeRename()
  await load()
}

// Visibility toggle (server handles the toggle and shortcut sync)
async function toggleVisibility(p) {
  await service.toggleProjectVisibility(p.id, !p.visible)
  await load()
}

// Ensure invisibility before delete so shortcut is removed server-side
async function ensureHiddenBeforeDelete(p) {
  if (p.visible) {
    await service.toggleProjectVisibility(p.id, false)
  }
}

async function remove(p) {
  if (!confirm(t("Delete this project? This action cannot be undone."))) return
  await ensureHiddenBeforeDelete(p)
  await service.deleteProject(p.id)
  await load()
}

onMounted(load)
</script>
