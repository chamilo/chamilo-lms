<template>
  <div>
    <ButtonToolbar v-if="isAuthenticated && isCurrentTeacher">
      <BaseButton
        label="Add a link"
        icon="new_link"
        class="mr-2 mb-2"
        type="black"
        @click="redirectToCreateLink"
      />
      <BaseButton
        label="Add a category"
        icon="new_folder"
        class="mr-2 mb-2"
        type="black"
        @click="redirectToCreateLinkCategory"
      />
      <BaseButton
        label="Export to PDF"
        icon="pdf"
        class="mr-2 mb-2"
        type="black"
        @click="exportToPDF"
      />
    </ButtonToolbar>

    <div v-if="!linksWithoutCategory && !categories">
      <!-- Render the image and create button -->
      <EmptyState
        icon="mdi mdi-link"
        summary="Add your first link to this course"
      >
        <BaseButton
          label="Create Link"
          class="mt-4"
          icon="plus"
          type="primary"
          @click="redirectToCreateLink"
        />
      </EmptyState>
    </div>

    <div>
      <!-- Render the list of links -->
      <ul>
        <li v-if="linksWithoutCategory && linksWithoutCategory.length > 0">
          <h3>General</h3>
          <ul>
            <li v-for="link in linksWithoutCategory" :key="link.id">
              <span>{{ link.title }}</span>
              <span>{{ link.url }}</span>
              <div>
                <a @click="checkLink(link.iid, link.url)" title="Check link" class="btn btn--secondary btn-sm">
                  <i class="mdi-check-circle mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                </a>
                <a @click="editLink(link)" title="Edit" class="btn btn--secondary btn-sm">
                  <i class="mdi-pencil mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                </a>
                <a @click="toggleVisibility(link)" title="Toggle visibility" class="btn btn--secondary btn-sm">
                  <i :class="link.linkVisible ? 'mdi-eye mdi' : 'mdi-eye-off mdi'" v-icon="true" aria-hidden="true"></i>
                </a>
                <a @click="moveUp(link.iid, link.position)" class="btn btn--secondary btn-sm disabled" title="Move up">
                  <i class="mdi-level-up-alt mdi v-icon notranslate v-icon--size-default"></i>
                  <span class="sr-only">Move up</span>
                </a>
                <a @click="moveDown(link.iid, link.position)" class="btn btn--secondary btn-sm" title="Move down">
                  <i class="mdi-level-down-alt mdi v-icon notranslate v-icon--size-default"></i>
                  <span class="sr-only">Move down</span>
                </a>
                <a @click="deleteLink(link.iid)" title="Delete" class="btn btn--secondary btn-sm">
                  <i class="mdi-delete mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                </a>
              </div>
            </li>
          </ul>
        </li>
        <li v-for="category in categories" :key="category.info.id">
          <div class="category-header">
            <h3>{{ category.info.name }}</h3>
            <div>
              <a @click="editCategory(category)" title="Edit" class="btn btn--secondary btn-sm">
                <i class="mdi-pencil mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
              </a>
              <a @click="deleteCategory(category)" title="Delete" class="btn btn--secondary btn-sm">
                <i class="mdi-delete mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
              </a>
              <a @click="toggleCategoryVisibility(category)" title="Toggle visibility" class="btn btn--secondary btn-sm">
                <i :class="category.info.visible ? 'mdi-eye mdi' : 'mdi-eye-off mdi'" v-icon="true" aria-hidden="true"></i>
              </a>
            </div>
          </div>
          <ul>
            <li v-for="link in category.links" :key="link.id">
              <span>{{ link.title }}</span>
              <span>{{ link.url }}</span>
              <div>
                <a @click="checkLink(link.iid, link.url)" title="Check link" class="btn btn--secondary btn-sm">
                  <i class="mdi-check-circle mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                </a>
                <a @click="editLink(link)" title="Edit" class="btn btn--secondary btn-sm">
                  <i class="mdi-pencil mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                </a>
                <a @click="toggleVisibility(link)" title="Toggle visibility" class="btn btn--secondary btn-sm">
                  <i :class="link.linkVisible ? 'mdi-eye mdi' : 'mdi-eye-off mdi'" v-icon="true" aria-hidden="true"></i>
                </a>
                <a @click="moveUp(link.iid, link.position)" class="btn btn--secondary btn-sm disabled" title="Move up">
                  <i class="mdi mdi-arrow-up-bold v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                  <span class="sr-only">Move up</span>
                </a>
                <a @click="moveDown(link.iid, link.position)" class="btn btn--secondary btn-sm" title="Move down">
                  <i class="mdi mdi-arrow-down-bold v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                  <span class="sr-only">Move down</span>
                </a>
                <a @click="deleteLink(link.iid)" title="Delete" class="btn btn--secondary btn-sm">
                  <i class="mdi-delete mdi v-icon notranslate v-icon--size-default" aria-hidden="true"></i>
                </a>
              </div>
            </li>
          </ul>
        </li>
      </ul>

    </div>
  </div>
</template>

<script setup>
import EmptyState from "../../components/EmptyState.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue";
import { computed, onMounted, ref } from "vue";
import { useStore } from "vuex";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";

const store = useStore();
const route = useRoute();
const router = useRouter();

const { t } = useI18n();

const isAuthenticated = computed(() => store.getters["security/isAuthenticated"]);
const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);

const linksWithoutCategory = ref([]);
const categories = ref([]);

const selectedLink = ref(null);
const selectedCategory = ref(null);

function editLink(link) {
  selectedLink.value = { ...link };
  router.push({
    name: "UpdateLink",
    params: { id: link.iid },
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
    .put(endpoint, { visible: makeVisible })
    .then(response => {
      const updatedLink = response.data;
      console.log('Link visibility updated:', updatedLink.linkVisible);
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
    .put(endpoint, { position: newPosition })
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
    .put(endpoint, { position: newPosition })
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
    .get(ENTRYPOINT + 'links', { params })
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
  selectedCategory.value = { ...category };
  router.push({
    name: "UpdateLinkCategory",
    params: { id: category.info.id },
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
    .put(endpoint, { visible: makeVisible })
    .then(response => {
      const updatedLinkCategory = response.data;
      console.log('Link visibility updated:', updatedLinkCategory);

      category.info.visible = updatedLinkCategory.linkCategoryVisible;

    })
    .catch(error => {
      console.error('Error updating link visibility:', error);
    });
}

</script>
<style scoped>
.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
</style>
