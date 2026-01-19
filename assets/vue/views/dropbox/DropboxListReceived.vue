<template>
  <div>
    <BaseToolbar>
      <template #start>
        <div class="flex gap-2">
          <BaseButton
            icon="arrow-up"
            type="black"
            onlyIcon
            :label="t('Up')"
            v-if="categoryId !== 0"
            @click="goRoot"
          />
          <BaseButton
            icon="folder-plus"
            type="primary"
            :label="t('New folder')"
            @click="openCreateFolder('received')"
          />
          <RouterLink
            :to="{ name: 'DropboxCreate', params: $route.params, query: { ...$route.query, from: 'received' } }"
          >
            <BaseButton
              icon="upload"
              type="primary-alternative"
              :label="t('Share a new file')"
            />
          </RouterLink>
        </div>
      </template>
    </BaseToolbar>
    <div
      v-if="categoryId !== 0"
      class="my-2 p-2 border rounded-lg bg-gray-50 flex items-center justify-between"
    >
      <div>
        <i
          :class="chamiloIconToClass['folder-generic']"
          class="mr-1"
        ></i>
        {{ t("Category") }}: <strong>{{ currentCat?.title }}</strong>
      </div>
      <BaseButton
        icon="download"
        type="black"
        :label="t('Download as ZIP')"
        @click="downloadCategoryZip(categoryId)"
      />
    </div>

    <BaseTable
      :values="rowsForTable"
      data-key="rowId"
      v-model:selectedItems="selected"
      :text-for-empty="t('No data available')"
    >
      <template #header>
        <div class="flex items-center gap-2">
          <BaseButton
            type="black"
            :label="t('Select all')"
            icon="check"
            @click="selectAll"
          />
          <BaseButton
            type="black"
            :label="t('Unselect all')"
            icon="xmark"
            @click="clearAll"
          />
          <BaseSelect
            v-model="bulk"
            :options="bulkOptions"
            optionLabel="label"
            optionValue="value"
            style="min-width: 220px"
            label=""
          />
          <BaseButton
            type="primary"
            :disabled="!selectedFiles.length || !bulk"
            :label="t('Apply')"
            icon="play"
            @click="runBulk"
          />
        </div>
      </template>

      <Column
        selectionMode="multiple"
        headerStyle="width:3rem"
      />

      <Column
        :header="t('Type')"
        headerStyle="width:4rem"
        bodyClass="text-center"
      >
        <template #body="{ data }">
          <span class="inline-flex items-center justify-center h-5 w-5">
            <i
              v-if="data.kind === 'folder'"
              :class="chamiloIconToClass['folder-generic']"
              class="text-base leading-none"
            ></i>
            <i
              v-else
              :class="chamiloIconToClass['file-generic']"
              class="text-base leading-none"
            ></i>
          </span>
        </template>
      </Column>

      <Column :header="t('Received Files')">
        <template #body="{ data }">
          <!-- Folder row -->
          <template v-if="data.kind === 'folder'">
            <a
              class="font-semibold cursor-pointer hover:underline"
              @click="enterCat(data.catId)"
            >
              {{ data.title }}
            </a>
          </template>

          <!-- File row -->
          <template v-else>
            <a
              class="mr-2 inline-flex items-center h-5 align-middle"
              :title="t('Download')"
              :href="downloadUrl(data.id)"
              target="_blank"
              rel="noopener"
            >
              <i
                :class="chamiloIconToClass['download']"
                class="text-base leading-none"
              ></i>
            </a>

            <span class="font-semibold align-middle">{{ data.title }}</span>

            <div
              v-if="data.description"
              class="text-sm text-gray-500 mt-1"
            >
              {{ data.description }}
            </div>
          </template>
        </template>
      </Column>

      <Column
        field="sizeHuman"
        :header="t('Size')"
        headerStyle="width:8rem"
      >
        <template #body="{ data }">
          <span v-if="data.kind === 'file'">{{ data.sizeHuman }}</span>
        </template>
      </Column>

      <Column
        :header="t('sender')"
        headerStyle="width:14rem"
      >
        <template #body="{ data }">
          <span v-if="data.kind === 'file'">{{ data.uploaderName }}</span>
        </template>
      </Column>

      <Column
        field="lastUploadAgo"
        :header="t('Latest sent on')"
        headerStyle="width:12rem"
      >
        <template #body="{ data }">
          <span v-if="data.kind === 'file'">{{ data.lastUploadAgo }}</span>
        </template>
      </Column>

      <Column
        :header="t('Edit')"
        headerStyle="width:16rem"
        bodyClass="text-right"
      >
        <template #body="{ data }">
          <template v-if="data.kind === 'file'">
            <div class="inline-flex items-center gap-1 whitespace-nowrap">
              <BaseButton
                icon="comment"
                type="black"
                onlyIcon
                :label="t('Feedback')"
                @click="openFeedback(data)"
              />
              <BaseButton
                icon="file-swap"
                type="black"
                onlyIcon
                :label="t('Move')"
                @click="openMove(data)"
              />
              <a
                :href="downloadUrl(data.id)"
                target="_blank"
                rel="noopener"
              >
                <BaseButton
                  icon="download"
                  type="black"
                  onlyIcon
                  :label="t('Download')"
                />
              </a>
              <BaseButton
                icon="delete"
                type="danger"
                onlyIcon
                :label="t('Delete')"
                @click="remove([data.id])"
              />
            </div>
          </template>
        </template>
      </Column>
    </BaseTable>

    <!-- Move dialog -->
    <DropboxMoveDialog
      v-if="moveFileRef"
      v-model:isVisible="showMove"
      :file-id="moveFileRef.id"
      :file-title="moveFileRef.title"
      area="received"
      :current-category-id="categoryId"
      @moved="onMoved"
    />

    <!-- Feedback dialog -->
    <DropboxFeedbackDialog
      v-if="feedbackFile"
      v-model:isVisible="showFeedback"
      :file-id="feedbackFile.id"
      :file-title="feedbackFile.title"
      @submitted="onFeedbackSubmitted"
    />

    <!-- Create folder -->
    <BaseDialog
      v-model:isVisible="showCatDialog"
      :title="t('New folder')"
      header-icon="folder-plus"
    >
      <BaseInputText
        id="catNameR"
        :label="t('Folder name')"
        v-model="catName"
        :form-submitted="submitted"
        :is-invalid="!catName"
      />
      <template #footer>
        <BaseButton
          type="black"
          :label="t('Cancel')"
          icon="xmark"
          @click="closeCatDialog"
        />
        <BaseButton
          type="primary"
          :label="t('Create folder')"
          icon="check"
          @click="saveCategory"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Column from "primevue/column"

import service from "../../services/dropbox"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import DropboxFeedbackDialog from "./DropboxFeedbackDialog.vue"
import DropboxMoveDialog from "./DropboxMoveDialog.vue"

const { t } = useI18n()

const categoryId = ref(0)
const items = ref([])
const cats = ref([])
const selected = ref([])
const bulk = ref("")
const showCatDialog = ref(false)
const catName = ref("")
const submitted = ref(false)

const currentCat = computed(() => cats.value.find((c) => c.id === categoryId.value))

// Build table rows: show folders when at root (categoryId === 0) + files for current category
const rowsForTable = computed(() => {
  const rows = []

  // Folders at root
  if (categoryId.value === 0) {
    for (const c of cats.value || []) {
      if (!c || c.id === 0) continue
      rows.push({ rowId: `cat:${c.id}`, kind: "folder", catId: c.id, title: c.title })
    }
  }

  // Files for current category
  for (const f of items.value || []) {
    // Normalize uploader -> uploaderName (string/object/JSON string)
    let uploaderName = f.uploader || f.authorName || ""
    if (uploaderName && typeof uploaderName === "object") {
      uploaderName = uploaderName.name || `User #${uploaderName.id}` || ""
    }
    if (typeof uploaderName === "string" && uploaderName.trim().startsWith("{")) {
      try {
        const parsed = JSON.parse(uploaderName)
        uploaderName = parsed?.name || uploaderName
      } catch {}
    }
    rows.push({ rowId: `file:${f.id}`, kind: "file", ...f, uploaderName })
  }

  return rows
})

// Only files (avoid selecting folders for bulk actions)
const selectedFiles = computed(() => selected.value.filter((r) => r.kind === "file"))

const bulkOptions = computed(() => [
  { label: t("Choose an action"), value: "" },
  { label: t("Delete"), value: "delete" },
  { label: t("Download"), value: "download" },
  ...cats.value.filter((c) => c.id !== 0).map((c) => ({ label: `${t("Move")} → ${c.title}`, value: `move:${c.id}` })),
])

async function load() {
  cats.value = await service.listCategories({ area: "received" })
  items.value = await service.listFiles({ area: "received", categoryId: categoryId.value })
}
onMounted(load)

function selectAll() {
  selected.value = rowsForTable.value.filter((r) => r.kind === "file")
}
function clearAll() {
  selected.value = []
}

async function remove(ids) {
  await service.deleteFiles(ids, "received")
  await load()
}

async function runBulk() {
  if (!bulk.value) return
  if (!selectedFiles.value.length) return

  if (bulk.value === "delete") {
    await remove(selectedFiles.value.map((s) => s.id))
  } else if (bulk.value === "download") {
    selectedFiles.value.forEach((f) => window.open(downloadUrl(f.id), "_blank"))
  } else if (bulk.value.startsWith("move:")) {
    const target = Number(bulk.value.split(":")[1])
    for (const row of selectedFiles.value) {
      await service.moveFile({ id: row.id, targetCatId: target, area: "received" })
    }
  }
  selected.value = []
  bulk.value = ""
  await load()
}

function openCreateFolder() {
  showCatDialog.value = true
}
function closeCatDialog() {
  showCatDialog.value = false
  catName.value = ""
  submitted.value = false
}
async function saveCategory() {
  submitted.value = true
  if (!catName.value.trim()) return
  await service.createCategory({ title: catName.value.trim(), area: "received" })
  closeCatDialog()
  await load()
}

function enterCat(id) {
  categoryId.value = id
  selected.value = []
  load()
}
function goRoot() {
  enterCat(0)
}

function downloadUrl(fileId) {
  return service.downloadUrl(fileId)
}

const showFeedback = ref(false)
const feedbackFile = ref(null)
function openFeedback(row) {
  feedbackFile.value = { id: row.id, title: row.title }
  showFeedback.value = true
}
function onFeedbackSubmitted() {
  load()
}

const showMove = ref(false)
const moveFileRef = ref(null)
function openMove(row) {
  moveFileRef.value = { id: row.id, title: row.title }
  showMove.value = true
}
async function onMoved() {
  await load()
}

function downloadCategoryZip(catId) {
  const url = service.categoryZipUrl(catId, "received")
  window.open(url, "_blank")
}
</script>
