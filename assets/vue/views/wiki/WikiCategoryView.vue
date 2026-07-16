<template>
  <section class="space-y-6">
    <BaseToolbar class="border-b border-gray-25 bg-white">
      <template #start>
        <div class="flex items-center gap-2">
          <BaseButton
            icon="home"
            :label="t('Home')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getPageRoute('index')"
          />
          <BaseButton
            icon="list"
            :label="t('All pages')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getReportRoute('all')"
          />
          <BaseButton
            icon="settings"
            :label="t('Wiki settings')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getSettingsRoute()"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div
        v-if="successMessage"
        class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
        role="status"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="!categoryData.enabled"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{ t("Wiki categories are disabled for this course.") }}
      </div>

      <template v-else>
        <BaseCard>
          <template #title>
            <div class="flex items-center gap-2">
              <BaseIcon
                :icon="editingCategoryId ? 'edit' : 'folder-plus'"
                size="normal"
              />
              <span>
                {{ editingCategoryId ? t("Edit category") : t("Add a category") }}
              </span>
            </div>
          </template>

          <form class="space-y-5" novalidate @submit.prevent="saveCategory">
            <div
              v-if="formErrorMessage"
              class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
              role="alert"
            >
              {{ formErrorMessage }}
            </div>

            <BaseInputText
              id="wiki_category_title"
              v-model="categoryForm.title"
              :error-text="t('Title is required')"
              :form-submitted="formSubmitted"
              :is-invalid="formSubmitted && !categoryForm.title.trim()"
              :label="t('Title')"
              name="title"
              required
            />

            <BaseSelect
              id="wiki_category_parent"
              v-model="categoryForm.parentId"
              :label="t('Parent category')"
              name="parentId"
              option-label="label"
              option-value="id"
              :options="parentOptions"
            />

            <div class="flex flex-wrap justify-end gap-2">
              <BaseButton
                v-if="editingCategoryId"
                icon="close"
                :label="t('Cancel')"
                type="secondary"
                @click="resetForm"
              />
              <BaseButton
                icon="save"
                :is-loading="isSaving"
                :label="t('Save')"
                name="save_category"
                type="success"
                is-submit
              />
            </div>
          </form>
        </BaseCard>

        <BaseCard>
          <template #title>
            <div class="flex items-center gap-2">
              <BaseIcon icon="folder-generic" size="normal" />
              <span>{{ t("Categories") }}</span>
            </div>
          </template>

          <BaseTable
            :is-loading="isLoading"
            :text-for-empty="t('No categories found')"
            :total-items="categoryData.categories.length"
            :values="categoryData.categories"
            data-key="id"
          >
            <Column :header="t('Category')" field="pathTitle">
              <template #body="slotProps">
                <div
                  class="flex items-start gap-2"
                  :style="getIndentStyle(slotProps.data.level)"
                >
                  <BaseIcon icon="folder-generic" size="small" />
                  <div class="min-w-0">
                    <p class="break-words font-medium text-gray-90">
                      {{ slotProps.data.title }}
                    </p>
                    <p
                      v-if="slotProps.data.parentId"
                      class="break-words text-xs text-gray-500"
                    >
                      {{ slotProps.data.pathTitle }}
                    </p>
                  </div>
                </div>
              </template>
            </Column>

            <Column :header="t('Pages')" field="pageCount">
              <template #body="slotProps">
                {{ Number(slotProps.data.pageCount || 0) }}
              </template>
            </Column>

            <Column :header="t('Subcategories')" field="descendantCount">
              <template #body="slotProps">
                {{ Number(slotProps.data.descendantCount || 0) }}
              </template>
            </Column>

            <Column :exportable="false">
              <template #body="slotProps">
                <div class="flex justify-end gap-1">
                  <BaseButton
                    icon="edit"
                    :label="t('Edit category')"
                    only-icon
                    size="small"
                    type="primary-text"
                    @click="editCategory(slotProps.data)"
                  />
                  <BaseButton
                    icon="delete"
                    :is-loading="isDeleting"
                    :label="t('Delete category')"
                    only-icon
                    size="small"
                    type="danger-text"
                    @click="confirmDeleteCategory(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </BaseTable>
        </BaseCard>
      </template>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import { useConfirmation } from "../../composables/useConfirmation";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseCard from "../../components/basecomponents/BaseCard.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseInputText from "../../components/basecomponents/BaseInputText.vue";
import BaseSelect from "../../components/basecomponents/BaseSelect.vue";
import BaseTable from "../../components/basecomponents/BaseTable.vue";
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue";
import wikiService from "../../services/wikiService";

const { t } = useI18n();
const route = useRoute();
const { requireConfirmation } = useConfirmation();

const isLoading = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const formSubmitted = ref(false);
const editingCategoryId = ref(0);
const errorMessage = ref("");
const formErrorMessage = ref("");
const successMessage = ref("");
const categoryData = reactive(createEmptyCategoryData());
const categoryForm = reactive({ title: "", parentId: 0 });

const parentOptions = computed(() => {
  const options = [{ id: 0, label: t("No parent category") }];
  const current = categoryData.categories.find(
    (category) => Number(category.id) === Number(editingCategoryId.value),
  );
  const currentPath = current?.pathTitle || "";

  for (const category of categoryData.categories) {
    if (Number(category.id) === Number(editingCategoryId.value)) {
      continue;
    }

    if (
      currentPath &&
      String(category.pathTitle).startsWith(`${currentPath} / `)
    ) {
      continue;
    }

    options.push({ id: Number(category.id), label: category.label });
  }

  return options;
});

function createEmptyCategoryData() {
  return {
    enabled: false,
    canManage: false,
    csrfToken: "",
    categories: [],
  };
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value;
}

function getContextParams() {
  const params = {
    cid: Number(getQueryValue(route.query.cid) || 0),
    node: Number(route.params.node || 0),
  };
  const sid = Number(getQueryValue(route.query.sid) || 0);
  const gid = Number(getQueryValue(route.query.gid) || 0);

  if (sid > 0) {
    params.sid = sid;
  }

  if (gid > 0) {
    params.gid = gid;
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView);
  }

  return params;
}

function getSharedQuery() {
  const query = { cid: getQueryValue(route.query.cid) };
  const sid = Number(getQueryValue(route.query.sid) || 0);
  const gid = Number(getQueryValue(route.query.gid) || 0);

  if (sid > 0) {
    query.sid = sid;
  }

  if (gid > 0) {
    query.gid = gid;
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    query.isStudentView = getQueryValue(route.query.isStudentView);
  }

  return query;
}

function getPageRoute(reflink = "index") {
  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query: { ...getSharedQuery(), title: reflink },
  };
}

function getSettingsRoute() {
  return {
    name: "WikiSettings",
    params: { node: route.params.node },
    query: getSharedQuery(),
  };
}

function getReportRoute(report, extraQuery = {}) {
  return {
    name: "WikiReports",
    params: { node: route.params.node },
    query: { ...getSharedQuery(), report, ...extraQuery },
  };
}

function getIndentStyle(level) {
  return { paddingInlineStart: `${Math.max(0, Number(level || 0)) * 1.25}rem` };
}

function editCategory(category) {
  editingCategoryId.value = Number(category.id || 0);
  categoryForm.title = String(category.title || "");
  categoryForm.parentId = Number(category.parentId || 0);
  formSubmitted.value = false;
  formErrorMessage.value = "";
  successMessage.value = "";
}

function resetForm() {
  editingCategoryId.value = 0;
  categoryForm.title = "";
  categoryForm.parentId = 0;
  formSubmitted.value = false;
  formErrorMessage.value = "";
}

async function saveCategory() {
  formSubmitted.value = true;
  formErrorMessage.value = "";
  successMessage.value = "";

  const title = categoryForm.title.trim();
  if (!title) {
    return;
  }

  isSaving.value = true;

  try {
    const payload = {
      title,
      parentId: Number(categoryForm.parentId || 0) || null,
      csrfToken: categoryData.csrfToken,
    };

    if (editingCategoryId.value > 0) {
      await wikiService.updateCategory(
        editingCategoryId.value,
        getContextParams(),
        payload,
      );
      successMessage.value = t("The category has been updated");
    } else {
      await wikiService.createCategory(getContextParams(), payload);
      successMessage.value = t("The category has been created");
    }

    resetForm();
    await loadCategories(false);
  } catch (error) {
    console.error("Error saving Wiki category", error);
    formErrorMessage.value = getErrorMessage(error);
  } finally {
    isSaving.value = false;
  }
}

function confirmDeleteCategory(category) {
  const details = [];
  const descendantCount = Number(category.descendantCount || 0);
  const pageCount = Number(category.pageCount || 0);

  if (descendantCount > 0) {
    details.push(
      t("This will also delete {0} subcategories.", [descendantCount]),
    );
  }

  if (pageCount > 0) {
    details.push(
      t("Category links will be removed from {0} Wiki pages.", [pageCount]),
    );
  }

  requireConfirmation({
    message: [
      t("Are you sure you want to delete this category?"),
      ...details,
    ].join(" "),
    accept: () => deleteCategory(category),
  });
}

async function deleteCategory(category) {
  isDeleting.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    await wikiService.deleteCategory(
      Number(category.id),
      getContextParams(),
      categoryData.csrfToken,
    );
    if (Number(editingCategoryId.value) === Number(category.id)) {
      resetForm();
    }
    successMessage.value = t("The category has been deleted");
    await loadCategories(false);
  } catch (error) {
    console.error("Error deleting Wiki category", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isDeleting.value = false;
  }
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.error ||
    t("An error occurred")
  );
}

async function loadCategories(showLoading = true) {
  if (showLoading) {
    isLoading.value = true;
  }
  errorMessage.value = "";

  try {
    const response = await wikiService.getCategories(getContextParams());
    Object.assign(categoryData, createEmptyCategoryData(), response, {
      categories: Array.isArray(response.categories) ? response.categories : [],
    });
  } catch (error) {
    console.error("Error loading Wiki categories", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadCategories);
</script>
