<template>
  <div>
    <ButtonToolbar v-if="securityStore.isAuthenticated && isCurrentTeacher">
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
      <StudentViewButton />
    </ButtonToolbar>

    <LinkCategoryCard v-if="isLoading">
      <template #header>
        <Skeleton class="h-6 w-48" />
      </template>
      <div class="flex flex-col gap-4">
        <Skeleton class="ml-2 h-6 w-52" />
        <Skeleton class="ml-2 h-6 w-64" />
        <Skeleton class="ml-2 h-6 w-60" />
        <Skeleton class="ml-2 h-6 w-52" />
        <Skeleton class="ml-2 h-6 w-60" />
      </div>
    </LinkCategoryCard>

    <div v-if="!linksWithoutCategory && !categories">
      <!-- Render the image and create button -->
      <EmptyState
        :summary="t('Add your first link to this course')"
        icon="link"
      >
        <BaseButton
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
      <!-- Render the list of links -->
      <LinkCategoryCard v-if="linksWithoutCategory && linksWithoutCategory.length > 0">
        <template #header>
          <h5>{{ t("General") }}</h5>
        </template>

        <ul>
          <li
            v-for="link in linksWithoutCategory"
            :key="link.id"
            class="mb-4"
          >
            <LinkItem
              :link="link"
              @check="checkLink(link.iid, link.url)"
              @delete="confirmDeleteLink(link)"
              @edit="editLink"
              @toggle="toggleVisibility"
              @move-up="moveUp(link.iid, link.position)"
              @move-down="moveDown(link.iid, link.position)"
            />
          </li>
        </ul>
      </LinkCategoryCard>

      <LinkCategoryCard
        v-for="category in categories"
        :key="category.info.id"
      >
        <template #header>
          <div class="flex justify-between">
            <div class="flex items-center">
              <BaseIcon
                class="mr-2"
                icon="folder-generic"
                size="big"
              />
              <h5>{{ category.info.name }}</h5>
            </div>
            <div class="flex gap-2">
              <BaseButton
                :label="t('Edit')"
                icon="edit"
                size="small"
                type="black"
                @click="editCategory(category)"
              />
              <BaseButton
                :icon="isVisible(category.info.visible) ? 'eye-on' : 'eye-off'"
                :label="t('Change visibility')"
                size="small"
                type="black"
                @click="toggleCategoryVisibility(category)"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                size="small"
                type="danger"
                @click="confirmDeleteCategory(category)"
              />
            </div>
          </div>
          <p v-if="category.info.description">{{ category.info.description }}</p>
        </template>

        <ul>
          <li
            v-for="link in category.links"
            :key="link.id"
          >
            <LinkItem
              :link="link"
              @check="checkLink(link.iid, link.url)"
              @delete="confirmDeleteLink(link)"
              @edit="editLink"
              @toggle="toggleVisibility"
              @move-up="moveUp(link.iid, link.position)"
              @move-down="moveDown(link.iid, link.position)"
            />
          </li>
        </ul>
        <p v-if="!category.links || category.links === 0">
          {{ t("There are no links in this category") }}
        </p>
      </LinkCategoryCard>
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
        <p class="mb-2 font-semibold">{{ categoryToDelete.info.name }}</p>
        <p>{{ t("With links") }}: {{ categoryToDelete.links.map((l) => l.title).join(", ") }}</p>
      </div>
    </BaseDialogDelete>
  </div>
</template>

<script setup>
import EmptyState from "../../components/EmptyState.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import { computed, onMounted, ref } from "vue"
import { useStore } from "vuex"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import LinkItem from "../../components/links/LinkItem.vue"
import { useNotification } from "../../composables/notification"
import LinkCategoryCard from "../../components/links/LinkCategoryCard.vue"
import linkService from "../../services/linkService"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import Skeleton from "primevue/skeleton"
import StudentViewButton from "../../components/StudentViewButton.vue"
import { isVisible, toggleVisibilityProperty, visibilityFromBoolean } from "../../components/links/linkVisibility"
import { useSecurityStore } from "../../store/securityStore"

const store = useStore()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()

const { t } = useI18n()

const notifications = useNotification()

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"])

const linksWithoutCategory = ref([])
const categories = ref({})

const selectedLink = ref(null)
const selectedCategory = ref(null)

const isDeleteLinkDialogVisible = ref(false)
const linkToDelete = ref(null)
const linkToDeleteString = computed(() => {
  if (linkToDelete.value === null) {
    return ""
  }
  return linkToDelete.value.title
})

const isDeleteCategoryDialogVisible = ref(false)
const categoryToDelete = ref(null)

const isLoading = ref(true)

onMounted(() => {
  linksWithoutCategory.value = []
  categories.value = {}
  fetchLinks()
})

function editLink(link) {
  selectedLink.value = { ...link }
  router.push({
    name: "UpdateLink",
    params: { id: link.iid },
    query: route.query,
  })
}

function confirmDeleteLink(link) {
  linkToDelete.value = link
  isDeleteLinkDialogVisible.value = true
}

async function deleteLink() {
  try {
    await linkService.deleteLink(linkToDelete.value.id)
    isDeleteLinkDialogVisible.value = true
    linkToDelete.value = null
    notifications.showSuccessNotification(t("Link deleted"))
    await fetchLinks()
  } catch (error) {
    console.error("Error deleting link:", error)
    notifications.showErrorNotification(t("Could not delete link"))
  }
}

function checkLink(id, url) {
  // Implement the logic to check the link using the provided id and url
}

async function toggleVisibility(link) {
  try {
    const visibility = toggleVisibilityProperty(!link.linkVisible)
    let newLink = await linkService.toggleLinkVisibility(link.iid, isVisible(visibility))
    notifications.showSuccessNotification(t("Link visibility updated"))
    linksWithoutCategory.value
      .filter((l) => l.iid === link.iid)
      .forEach((l) => (l.linkVisible = visibilityFromBoolean(newLink.linkVisible)))
    Object.values(categories.value)
      .map((c) => c.links)
      .flat()
      .filter((l) => l.iid === link.iid)
      .forEach((l) => (l.linkVisible = visibilityFromBoolean(newLink.linkVisible)))
  } catch (error) {
    console.error("Error deleting link:", error)
    notifications.showErrorNotification(t("Could not change visibility of link"))
  }
}

async function moveUp(id, position) {
  let newPosition = parseInt(position) - 1
  if (newPosition < 0) {
    newPosition = 0
  }
  try {
    await linkService.moveLink(id, newPosition)
    notifications.showSuccessNotification(t("Link moved up"))
    await fetchLinks()
  } catch (error) {
    console.error("Error moving link up:", error)
    notifications.showErrorNotification(t("Could not moved link up"))
  }
}

async function moveDown(id, position) {
  const newPosition = parseInt(position) + 1
  try {
    await linkService.moveLink(id, newPosition)
    notifications.showSuccessNotification(t("Link moved down"))
    await fetchLinks()
  } catch (error) {
    console.error("Error moving link down:", error)
    notifications.showErrorNotification(t("Could not moved link down"))
  }
}

function redirectToCreateLink() {
  router.push({
    name: "CreateLink",
    query: route.query,
  })
}

function redirectToCreateLinkCategory() {
  router.push({
    name: "CreateLinkCategory",
    query: route.query,
  })
}

function editCategory(category) {
  selectedCategory.value = { ...category }
  router.push({
    name: "UpdateLinkCategory",
    params: { id: category.info.id },
    query: route.query,
  })
}

function confirmDeleteCategory(category) {
  categoryToDelete.value = category
  isDeleteCategoryDialogVisible.value = true
}

async function deleteCategory() {
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
  const visibility = toggleVisibilityProperty(category.info.visible)
  try {
    const updatedLinkCategory = await linkService.toggleCategoryVisibility(category.info.id, isVisible(visibility))
    category.info.visible = visibilityFromBoolean(updatedLinkCategory.linkCategoryVisible)
    notifications.showSuccessNotification(t("Visibility of category changed"))
  } catch (error) {
    console.error("Error updating link visibility:", error)
    notifications.showErrorNotification(t("Could not change visibility of category"))
  }
}

function exportToPDF() {
  // TODO
}

function toggleTeacherStudent() {
  // TODO
}

async function fetchLinks() {
  isLoading.value = true
  const params = {
    "resourceNode.parent": route.query.parent || null,
    cid: route.query.cid || null,
    sid: route.query.sid || null,
  }

  try {
    const data = await linkService.getLinks(params)
    linksWithoutCategory.value = data.linksWithoutCategory
    categories.value = data.categories
  } catch (error) {
    console.error("Error fetching links:", error)
    notifications.showErrorNotification(t("Could not retrieve links"))
  } finally {
    isLoading.value = false
  }
}
</script>