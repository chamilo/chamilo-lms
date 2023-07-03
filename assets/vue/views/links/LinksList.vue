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
          <div class="flex justify-between mb-2">
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
                :label="t('Delete')"
                type="black"
                :icon="category.info.visible ? 'eye-on' : 'eye-off'"
                size="small"
                @click="deleteCategory(category)"
              />
              <BaseButton
                :label="t('Delete')"
                type="danger"
                icon="delete"
                size="small"
                @click="toggleCategoryVisibility(category)"
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
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import LinkItem from "../../components/links/LinkItem.vue";
import {useNotification} from "../../composables/notification";
import LinkCategoryCard from "../../components/links/LinkCategoryCard.vue";

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

function editLink(link) {
  selectedLink.value = {...link};
  router.push({
    name: "UpdateLink",
    params: {id: link.iid},
    query: route.query,
  });
}

function deleteLink(id) {
  axios
    .delete(`${ENTRYPOINT}links/${id}`)
    .then(response => {
      console.log('Link deleted:', response.data);
      fetchLinks();
    })
    .catch(error => {
      console.error('Error deleting link:', error);
    });
}

function checkLink(id, url) {
  // Implement the logic to check the link using the provided id and url
}

function toggleVisibility(link) {
  const makeVisible = !link.visible;

  const endpoint = `${ENTRYPOINT}links/${link.iid}/toggle_visibility`;

  axios
    .put(endpoint, {visible: makeVisible})
    .then(response => {
      const updatedLink = response.data;
      notifications.showSuccessNotification(t('Link visibility updated'))
    })
    .catch(error => {
      console.error('Error updating link visibility:', error);
    });

  linksWithoutCategory.value.forEach((item) => {
    if (item.iid === link.iid) {
      item.linkVisible = !item.linkVisible;
    }
  });
}

function moveUp(id, position) {

  console.log('linkIndex Down position', position);
  // Send the updated position to the server
  const endpoint = `${ENTRYPOINT}links/${id}/move`;
  let newPosition = parseInt(position) - 1;

  if (newPosition < 0) {
    newPosition = 0;
  }

  console.log('linkIndex Up newPosition', newPosition);

  axios
    .put(endpoint, {position: newPosition})
    .then((response) => {
      console.log("Link moved up:", response.data);
      // Perform any additional actions or updates if needed
      fetchLinks();
    })
    .catch((error) => {
      console.error("Error moving link up:", error);
    });

}

function moveDown(id, position) {

  console.log('linkIndex Down position', position);

  // Send the updated position to the server
  const endpoint = `${ENTRYPOINT}links/${id}/move`;
  const newPosition = parseInt(position) + 1;

  console.log('linkIndex Down newPosition', newPosition);

  axios
    .put(endpoint, {position: newPosition})
    .then((response) => {
      console.log("Link moved down:", response.data);
      // Perform any additional actions or updates if needed
      fetchLinks();
    })
    .catch((error) => {
      console.error("Error moving link down:", error);
    });
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

function fetchLinks() {
  const params = {
    'resourceNode.parent': route.query.parent || null,
    'cid': route.query.cid || null,
    'sid': route.query.sid || null
  };

  axios
    .get(ENTRYPOINT + 'links', {params})
    .then(response => {

      console.log('responsedata:', response.data);

      const data = response.data;
      linksWithoutCategory.value = data.linksWithoutCategory;
      categories.value = data.categories;
      console.log('linksWithoutCategory:', linksWithoutCategory.value);
      console.log('categories:', categories.value);
    })
    .catch(error => {
      console.error('Error fetching links:', error);
    });
}

onMounted(() => {
  linksWithoutCategory.value = [];
  categories.value = [];
  fetchLinks();
});

function editCategory(category) {
  console.log('category.info.id', category.info.id);
  selectedCategory.value = {...category};
  router.push({
    name: "UpdateLinkCategory",
    params: {id: category.info.id},
    query: route.query,
  });
}

function deleteCategory(category) {
  axios
    .delete(`${ENTRYPOINT}link_categories/${category.info.id}`)
    .then(response => {
      console.log('Category deleted:', response.data);
      fetchLinks();
    })
    .catch(error => {
      console.error('Error deleting category:', error);
    });
}

function toggleCategoryVisibility(category) {

  const makeVisible = !category.info.visible;
  const endpoint = `${ENTRYPOINT}link_categories/${category.info.id}/toggle_visibility`;

  axios
    .put(endpoint, {visible: makeVisible})
    .then(response => {
      const updatedLinkCategory = response.data;
      console.log('Link visibility updated:', updatedLinkCategory);

      category.info.visible = updatedLinkCategory.linkCategoryVisible;

    })
    .catch(error => {
      console.error('Error updating link visibility:', error);
    });
}

function exportToPDF() {
  // TODO
}
</script>
