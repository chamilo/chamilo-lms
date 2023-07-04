<template>
  <div>
    <ButtonToolbar v-if="isAuthenticated && isCurrentTeacher">
      <BaseButton
        :label="t('Add a link')"
        icon="link-add"
        class="mr-2 mb-2"
        type="black"
        @click="redirectToCreateLink"
      />
      <BaseButton
        :label="t('Add a category')"
        icon="folder-plus"
        class="mr-2 mb-2"
        type="black"
        @click="redirectToCreateLinkCategory"
      />
      <BaseButton
        :label="t('Export to PDF')"
        icon="file-pdf"
        class="mr-2 mb-2"
        type="black"
        @click="exportToPDF"
      />
      <BaseButton
        :label="t('Switch to student view')"
        icon="eye-on"
        class="mr-2 mb-2"
        type="black"
        @click="toggleTeacherStudent"
      />
    </ButtonToolbar>

    <div v-if="!linksWithoutCategory && !categories">
      <!-- Render the image and create button -->
      <EmptyState
        icon="mdi mdi-link"
        :summary="t('Add your first link to this course')"
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

    <div class="flex flex-col gap-4">
      <!-- Render the list of links -->
      <LinkCategoryCard v-if="linksWithoutCategory && linksWithoutCategory.length > 0">
        <template #header>
          <h5>{{ t('General') }}</h5>
        </template>

        <ul>
          <li v-for="link in linksWithoutCategory" :key="link.id" class="mb-4">
            <LinkItem
              :link="link"
              @check="checkLink(link.iid, link.url)"
              @edit="editLink"
              @toggle="toggleVisibility"
              @move-up="moveUp(link.iid, link.position)"
              @move-down="moveDown(link.iid, link.position)"
              @delete="deleteLink(link.iid)"
            />
          </li>
        </ul>
      </LinkCategoryCard>

      <LinkCategoryCard v-for="category in categories" :key="category.info.id">

        <template #header>
          <div class="flex justify-between">
            <div class="flex items-center">
              <BaseIcon class="mr-2" icon="folder-generic" size="big"/>
              <h5>{{ category.info.name }}</h5>
            </div>
            <div class="flex gap-2">
              <BaseButton
                :label="t('Edit')"
                type="black"
                icon="edit"
                size="small"
                @click="editCategory(category)"
              />
              <BaseButton
                :label="t('Change visibility')"
                type="black"
                :icon="category.info.visible ? 'eye-on' : 'eye-off'"
                size="small"
                @click="toggleCategoryVisibility(category)"
              />
              <BaseButton
                :label="t('Delete')"
                type="black"
                icon="delete"
                size="small"
                @click="deleteCategory(category)"
              />
            </div>
          </div>
          <p v-if="category.info.description">{{ category.info.description }}</p>
        </template>

        <ul>
          <li v-for="link in category.links" :key="link.id">
            <LinkItem
              :link="link"
              @check="checkLink(link.iid, link.url)"
              @edit="editLink"
              @toggle="toggleVisibility"
              @move-up="moveUp(link.iid, link.position)"
              @move-down="moveDown(link.iid, link.position)"
              @delete="deleteLink(link.iid)"
            />
          </li>
        </ul>
        <p v-if="!category.links || category.links === 0">
          {{ t('There are no links in this category') }}
        </p>
      </LinkCategoryCard>
    </div>
  </div>
</template>

<script setup>
import EmptyState from "../../components/EmptyState.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue";
import {computed, onMounted, ref} from "vue";
import {useStore} from "vuex";
import {useRoute, useRouter} from "vue-router";
import {useI18n} from "vue-i18n";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import LinkItem from "../../components/links/LinkItem.vue";
import {useNotification} from "../../composables/notification";
import LinkCategoryCard from "../../components/links/LinkCategoryCard.vue";
import linkService from "../../services/linkService";

const store = useStore();
const route = useRoute();
const router = useRouter();

const {t} = useI18n();

const notifications = useNotification()

const isAuthenticated = computed(() => store.getters["security/isAuthenticated"]);
const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);

const linksWithoutCategory = ref([]);
const categories = ref([]);

const selectedLink = ref(null);
const selectedCategory = ref(null);

onMounted(() => {
  linksWithoutCategory.value = [];
  categories.value = [];
  fetchLinks()
});


function editLink(link) {
  selectedLink.value = {...link};
  router.push({
    name: "UpdateLink",
    params: {id: link.iid},
    query: route.query,
  });
}

async function deleteLink(id) {
  try {
    await linkService.deleteLink(id)
    notifications.showSuccessNotification(t('Link deleted'))
    fetchLinks()
  } catch (error) {
    console.error('Error deleting link:', error);
    notifications.showErrorNotification(t('Could not delete link'))
  }
}

function checkLink(id, url) {
  // Implement the logic to check the link using the provided id and url
}

async function toggleVisibility(link) {
  try {
    const makeVisible = !link.visible;
    await linkService.toggleLinkVisibility(link.iid, makeVisible)
    notifications.showSuccessNotification(t('Link visibility updated'))
    fetchLinks()
    linksWithoutCategory.value.forEach((item) => {
      if (item.iid === link.iid) {
        item.linkVisible = !item.linkVisible;
      }
    })
  } catch (error) {
    console.error('Error deleting link:', error);
    notifications.showErrorNotification(t('Could not change visibility of link'))
  }
}

async function moveUp(id, position) {
  let newPosition = parseInt(position) - 1;
  if (newPosition < 0) {
    newPosition = 0;
  }
  try {
    await linkService.moveLink(id, newPosition)
    notifications.showSuccessNotification(t('Link moved up'))
    fetchLinks()
  } catch (error) {
    console.error("Error moving link up:", error);
    notifications.showErrorNotification(t('Could not moved link up'))
  }
}

async function moveDown(id, position) {
  const newPosition = parseInt(position) + 1;
  try {
    await linkService.moveLink(id, newPosition)
    notifications.showSuccessNotification(t('Link moved down'))
    fetchLinks()
  } catch (error) {
    console.error("Error moving link down:", error);
    notifications.showErrorNotification(t('Could not moved link down'))
  }
}

function redirectToCreateLink() {
  router.push({
    name: "CreateLink",
    query: route.query,
  });
}

function redirectToCreateLinkCategory() {
  router.push({
    name: "CreateLinkCategory",
    query: route.query,
  });
}

function editCategory(category) {
  selectedCategory.value = {...category};
  router.push({
    name: "UpdateLinkCategory",
    params: {id: category.info.id},
    query: route.query,
  });
}

async function deleteCategory(category) {
  try {
    await linkService.deleteCategory(category.info.id)
    notifications.showSuccessNotification(t('Category deleted'))
    fetchLinks()
  } catch (error) {
    console.error('Error deleting category:', error);
    notifications.showErrorNotification(t('Could not delete category'))
  }
}

async function toggleCategoryVisibility(category) {
  const makeVisible = !category.info.visible
  try {
    const updatedLinkCategory = await linkService.toggleCategoryVisibility(category.info.id, makeVisible)
    category.info.visible = updatedLinkCategory.linkCategoryVisible;
    notifications.showSuccessNotification(t('Visibility of category changed'))
  } catch (error) {
    console.error('Error updating link visibility:', error)
    notifications.showErrorNotification(t('Could not change visibility of category'))
  }
}

function exportToPDF() {
  // TODO
}

function toggleTeacherStudent() {
  // TODO
}

async function fetchLinks() {
  const params = {
    'resourceNode.parent': route.query.parent || null,
    'cid': route.query.cid || null,
    'sid': route.query.sid || null
  };

  try {
    const data = await linkService.getLinks(params)
    linksWithoutCategory.value = data.linksWithoutCategory;
    categories.value = data.categories;
  } catch (error) {
    console.error('Error fetching links:', error);
    notifications.showErrorNotification(t('Could not retrieve links'))
  }
}
</script>
