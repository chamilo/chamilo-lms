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
            :route="getWikiRoute('index')"
          />
          <BaseButton
            icon="back"
            :label="t('Back')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getWikiRoute(discussion.reflink)"
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
        </div>
      </template>

      <template #end>
        <div class="flex items-center gap-2">
          <BaseButton
            v-if="discussion.canManage"
            :icon="discussion.commentsOpen ? 'lock' : 'unlock'"
            :is-loading="isManaging"
            :label="
              discussion.commentsOpen
                ? t('Block comments')
                : t('Allow comments')
            "
            only-icon
            size="large"
            :type="discussion.commentsOpen ? 'danger-text' : 'success-text'"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeCommenting"
          />
          <BaseButton
            v-if="discussion.canManage"
            :icon="discussion.visible ? 'eye-on' : 'eye-off'"
            :is-loading="isManaging"
            :label="
              discussion.visible ? t('Hide discussion') : t('Show discussion')
            "
            only-icon
            size="large"
            type="secondary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeVisibility"
          />
          <BaseButton
            v-if="discussion.canManage"
            icon="tracking"
            :is-loading="isManaging"
            :label="
              discussion.ratingsOpen ? t('Block ratings') : t('Allow ratings')
            "
            only-icon
            size="large"
            :type="discussion.ratingsOpen ? 'danger-text' : 'success-text'"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeRating"
          />
          <BaseButton
            v-if="discussion.canSubscribe"
            icon="notification"
            :is-loading="isManaging"
            :label="
              discussion.subscribed ? t('Stop notifying me') : t('Notify me')
            "
            only-icon
            size="large"
            :type="discussion.subscribed ? 'danger-text' : 'primary-text'"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeSubscription"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ successMessage }}
    </div>

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
        v-if="!discussion.visible && discussion.canManage"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{ t("This discussion is hidden from learners") }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex min-w-0 items-center gap-2">
            <BaseIcon icon="comment" size="small" />
            <h1
              class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90"
            >
              {{ t("Discussion") }}: {{ discussion.title }}
            </h1>
          </div>
        </template>

        <div class="grid gap-4 md:grid-cols-3">
          <div class="rounded-xl border border-gray-20 bg-gray-10 p-4">
            <div class="text-sm text-gray-600">
              {{ t("Comments on this page") }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-90">
              {{ discussion.commentCount }}
            </div>
          </div>
          <div class="rounded-xl border border-gray-20 bg-gray-10 p-4">
            <div class="text-sm text-gray-600">
              {{ t("Number of comments scored") }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-90">
              {{ discussion.scoredCommentCount }}
            </div>
          </div>
          <div class="rounded-xl border border-gray-20 bg-gray-10 p-4">
            <div class="text-sm text-gray-600">{{ t("Average rating") }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-90">
              {{ formattedAverage }} / 10
            </div>
          </div>
        </div>

        <div
          v-if="discussion.latestAuthorName || formattedLatestUpdatedAt"
          class="mt-4 text-sm text-gray-600"
        >
          <span v-if="discussion.latestAuthorName">
            {{ t("The latest version was edited by") }}:
            {{ discussion.latestAuthorName }}
          </span>
          <span v-if="discussion.latestAuthorName && formattedLatestUpdatedAt">
            ·
          </span>
          <span v-if="formattedLatestUpdatedAt">{{
            formattedLatestUpdatedAt
          }}</span>
        </div>
      </BaseCard>

      <BaseCard v-if="discussion.canComment">
        <template #title>{{ t("Add comment") }}</template>

        <form class="space-y-4" @submit.prevent="submitComment">
          <div>
            <label
              for="wiki_discussion_comment"
              class="mb-2 block text-sm font-medium text-gray-90"
            >
              {{ t("Comments") }}
            </label>
            <textarea
              id="wiki_discussion_comment"
              v-model="form.comment"
              name="comment"
              rows="5"
              class="w-full rounded-lg border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90 focus:border-primary focus:outline-none"
              :placeholder="t('Comments')"
            ></textarea>
          </div>

          <div class="max-w-xs">
            <label
              for="wiki_discussion_rating"
              class="mb-2 block text-sm font-medium text-gray-90"
            >
              {{ t("Rating") }}
            </label>
            <select
              id="wiki_discussion_rating"
              v-model="form.rating"
              name="rating"
              class="w-full rounded-lg border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90 focus:border-primary focus:outline-none disabled:bg-gray-20"
              :disabled="!discussion.canRate"
            >
              <option value="">-</option>
              <option
                v-for="rating in ratingOptions"
                :key="rating"
                :value="String(rating)"
              >
                {{ rating }}
              </option>
            </select>
            <p v-if="!discussion.canRate" class="mt-2 text-xs text-gray-500">
              {{ t("Ratings are blocked in this discussion") }}
            </p>
          </div>

          <BaseButton
            icon="send"
            :is-loading="isSubmitting"
            :label="t('Send')"
            type="success"
            @click="submitComment"
          />
        </form>
      </BaseCard>

      <div
        v-else-if="!discussion.commentsOpen"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
      >
        {{ t("New comments are blocked in this discussion") }}
      </div>

      <BaseCard>
        <template #title>{{ t("Comments") }}</template>

        <div v-if="discussion.comments.length" class="space-y-4">
          <article
            v-for="comment in discussion.comments"
            :key="comment.iid"
            class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
          >
            <div
              class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600"
            >
              <span class="font-semibold text-gray-90">{{
                comment.authorName
              }}</span>
              <span>·</span>
              <span>{{ t(comment.authorRole) }}</span>
              <span>·</span>
              <span>{{ formatDate(comment.createdAt) }}</span>
              <template v-if="comment.rating !== null">
                <span>·</span>
                <span
                  class="inline-flex items-center gap-1 font-medium text-yellow-700"
                >
                  <BaseIcon icon="tracking" size="small" />
                  {{ t("Rating") }}: {{ comment.rating }} / 10
                </span>
              </template>
            </div>
            <p
              class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-gray-90"
            >
              {{ comment.comment }}
            </p>
          </article>
        </div>

        <div v-else class="py-8 text-center text-sm italic text-gray-500">
          {{ t("No comments yet.") }}
        </div>
      </BaseCard>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseCard from "../../components/basecomponents/BaseCard.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue";
import wikiService from "../../services/wikiService";

const { t } = useI18n();
const route = useRoute();

const isLoading = ref(false);
const isManaging = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const discussion = reactive(createEmptyDiscussion());
const form = reactive({ comment: "", rating: "" });
const ratingOptions = Array.from({ length: 11 }, (_, index) => index);

const formattedAverage = computed(() =>
  Number(discussion.averageRating || 0).toFixed(2),
);
const formattedLatestUpdatedAt = computed(() =>
  formatDate(discussion.latestUpdatedAt),
);

function createEmptyDiscussion() {
  return {
    pageId: null,
    reflink: "index",
    title: "",
    latestAuthorName: "",
    latestUpdatedAt: null,
    visible: true,
    commentsOpen: true,
    ratingsOpen: true,
    subscribed: false,
    canManage: false,
    canComment: false,
    canRate: false,
    canSubscribe: false,
    csrfToken: "",
    commentCount: 0,
    scoredCommentCount: 0,
    averageRating: 0,
    comments: [],
  };
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value;
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

function getContextParams() {
  return {
    ...getSharedQuery(),
    node: Number(route.params.node || 0),
  };
}

function getWikiRoute(reflink) {
  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query: { ...getSharedQuery(), title: reflink || "index" },
  };
}

function getReportRoute(report) {
  return {
    name: "WikiReports",
    params: { node: route.params.node },
    query: { ...getSharedQuery(), report },
  };
}

function formatDate(value) {
  if (!value) {
    return "";
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? "" : date.toLocaleString();
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.error ||
    t("An error occurred")
  );
}

async function loadDiscussion() {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const response = await wikiService.getDiscussion(
      Number(route.params.pageId),
      getContextParams(),
    );
    Object.assign(discussion, createEmptyDiscussion(), response);
  } catch (error) {
    console.error("Error loading Wiki discussion", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isLoading.value = false;
  }
}

async function submitComment() {
  if (isSubmitting.value) {
    return;
  }

  if (!form.comment.trim()) {
    errorMessage.value = t("A comment is required");
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    await wikiService.addDiscussionComment(
      Number(route.params.pageId),
      getContextParams(),
      {
        comment: form.comment,
        rating: "" === form.rating ? null : Number(form.rating),
        writeCsrfToken: discussion.csrfToken,
      },
    );
    form.comment = "";
    form.rating = "";
    successMessage.value = t("Comment added");
    await loadDiscussion();
  } catch (error) {
    console.error("Error adding Wiki discussion comment", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isSubmitting.value = false;
  }
}

async function executeAction(action, message) {
  isManaging.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    await action();
    successMessage.value = t(message);
    await loadDiscussion();
  } catch (error) {
    console.error("Error managing Wiki discussion", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isManaging.value = false;
  }
}

function changeVisibility() {
  return executeAction(
    () =>
      wikiService.setDiscussionVisibility(
        Number(route.params.pageId),
        !discussion.visible,
        getContextParams(),
        discussion.csrfToken,
      ),
    discussion.visible ? "Discussion hidden" : "Discussion shown",
  );
}

function changeCommenting() {
  return executeAction(
    () =>
      wikiService.setDiscussionCommenting(
        Number(route.params.pageId),
        !discussion.commentsOpen,
        getContextParams(),
        discussion.csrfToken,
      ),
    discussion.commentsOpen
      ? "Discussion comments blocked"
      : "Discussion comments allowed",
  );
}

function changeRating() {
  return executeAction(
    () =>
      wikiService.setDiscussionRating(
        Number(route.params.pageId),
        !discussion.ratingsOpen,
        getContextParams(),
        discussion.csrfToken,
      ),
    discussion.ratingsOpen
      ? "Discussion ratings blocked"
      : "Discussion ratings allowed",
  );
}

function changeSubscription() {
  return executeAction(
    () =>
      wikiService.setDiscussionSubscription(
        Number(route.params.pageId),
        !discussion.subscribed,
        getContextParams(),
        discussion.csrfToken,
      ),
    discussion.subscribed
      ? "Discussion notifications disabled"
      : "Discussion notifications enabled",
  );
}

onMounted(loadDiscussion);

watch(
  () => [
    route.params.node,
    route.params.pageId,
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.isStudentView,
  ],
  loadDiscussion,
);
</script>
