<template>
  <div>
    <SectionHeader :title="t('Links')">
      <template #end>
        <StudentViewButton
          v-if="securityStore.isAuthenticated"
          @change="refreshForViewToggle"
        />
      </template>
    </SectionHeader>
    <BaseToolbar v-if="securityStore.isAuthenticated && canEditLinks">
      <BaseButton
        :label="t('Add a link')"
        icon="link-add"
        type="black"
        @click="redirectToCreateLink"
      />
      <BaseButton
        :label="t('Add a category')"
        icon="folder-plus"
        type="black"
        @click="redirectToCreateLinkCategory"
      />
      <BaseButton
        :label="t('Export to PDF')"
        icon="file-pdf"
        type="black"
        @click="exportToPDF"
      />
    </BaseToolbar>

    <LinkCategoryCard v-if="isLoading">
      <template #header>
        <Skeleton class="h-6 w-48" />
      </template>
      <div class="flex flex-col gap-4">
        <Skeleton class="ml-2 h-6 w-52" />
        <Skeleton class="ml-2 h-6 w-64" />
        <Skeleton class="ml-2 h-6 w-60" />
      </div>
    </LinkCategoryCard>

    <div v-if="!isLoading && !linksWithoutCategory.length && !categories.length">
      <EmptyState
        :summary="t('Add your first link to this course')"
        icon="link"
      >
        <BaseButton
          v-if="canEditLinks"
          :label="t('Add a link')"
          class="mt-4"
          icon="link-add"
          type="primary"
          @click="redirectToCreateLink"
        />
      </EmptyState>
    </div>

    <div
      v-if="!isLoading"
      class="flex flex-col gap-4"
    >
      <!-- General (always visible when editable to allow dropping) -->
      <LinkCategoryCard
        v-if="showGeneralCard"
        :showHeader="false"
      >
        <template #header>
          <div class="flex items-center justify-between gap-4">
            <h5>{{ t("General") }}</h5>

            <div
              v-if="canEditLinks"
              class="flex items-center gap-2 text-xs text-gray-600"
            >
              <BaseIcon
                icon="dots-vertical"
                size="small"
              />
              <span>{{ t("Drag links to reorder") }}</span>
            </div>
          </div>
        </template>

        <Draggable
          tag="ul"
          class="min-h-[48px] py-2"
          :list="linksWithoutCategory"
          item-key="iid"
          :disabled="!canEditLinks || isMoving"
          :group="{ name: 'links', pull: true, put: true }"
          :animation="150"
          handle=".link-drag-handle"
          ghost-class="link-ghost"
          chosen-class="link-chosen"
          drag-class="link-drag"
          :force-fallback="true"
          :fallback-on-body="true"
          :data-category-id="0"
          :empty-insert-threshold="80"
          @end="onLinkDndEnd"
        >
          <template #item="{ element: link }">
            <li class="mb-3">
              <LinkItem
                :isLinkValid="linkValidationResults[link.iid]"
                :link="link"
                :can-edit="canEditLinks"
                @check="() => checkLink(link.iid, link.url)"
                @delete="confirmDeleteLink"
                @edit="editLink"
                @toggle="toggleVisibility"
              />
            </li>
          </template>
        </Draggable>

        <p
          v-if="linksWithoutCategory.length === 0"
          class="text-sm text-gray-600"
        >
          {{ t("Drop links here to keep them without category") }}
        </p>
      </LinkCategoryCard>

      <!-- Categories -->
      <div
        v-if="!canMoveCategories"
        class="flex flex-col gap-4"
      >
        <LinkCategoryCard
          v-for="category in categories"
          :key="category.key"
          :showHeader="true"
        >
          <template #header>
            <div class="flex justify-between gap-4">
              <div class="flex items-center">
                <BaseIcon
                  class="mr-2"
                  icon="folder-generic"
                  size="normal"
                />
                <h5>{{ category.info.title }}</h5>
              </div>

              <div
                v-if="securityStore.isAuthenticated && canEditLinks"
                class="flex items-center gap-3 text-gray-700"
              >
                <BaseIcon
                  icon="edit"
                  size="normal"
                  :title="t('Edit')"
                  class="hover:text-black"
                  @click="editCategory(category)"
                />
                <BaseIcon
                  :icon="isVisible(category.info.visible) ? 'eye-on' : 'eye-off'"
                  size="normal"
                  :title="t('Change visibility')"
                  class="hover:text-black"
                  @click="toggleCategoryVisibility(category)"
                />
                <BaseIcon
                  icon="delete"
                  size="normal"
                  :title="t('Delete')"
                  class="hover:text-black"
                  @click="confirmDeleteCategory(category)"
                />
              </div>
            </div>

            <p v-if="category.info.description">{{ category.info.description }}</p>
          </template>

          <Draggable
            tag="ul"
            class="min-h-[48px] py-2"
            :list="category.links"
            item-key="iid"
            :disabled="!canEditLinks || isMoving"
            :group="{ name: 'links', pull: true, put: true }"
            :animation="150"
            handle=".link-drag-handle"
            ghost-class="link-ghost"
            chosen-class="link-chosen"
            drag-class="link-drag"
            :force-fallback="true"
            :fallback-on-body="true"
            :data-category-id="category.info.id"
            :empty-insert-threshold="80"
            @end="onLinkDndEnd"
          >
            <template #item="{ element: link }">
              <li class="mb-3">
                <LinkItem
                  :isLinkValid="linkValidationResults[link.iid]"
                  :link="link"
                  :can-edit="canEditLinks"
                  @check="() => checkLink(link.iid, link.url)"
                  @delete="confirmDeleteLink"
                  @edit="editLink"
                  @toggle="toggleVisibility"
                />
              </li>
            </template>
          </Draggable>

          <p
            v-if="!category.links || category.links.length === 0"
            class="text-sm text-gray-600"
          >
            {{ t("There are no links in this category") }}
          </p>
        </LinkCategoryCard>
      </div>

      <!-- Optional: Category reorder mode (keep your old Draggable wrapper if you enable it later) -->
      <Draggable
        v-else
        tag="div"
        class="flex flex-col gap-4"
        :list="categories"
        item-key="key"
        :disabled="!canEditLinks || !canMoveCategories"
        :animation="150"
        @change="onCategoryDndChange"
      >
        <template #item="{ element: category }">
          <LinkCategoryCard :showHeader="true">
            <template #header>
              <div class="flex justify-between gap-4">
                <div class="flex items-center">
                  <BaseIcon
                    class="mr-2"
                    icon="folder-generic"
                    size="normal"
                  />
                  <h5>{{ category.info.title }}</h5>
                </div>

                <div
                  v-if="securityStore.isAuthenticated && canEditLinks"
                  class="flex items-center gap-3 text-gray-700"
                >
                  <BaseIcon
                    icon="edit"
                    size="normal"
                    :title="t('Edit')"
                    class="hover:text-black"
                    @click="editCategory(category)"
                  />
                  <BaseIcon
                    :icon="isVisible(category.info.visible) ? 'eye-on' : 'eye-off'"
                    size="normal"
                    :title="t('Change visibility')"
                    class="hover:text-black"
                    @click="toggleCategoryVisibility(category)"
                  />
                  <BaseIcon
                    icon="delete"
                    size="normal"
                    :title="t('Delete')"
                    class="hover:text-black"
                    @click="confirmDeleteCategory(category)"
                  />
                </div>
              </div>

              <p v-if="category.info.description">{{ category.info.description }}</p>
            </template>

            <Draggable
              tag="ul"
              class="min-h-[48px] py-2"
              :list="category.links"
              item-key="iid"
              :disabled="!canEditLinks || isMoving"
              :group="{ name: 'links', pull: true, put: true }"
              :animation="150"
              handle=".link-drag-handle"
              ghost-class="link-ghost"
              chosen-class="link-chosen"
              drag-class="link-drag"
              :force-fallback="true"
              :fallback-on-body="true"
              :data-category-id="category.info.id"
              :empty-insert-threshold="80"
              @end="onLinkDndEnd"
            >
              <template #item="{ element: link }">
                <li class="mb-3">
                  <LinkItem
                    :isLinkValid="linkValidationResults[link.iid]"
                    :link="link"
                    :can-edit="canEditLinks"
                    @check="() => checkLink(link.iid, link.url)"
                    @delete="confirmDeleteLink"
                    @edit="editLink"
                    @toggle="toggleVisibility"
                  />
                </li>
              </template>
            </Draggable>

            <p
              v-if="!category.links || category.links.length === 0"
              class="text-sm text-gray-600"
            >
              {{ t("There are no links in this category") }}
            </p>
          </LinkCategoryCard>
        </template>
      </Draggable>
    </div>

    <BaseDialogDelete
      v-model:is-visible="isDeleteLinkDialogVisible"
      :item-to-delete="linkToDeleteString"
      @confirm-clicked="deleteLink"
      @cancel-clicked="isDeleteLinkDialogVisible = false"
    />
    <BaseDialogDelete
      v-model:is-visible="isDeleteCategoryDialogVisible"
      @confirm-clicked="deleteCategory"
      @cancel-clicked="isDeleteCategoryDialogVisible = false"
    >
      <div v-if="categoryToDelete">
        <p class="mb-2 font-semibold">{{ categoryToDelete.info.title }}</p>
        <p>
          {{ t("With links") }}:
          {{ (categoryToDelete.links || []).map((l) => l.title).join(", ") }}
        </p>
      </div>
    </BaseDialogDelete>
  </div>
</template>

<script setup>
import EmptyState from "../../components/EmptyState.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import LinkItem from "../../components/links/LinkItem.vue"
import LinkCategoryCard from "../../components/links/LinkCategoryCard.vue"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"

import Skeleton from "primevue/skeleton"
import Draggable from "vuedraggable"

import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"

import linkService from "../../services/linkService"
import { useNotification } from "../../composables/notification"
import { isVisible, toggleVisibilityProperty, visibilityFromBoolean } from "../../components/links/linkVisibility"
import { useSecurityStore } from "../../store/securityStore"
import { useCidReq } from "../../composables/cidReq"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { usePlatformConfig } from "../../store/platformConfig"

const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const platform = usePlatformConfig()
const { cid, sid } = useCidReq()

const { t } = useI18n()
const notifications = useNotification()

const linksWithoutCategory = ref([])
const categories = ref([])

const isDeleteLinkDialogVisible = ref(false)
const linkToDelete = ref(null)
const linkToDeleteString = computed(() => (linkToDelete.value ? linkToDelete.value.title : ""))

const isDeleteCategoryDialogVisible = ref(false)
const categoryToDelete = ref(null)

const isLoading = ref(true)
const linkValidationResults = ref({})
const isToggling = ref({})
const isMoving = ref(false)

const isAllowedToEdit = ref(securityStore.isAdmin || securityStore.isCurrentTeacher)
const canEditLinks = computed(() => {
  if (platform.isStudentViewActive) return false
  return Boolean(isAllowedToEdit.value || securityStore.isAdmin || securityStore.isCurrentTeacher)
})

const canMoveCategories = false
const parentId = computed(() => Number(route.query.parent || 0))
const cidValue = computed(() => Number(route.query.cid || (cid && typeof cid === "object" ? cid.value : cid) || 0))
const sidValue = computed(() => Number(route.query.sid || (sid && typeof sid === "object" ? sid.value : sid) || 0))
const showGeneralCard = computed(() => {
  if (linksWithoutCategory.value.length > 0) return true
  if (categories.value.length > 0) return true
  return Boolean(canEditLinks.value)
})

onMounted(async () => {
  await reconcileEditGate()
  await fetchLinks()
})

watch(() => platform.isStudentViewActive, refreshForViewToggle)

async function reconcileEditGate() {
  try {
    const allowed = await checkIsAllowedToEdit(true, true, true)
    isAllowedToEdit.value = Boolean(allowed || securityStore.isAdmin || securityStore.isCurrentTeacher)
  } catch (error) {
    console.error("Error checking edit permission:", error)
    isAllowedToEdit.value = Boolean(securityStore.isAdmin || securityStore.isCurrentTeacher)
  }
}

async function refreshForViewToggle() {
  await reconcileEditGate()
  await fetchLinks()
}

async function fetchLinks() {
  isLoading.value = true

  const params = {
    "resourceNode.parent": parentId.value || null,
    cid: cidValue.value || null,
    sid: sidValue.value || null,
  }

  try {
    const data = await linkService.getLinks(params)
    linksWithoutCategory.value = data.linksWithoutCategory || []
    categories.value = Object.values(data.categories || {}).map((c) => ({ ...c, key: c.info.id }))
  } catch (error) {
    console.error("Error fetching links:", error)
    notifications.showErrorNotification(t("Could not retrieve links"))
  } finally {
    isLoading.value = false
  }
}

/**
 * Backend-generated PDF export.
 */
async function exportToPDF() {
  if (!canEditLinks.value) return

  try {
    const response = await linkService.exportLinks("pdf", { cid: cidValue.value, sid: sidValue.value })

    const blob = new Blob([response.data], { type: "application/pdf" })
    const url = window.URL.createObjectURL(blob)

    let filename = "links.pdf"
    const contentDisposition = response.headers?.["content-disposition"] || response.headers?.["Content-Disposition"]
    if (contentDisposition) {
      const match = /filename="([^"]+)"/.exec(contentDisposition)
      if (match && match[1]) filename = match[1]
    }

    const a = document.createElement("a")
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)

    notifications.showSuccessNotification(t("Export completed"))
  } catch (error) {
    console.error("Error exporting links:", error)
    notifications.showErrorNotification(t("Could not export"))
  }
}

/**
 * Robust DnD save: use Sortable's "end" event.
 * It reliably gives from/to + oldIndex/newIndex.
 * We also attach data-category-id to each list container.
 */
async function onLinkDndEnd(evt) {
  if (!canEditLinks.value) return
  if (isMoving.value) return

  const newIndex = typeof evt?.newIndex === "number" ? evt.newIndex : null
  const oldIndex = typeof evt?.oldIndex === "number" ? evt.oldIndex : null
  if (newIndex === null || oldIndex === null) {
    // Drop outside / cancelled -> nothing to persist
    return
  }

  const fromCategoryId = getCategoryIdFromContainer(evt.from)
  const toCategoryId = getCategoryIdFromContainer(evt.to)

  // No actual move
  if (fromCategoryId === toCategoryId && newIndex === oldIndex) {
    return
  }

  const link = getDraggedLinkFromEvent(evt)
  if (!link || !link.iid) {
    console.error("DnD end event has no underlying link element.")
    await fetchLinks()
    return
  }

  isMoving.value = true
  try {
    await linkService.moveLink(link.iid, newIndex, {
      categoryId: toCategoryId,
      cid: cidValue.value,
      sid: sidValue.value,
    })

    notifications.showSuccessNotification(t("Link moved"))
    await fetchLinks()
  } catch (error) {
    console.error("Error moving link:", error)
    notifications.showErrorNotification(t("Could not move link"))
    await fetchLinks()
  } finally {
    isMoving.value = false
  }
}

function getCategoryIdFromContainer(container) {
  if (!container) return 0
  const raw = container.getAttribute?.("data-category-id") ?? container.dataset?.categoryId ?? "0"
  const n = Number(raw)
  return Number.isFinite(n) ? n : 0
}

/**
 * vuedraggable stores the underlying element on the DOM node.
 * We read it in a defensive way to support different builds.
 */
function getDraggedLinkFromEvent(evt) {
  const item = evt?.item
  if (!item) return null

  // vuedraggable 4
  if (item.__draggable_context && item.__draggable_context.element) {
    return item.__draggable_context.element
  }

  // fallback
  if (item._underlying_vm_) {
    return item._underlying_vm_
  }

  return null
}

/**
 * Optional category reorder (only if you add move endpoint + enable canMoveCategories).
 */
async function onCategoryDndChange(evt) {
  if (!canEditLinks.value || !canMoveCategories) return
  if (!evt.moved) return
  notifications.showErrorNotification(t("Category reordering is not enabled"))
}

/* --- actions --- */
function editLink(link) {
  if (!canEditLinks.value) return
  router.push({ name: "UpdateLink", params: { id: link.iid }, query: route.query })
}

function confirmDeleteLink(link) {
  if (!canEditLinks.value) return
  linkToDelete.value = link
  isDeleteLinkDialogVisible.value = true
}

async function deleteLink() {
  if (!canEditLinks.value || !linkToDelete.value) return

  try {
    await linkService.deleteLink(linkToDelete.value.iid)
    linkToDelete.value = null
    isDeleteLinkDialogVisible.value = false
    notifications.showSuccessNotification(t("Link deleted"))
    await fetchLinks()
  } catch (error) {
    console.error("Error deleting link:", error)
    notifications.showErrorNotification(t("Could not delete link"))
  }
}

async function checkLink(id, url) {
  try {
    const result = await linkService.checkLink(url, id)
    linkValidationResults.value = { ...linkValidationResults.value, [id]: { isValid: result.isValid } }
  } catch (error) {
    console.error("Error checking link:", error)
    linkValidationResults.value = {
      ...linkValidationResults.value,
      [id]: { isValid: false, message: error.message || "Link validation failed" },
    }
  }
}

async function toggleVisibility(link) {
  if (!canEditLinks.value) return
  if (isToggling.value[link.iid]) return
  isToggling.value = { ...isToggling.value, [link.iid]: true }

  try {
    const newVisible = !isVisible(link.linkVisible)
    const updatedLink = await linkService.toggleLinkVisibility(link.iid, newVisible, cidValue.value, sidValue.value)
    const newFlagValue = visibilityFromBoolean(updatedLink.linkVisible)

    linksWithoutCategory.value.filter((l) => l.iid === link.iid).forEach((l) => (l.linkVisible = newFlagValue))

    categories.value
      .flatMap((c) => c.links || [])
      .filter((l) => l.iid === link.iid)
      .forEach((l) => (l.linkVisible = newFlagValue))

    notifications.showSuccessNotification(t("Link visibility updated"))
  } catch (error) {
    console.error("Error toggling link visibility:", error)
    notifications.showErrorNotification(t("Could not change visibility of link"))
  } finally {
    isToggling.value = { ...isToggling.value, [link.iid]: false }
  }
}

function redirectToCreateLink() {
  if (!canEditLinks.value) return
  router.push({ name: "CreateLink", query: route.query })
}

function redirectToCreateLinkCategory() {
  if (!canEditLinks.value) return
  router.push({ name: "CreateLinkCategory", query: route.query })
}

function editCategory(category) {
  if (!canEditLinks.value) return
  router.push({ name: "UpdateLinkCategory", params: { id: category.info.id }, query: route.query })
}

function confirmDeleteCategory(category) {
  if (!canEditLinks.value) return
  categoryToDelete.value = category
  isDeleteCategoryDialogVisible.value = true
}

async function deleteCategory() {
  if (!canEditLinks.value || !categoryToDelete.value) return

  try {
    await linkService.deleteCategory(categoryToDelete.value.info.id)
    categoryToDelete.value = null
    isDeleteCategoryDialogVisible.value = false
    notifications.showSuccessNotification(t("Category deleted"))
    await fetchLinks()
  } catch (error) {
    console.error("Error deleting category:", error)
    notifications.showErrorNotification(t("Could not delete category"))
  }
}

async function toggleCategoryVisibility(category) {
  if (!canEditLinks.value) return

  const visibility = toggleVisibilityProperty(category.info.visible)
  try {
    const updated = await linkService.toggleCategoryVisibility(
      category.info.id,
      isVisible(visibility),
      cidValue.value,
      sidValue.value,
    )
    category.info.visible = visibilityFromBoolean(updated.linkCategoryVisible)
    notifications.showSuccessNotification(t("Visibility of category changed"))
  } catch (error) {
    console.error("Error updating category visibility:", error)
    notifications.showErrorNotification(t("Could not change visibility of category"))
  }
}
</script>
<style>
/**
 * SortableJS requires single-token class names (no spaces).
 */
.link-ghost {
  opacity: 0.45;
}

.link-chosen {
  outline: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 6px;
}

.link-drag {
  opacity: 0.85;
}
</style>
