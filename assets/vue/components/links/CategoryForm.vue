<template>
  <div>
    <div>
      <form @submit.prevent="submitCategoryForm">
        <div class="field">
          <div class="p-float-label">
            <input v-model="formData.title" type="text" id="category_category_title" class="p-inputtext p-component p-filled" />
            <label for="category_category_title">
              <span class="form_required">*</span>
              Category name
            </label>
          </div>
        </div>
        <div class="field">
          <div class="p-float-label">
            <textarea v-model="formData.description" id="description" name="description"></textarea>
            <label for="description">
              Description
            </label>
          </div>
        </div>
        <div class="field 2">
          <div class="8">
            <label for="category_submitCategory" class="h-4"></label>
            <button class="btn btn--primary" name="submitCategory" type="submit" id="category_submitCategory">
              <em class="mdi mdi-check"></em> Add a category
            </button>
          </div>
        </div>
        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <span class="form_required">*</span>
            <small>Required field</small>
          </div>
        </div>
      </form>
    </div>

  </div>
</template>

<script setup>
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import { RESOURCE_LINK_PUBLISHED } from "../resource_links/visibility";
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from "vue-i18n";
import { ref, onMounted } from "vue";
import linkService from "../../services/linkService";


const route = useRoute();
const router = useRouter();
const { t } = useI18n();


const props = defineProps({
  categoryId: {
    type: Number,
    default: null
  }
})

const parentResourceNodeId = ref(Number(route.params.node));

const resourceLinkList = ref(
  JSON.stringify([
    {
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    },
  ])
);

const formData = ref({
  title: '',
  description: '',
});

const fetchCategory = () => {
  if (props.categoryId) {
    axios.get(ENTRYPOINT + 'link_categories/' + props.categoryId)
      .then(response => {
        const category = response.data;
        formData.value.title = category.categoryTitle;
        formData.value.description = category.description;
      })
      .catch(error => {
        console.error('Error fetching link:', error);
      });
  }
};

onMounted(() => {
  fetchCategory();
});

const submitCategoryForm = async () => {
  const postData = {
    category_title: formData.value.title,
    description: formData.value.description,
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  }

  try {
    if (props.categoryId) {
      await linkService.updateCategory(props.categoryId, postData)
    } else {
      await linkService.createCategory(postData)
    }

    await router.push({
      name: "LinksList",
      query: route.query,
    })
  } catch (error) {
    console.error('Error updating link:', error)
  }
}
</script>
