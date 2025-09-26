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
            v-if="categoryId!==0"
            @click="goRoot"
          />
          <BaseButton
            icon="folder-plus"
            type="primary"
            :label="t('New folder')"
            @click="openCreateFolder('sent')"
          />
          <RouterLink :to="{ name:'DropboxCreate', params:$route.params, query:$route.query }">
            <BaseButton icon="upload" type="primary-alternative" :label="t('Share a new file')" />
          </RouterLink>
        </div>
      </template>
    </BaseToolbar>

    <div v-if="categoryId!==0" class="my-2 p-2 border rounded-lg bg-gray-50 flex items-center justify-between">
      <div>
        <i :class="chamiloIconToClass['folder-generic']" class="mr-1"></i>
        {{ t('Category') }}: <strong>{{ currentCat?.title }}</strong>
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
          <BaseButton type="black" :label="t('Select all')" icon="check" @click="selectAll" />
          <BaseButton type="black" :label="t('Unselect all')" icon="xmark" @click="clearAll" />
          <BaseSelect
            v-model="bulk"
            :options="bulkOptions"
            optionLabel="label"
            optionValue="value"
            style="min-width:220px"
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

      <Column selectionMode="multiple" headerStyle="width:3.2rem" />
      <Column :header="t('Type')" bodyClass="text-center" headerStyle="width:4rem">
        <template #body="{ data }">
          <i v-if="data.kind==='folder'" :class="chamiloIconToClass['folder-generic']"></i>
          <i v-else :class="chamiloIconToClass['file-generic']"></i>
        </template>
      </Column>

      <Column :header="t('Sent')">
        <template #body="{ data }">
          <template v-if="data.kind==='folder'">
            <a class="font-semibold cursor-pointer hover:underline" @click="enterCat(data.catId)">{{ data.title }}</a>
          </template>
          <template v-else>
            <a
              class="mr-2 inline-flex items-center"
              :title="t('Download')"
              :href="downloadUrl(data.id)"
              target="_blank"
              rel="noopener"
            >
              <i :class="chamiloIconToClass['download']"></i>
            </a>
            <span class="font-semibold">{{ data.title }}</span>
            <div v-if="data.description" class="text-sm text-gray-500">{{ data.description }}</div>
          </template>
        </template>
      </Column>

      <Column field="sizeHuman" :header="t('Size')" headerStyle="width:8rem">
        <template #body="{ data }">
          <span v-if="data.kind==='file'">{{ data.sizeHuman }}</span>
        </template>
      </Column>

      <Column :header="t('Visible to')" headerStyle="width:18rem">
        <template #body="{ data }">
          <span v-if="data.kind==='file'">
            {{
              Array.isArray(data.recipients)
                ? data.recipients
                  .map(r => (typeof r === 'string' ? r : r?.name))
                  .filter(Boolean)
                  .join(', ')
                : (typeof data.recipients === 'string' ? data.recipients : '')
            }}
          </span>
        </template>
      </Column>

      <Column field="lastUploadAgo" :header="t('Latest sent on')" headerStyle="width:12rem">
        <template #body="{ data }">
          <span v-if="data.kind==='file'">{{ data.lastUploadAgo }}</span>
        </template>
      </Column>

      <Column :header="t('Edit')" headerStyle="width:16rem" bodyClass="text-right">
        <template #body="{ data }">
          <!-- Folder actions -->
          <template v-if="data.kind==='folder'">
            <BaseButton
              icon="download"
              type="black"
              onlyIcon
              :label="t('Download as ZIP')"
              @click="downloadCategoryZip(data.catId)"
            />
            <BaseButton
              icon="pencil"
              type="black"
              onlyIcon
              :label="t('Rename')"
              @click="openRenameFolder(data)"
            />
            <BaseButton
              icon="trash"
              type="danger"
              onlyIcon
              :label="t('Delete')"
              @click="deleteFolder(data)"
            />
          </template>

          <!-- File actions -->
          <template v-else>
            <BaseButton icon="comment" type="black" onlyIcon :label="t('Feedback')" @click.stop="openFeedback(data)" />
            <BaseButton icon="file-upload" type="black" onlyIcon :label="t('Update')" @click.stop="openUpdate(data)" />
            <BaseButton icon="file-swap" type="black" onlyIcon :label="t('Move')" @click.stop="openMove(data)" />
            <a :href="downloadUrl(data.id)" target="_blank" rel="noopener">
              <BaseButton icon="download" type="black" onlyIcon :label="t('Download')" />
            </a>
            <BaseButton icon="delete" type="danger" onlyIcon :label="t('Delete')" @click="remove([data.id])" />
          </template>
        </template>
      </Column>
    </BaseTable>

    <DropboxUpdateDialog
      v-if="updateFileRef"
      v-model:isVisible="showUpdate"
      :file-id="updateFileRef.id"
      :file-title="updateFileRef.title"
      @updated="load"
    />

    <!-- Move dialog -->
    <DropboxMoveDialog
      v-if="moveFileRef"
      v-model:isVisible="showMove"
      :file-id="moveFileRef.id"
      :file-title="moveFileRef.title"
      area="sent"
      :current-category-id="categoryId"
      @moved="onMoved"
    />

    <!-- Create folder -->
    <BaseDialog v-model:isVisible="showCatDialog" :title="t('New folder')" header-icon="folder-plus">
      <BaseInputText
        id="catName"
        :label="t('Folder name')"
        v-model="catName"
        :form-submitted="submitted"
        :is-invalid="!catName"
      />
      <template #footer>
        <BaseButton type="black" :label="t('Cancel')" icon="xmark" @click="closeCatDialog" />
        <BaseButton type="primary" :label="t('Create folder')" icon="check" @click="saveCategory" />
      </template>
    </BaseDialog>

    <!-- Rename folder -->
    <BaseDialog v-model:isVisible="showRenameDialog" :title="t('Rename folder')" header-icon="pencil">
      <BaseInputText
        id="catRename"
        :label="t('New name')"
        v-model="renameName"
        :form-submitted="renameSubmitted"
        :is-invalid="!renameName"
      />
      <template #footer>
        <BaseButton type="black" :label="t('Cancel')" icon="xmark" @click="closeRenameDialog" />
        <BaseButton type="primary" :label="t('Save')" icon="check" @click="saveRename" />
      </template>
    </BaseDialog>

    <!-- Feedback dialog -->
    <DropboxFeedbackDialog
      v-if="feedbackFile"
      v-model:isVisible="showFeedback"
      :file-id="feedbackFile.id"
      :file-title="feedbackFile.title"
      @submitted="onFeedbackSubmitted"
    />
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
import DropboxUpdateDialog from "./DropboxUpdateDialog.vue"
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

const showRenameDialog = ref(false)
const renameName = ref("")
const renameTarget = ref(null)
const renameSubmitted = ref(false)

const currentCat = computed(() => cats.value.find(c => c.id === categoryId.value))
const selectedFiles = computed(() => selected.value.filter(r => r.kind === 'file'))

const bulkOptions = computed(() => [
  { label: t("Choose an action"), value: "" },
  { label: t("Delete"), value: "delete" },
  { label: t("Download"), value: "download" },
  ...cats.value
    .filter(c => c.id !== 0)
    .map(c => ({ label: `${t('Move')} → ${c.title}`, value: `move:${c.id}` })),
])

async function load() {
  cats.value = await service.listCategories({ area: "sent" })
  items.value = await service.listFiles({ area: "sent", categoryId: categoryId.value })
}
onMounted(load)

const rowsForTable = computed(() => {
  const rows = []
  if (categoryId.value === 0) {
    for (const c of (cats.value || [])) {
      if (!c || c.id === 0) continue
      rows.push({ rowId: `cat:${c.id}`, kind: 'folder', catId: c.id, title: c.title })
    }
  }
  for (const f of (items.value || [])) {
    rows.push({ rowId: `file:${f.id}`, kind: 'file', ...f })
  }
  return rows
})

function enterCat(id) { categoryId.value = id; selected.value = []; load() }
function goRoot() { enterCat(0) }

function selectAll() { selected.value = rowsForTable.value.filter(r => r.kind === 'file') }
function clearAll() { selected.value = [] }

async function remove(ids) {
  await service.deleteFiles(ids, "sent")
  await load()
}

async function runBulk() {
  if (!bulk.value) return
  const filesOnly = selectedFiles.value
  if (!filesOnly.length) return

  if (bulk.value === "delete") {
    await remove(filesOnly.map(s => s.id))
  } else if (bulk.value === "download") {
    filesOnly.forEach(f => window.open(downloadUrl(f.id), "_blank"))
  } else if (bulk.value.startsWith("move:")) {
    const target = Number(bulk.value.split(":")[1])
    for (const row of filesOnly) {
      await service.moveFile({ id: row.id, targetCatId: target, area: "sent" })
    }
  }
  selected.value = []
  bulk.value = ""
  await load()
}

// Folder create/rename/delete
function openCreateFolder() { showCatDialog.value = true }
function closeCatDialog() { showCatDialog.value = false; catName.value = ""; submitted.value = false }
async function saveCategory() {
  submitted.value = true
  if (!catName.value.trim()) return
  await service.createCategory({ title: catName.value.trim(), area: "sent" })
  closeCatDialog()
  await load()
}

function openRenameFolder(row) {
  renameTarget.value = row
  renameName.value = row?.title || ""
  showRenameDialog.value = true
}
function closeRenameDialog() {
  showRenameDialog.value = false
  renameName.value = ""
  renameTarget.value = null
  renameSubmitted.value = false
}
async function saveRename() {
  renameSubmitted.value = true
  if (!renameName.value.trim() || !renameTarget.value) return
  await service.renameCategory({ id: renameTarget.value.catId, title: renameName.value.trim(), area: "sent" })
  closeRenameDialog()
  await load()
}
async function deleteFolder(row) {
  if (!row?.catId) return
  if (!confirm(t("Delete this folder? All files inside will be permanently deleted."))) return
  await service.deleteCategory({ id: row.catId, area: "sent" })
  if (categoryId.value === row.catId) categoryId.value = 0
  await load()
}

// Download URL helper
function downloadUrl(fileId) { return service.downloadUrl(fileId) }

// Feedback
const showFeedback = ref(false)
const feedbackFile = ref(null)
function openFeedback(row) {
  feedbackFile.value = { id: row.id, title: row.title }
  showFeedback.value = true
}
function onFeedbackSubmitted() { load() }

// Update
const showUpdate = ref(false)
const updateFileRef = ref(null)
function openUpdate(row) {
  updateFileRef.value = { id: row.id, title: row.title }
  showUpdate.value = true
}

// Move (dialog)
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
  const url = service.categoryZipUrl(catId, "sent")
  window.open(url, "_blank")
}
</script>
