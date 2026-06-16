<template>
  <section class="space-y-5">
    <div class="exercise-question-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        class="exercise-question-toolbar__button"
        :label="backButtonLabel"
        :route="learningPathContext ? null : { name: 'ExerciseList', params: route.params, query: getContextParams() }"
        :to-url="learningPathContext ? learningPathBackUrl : null"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="!learningPathContext"
        class="exercise-question-toolbar__button"
        :label="t('Preview')"
        :route="{ name: 'ExercisePlayer', params: { ...route.params, exerciseId }, query: getContextParams() }"
        icon="play-box-outline"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="!learningPathContext"
        class="exercise-question-toolbar__button"
        :label="t('Results')"
        :route="{ name: 'ExerciseReport', params: { ...route.params, exerciseId }, query: getContextParams() }"
        icon="tracking"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-question-toolbar__button"
        :label="t('Edit test name and settings')"
        :route="{ name: 'ExerciseEdit', params: { ...route.params, exerciseId }, query: getContextParams() }"
        icon="settings"
        only-icon
        size="small"
        type="secondary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-700 shadow-sm"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <template v-if="!isLoading && !errorMessage">
      <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="space-y-1">
            <h1 class="text-xl font-semibold text-gray-90">
              {{ displayText(title, t("Questions")) }}
            </h1>
            <p class="text-sm text-gray-600">
              {{
                isReadOnlyFromLearningPath
                  ? t("This exercise is read-only because it is included in a learning path.")
                  : t("Select a question type to create a question.")
              }}
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <BaseButton
              v-if="learningPathContext"
              :disabled="questionCount <= 0"
              :is-loading="isAttachingToLearningPath"
              :label="t('Finish and return to learning path')"
              icon="check"
              type="success"
              @click="finishLearningPathCreation"
            />
            <BaseButton
              :disabled="isReadOnlyFromLearningPath"
              :label="t('Recycle existing questions')"
              :route="
                isReadOnlyFromLearningPath
                  ? null
                  : { name: 'ExerciseQuestionBank', params: { ...route.params, exerciseId }, query: getContextParams() }
              "
              icon="table"
              type="primary"
            />
          </div>
        </div>

        <div
          v-if="isReadOnlyFromLearningPath"
          class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-medium text-yellow-800"
        >
          {{ t(learningPathReadOnlyMessage) }}
        </div>

        <div class="rounded-lg border border-info/30 bg-support-1 px-4 py-3 text-sm font-medium text-support-4">
          {{ summaryText }}
        </div>

        <div class="rounded-xl border border-gray-20 bg-white px-3 py-3 shadow-sm">
          <div class="flex flex-wrap items-center gap-3">
            <a
              v-for="questionType in questionTypes"
              :key="`question-type-${questionType.type}`"
              :aria-disabled="isQuestionTypeDisabled(questionType)"
              :aria-label="questionTypeTitle(questionType)"
              :class="questionTypeCardClass(questionType)"
              :href="questionTypeHref(questionType)"
              :title="questionTypeTitle(questionType)"
              @click="openQuestionType(questionType, $event)"
            >
              <span :class="questionTypeIconClass(questionType)">
                <img
                  :alt="t(questionType.label)"
                  class="h-16 w-16 object-contain"
                  :src="questionIconUrl(questionType)"
                  @error="useFallbackIcon"
                />
              </span>
              <span class="sr-only">
                {{ t(questionType.label) }}
              </span>
            </a>
          </div>
        </div>
      </section>

      <section class="space-y-3">
        <div
          v-if="0 === questions.length"
          class="rounded-lg border border-yellow-100 bg-yellow-50 px-4 py-3 text-sm text-yellow-800"
        >
          {{ t("Questions list (there is no question so far).") }}
        </div>

        <div
          v-else
          class="overflow-x-auto rounded-lg border border-gray-20 bg-white shadow-sm"
        >
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-gray-90">
              <tr>
                <th class="w-14 px-3 py-3 text-left font-semibold">{{ t("Order") }}</th>
                <th class="px-3 py-3 text-left font-semibold">{{ t("Question") }}</th>
                <th class="w-32 px-3 py-3 text-center font-semibold">{{ t("Type") }}</th>
                <th class="w-40 px-3 py-3 text-center font-semibold">{{ t("Category") }}</th>
                <th class="w-28 px-3 py-3 text-center font-semibold">{{ t("Difficulty") }}</th>
                <th class="w-24 px-3 py-3 text-center font-semibold">{{ t("Score") }}</th>
                <th class="w-44 px-3 py-3 text-right font-semibold">{{ t("Detail") }}</th>
              </tr>
            </thead>
            <tbody>
              <template
                v-for="question in questions"
                :key="question.id"
              >
                <tr
                  :class="[
                    'border-t border-gray-20 transition hover:bg-gray-10',
                    expandedQuestionId === question.id ? 'bg-gray-10' : 'bg-white',
                    draggedQuestionId === question.id ? 'opacity-50' : '',
                  ]"
                  draggable="true"
                  @dragstart="startDrag(question)"
                  @dragover.prevent
                  @drop="dropQuestion(question)"
                >
                  <td class="px-3 py-3 align-middle">
                    <button
                      class="cursor-move text-gray-70 hover:text-primary"
                      :title="t('Drag to reorder')"
                      type="button"
                      @click.stop
                    >
                      <BaseIcon
                        icon="cursor-move"
                        size="normal"
                      />
                    </button>
                  </td>
                  <td
                    class="cursor-pointer px-3 py-3 align-middle"
                    @click="togglePreview(question.id)"
                  >
                    <div class="space-y-1">
                      <div class="font-semibold text-gray-90">
                        {{ displayText(question.title, t("Untitled")) }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ t("Click to preview") }}
                      </div>
                    </div>
                  </td>
                  <td class="px-3 py-3 text-center align-middle">
                    <span
                      class="inline-flex items-center justify-center"
                      :title="t(question.typeLabel)"
                    >
                      <img
                        v-if="question.typeIcon"
                        :alt="t(question.typeLabel)"
                        class="h-10 w-10 object-contain"
                        :src="`/img/icons/64/${question.typeIcon}`"
                        @error="useSmallFallbackIcon"
                      />
                      <span
                        v-else
                        class="sr-only"
                      >{{ t(question.typeLabel) }}</span>
                    </span>
                  </td>
                  <td class="px-3 py-3 text-center align-middle">
                    {{ displayText(question.categoryLabel, "-") }}
                  </td>
                  <td class="px-3 py-3 text-center align-middle">
                    {{ question.difficulty || 1 }}
                  </td>
                  <td class="px-3 py-3 text-center align-middle">
                    {{ formatScore(question.score) }}
                  </td>
                  <td class="px-3 py-3 align-middle">
                    <div class="flex justify-end gap-1" @click.stop>
                      <BaseButton
                        :label="expandedQuestionId === question.id ? t('Hide preview') : t('Preview')"
                        :icon="expandedQuestionId === question.id ? 'fold' : 'information'"
                        only-icon
                        size="small"
                        type="primary-text"
                        @click="togglePreview(question.id)"
                      />
                      <BaseButton
                        v-if="isVueQuestionType(question.type)"
                        :label="t('Edit')"
                        :route="questionEditRoute(question)"
                        icon="edit"
                        only-icon
                        size="small"
                        type="secondary-text"
                      />
                      <BaseButton
                        v-else-if="!learningPathContext"
                        :label="t('Edit')"
                        :to-url="legacyQuestionEditUrl(question.id)"
                        icon="edit"
                        only-icon
                        size="small"
                        type="secondary-text"
                      />
                      <BaseButton
                        :disabled="isActionSaving || isReadOnlyFromLearningPath"
                        :label="t('Copy')"
                        icon="copy"
                        only-icon
                        size="small"
                        type="warning"
                        @click="duplicateQuestion(question)"
                      />
                      <BaseButton
                        :disabled="isActionSaving || isReadOnlyFromLearningPath"
                        :label="t('Delete')"
                        icon="delete"
                        only-icon
                        size="small"
                        type="danger"
                        @click="confirmDeleteQuestion(question)"
                      />
                    </div>
                  </td>
                </tr>
                <tr
                  v-if="expandedQuestionId === question.id"
                  class="border-t border-gray-20 bg-white"
                >
                  <td colspan="7" class="px-6 py-4">
                    <article class="space-y-4 rounded-lg border border-gray-20 bg-gray-5 p-4">
                      <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <h3 class="text-lg font-semibold text-gray-90">
                            {{ t(question.typeLabel) }}
                          </h3>
                          <div
                            class="prose prose-sm max-w-none text-gray-90"
                            v-html="question.title"
                          />
                        </div>
                        <div class="text-sm font-semibold text-gray-70">
                          {{ t("Score") }}: {{ formatScore(question.score) }}
                        </div>
                      </div>

                      <div
                        v-if="hasHtmlContent(question.description)"
                        class="prose prose-sm max-w-none rounded bg-white p-3 text-gray-80"
                        v-html="question.description"
                      />

                      <div
                        v-if="isAnnotationQuestionType(question.type) && question.annotation?.imageUrl"
                        class="space-y-3"
                      >
                        <p
                          v-if="question.annotation?.imageName"
                          class="text-sm font-semibold text-gray-80"
                        >
                          {{ question.annotation.imageName }}
                        </p>
                        <img
                          :alt="question.annotation?.imageName || t('Image')"
                          class="max-h-80 max-w-full rounded-lg border border-gray-20 bg-white object-contain p-2"
                          :src="question.annotation.imageUrl"
                        />
                      </div>

                      <div
                        v-else-if="isHotspotQuestionType(question.type) && question.hotspot"
                        class="space-y-3"
                      >
                        <p
                          v-if="question.hotspot?.imageName"
                          class="text-sm font-semibold text-gray-80"
                        >
                          {{ question.hotspot.imageName }}
                        </p>
                        <img
                          v-if="question.hotspot?.imageUrl"
                          :alt="question.hotspot?.imageName || t('Image')"
                          class="max-h-80 max-w-full rounded-lg border border-gray-20 bg-white object-contain p-2"
                          :src="question.hotspot.imageUrl"
                        />
                        <div
                          v-if="Array.isArray(question.hotspot.items) && question.hotspot.items.length"
                          class="overflow-x-auto"
                        >
                          <table class="min-w-full border-collapse bg-white text-sm">
                            <thead class="bg-gray-15 text-gray-90">
                              <tr>
                                <th class="w-16 border border-gray-20 px-2 py-2 text-left">{{ t("N°") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Answer") }}</th>
                                <th class="w-32 border border-gray-20 px-2 py-2 text-left">{{ t("Shape") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Coordinates") }}</th>
                                <th
                                  v-if="!isHotspotCombinationQuestionType(question.type)"
                                  class="w-24 border border-gray-20 px-2 py-2 text-right"
                                >
                                  {{ t("Score") }}
                                </th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="(item, itemIndex) in question.hotspot.items"
                                :key="`${question.id}-hotspot-${item.position}`"
                              >
                                <td class="border border-gray-20 px-2 py-2 align-top">{{ itemIndex + 1 }}</td>
                                <td class="border border-gray-20 px-2 py-2 align-top">
                                  <div
                                    class="prose prose-sm max-w-none"
                                    v-html="item.answer"
                                  />
                                </td>
                                <td class="border border-gray-20 px-2 py-2 align-top">{{ t(item.hotspotTypeLabel || item.hotspotType) }}</td>
                                <td class="border border-gray-20 px-2 py-2 align-top">{{ item.coordinates }}</td>
                                <td
                                  v-if="!isHotspotCombinationQuestionType(question.type)"
                                  class="border border-gray-20 px-2 py-2 text-right align-top"
                                >
                                  {{ formatScore(item.score) }}
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <div
                        v-else-if="isFillBlanksQuestionType(question.type) && question.fillBlanks"
                        class="space-y-3"
                      >
                        <div
                          class="prose prose-sm max-w-none rounded bg-white p-3 text-gray-80"
                          v-html="question.fillBlanks.text"
                        />
                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                          <span class="rounded-full bg-gray-15 px-2 py-1 text-gray-80">
                            {{ t("Blanks") }}: {{ question.fillBlanks.items?.length || 0 }}
                          </span>
                          <span
                            v-if="question.fillBlanks.switchable"
                            class="rounded-full bg-blue-50 px-2 py-1 text-blue-800"
                          >
                            {{ t("Switchable blanks") }}
                          </span>
                          <span
                            v-if="question.fillBlanks.caseInsensitive"
                            class="rounded-full bg-blue-50 px-2 py-1 text-blue-800"
                          >
                            {{ t("Case insensitive") }}
                          </span>
                        </div>
                        <div
                          v-if="Array.isArray(question.fillBlanks.items) && question.fillBlanks.items.length"
                          class="overflow-x-auto"
                        >
                          <table class="min-w-full border-collapse bg-white text-sm">
                            <thead class="bg-gray-15 text-gray-90">
                              <tr>
                                <th class="w-16 border border-gray-20 px-2 py-2 text-left">{{ t("N°") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Blank") }}</th>
                                <th class="w-24 border border-gray-20 px-2 py-2 text-right">{{ t("Score") }}</th>
                                <th class="w-32 border border-gray-20 px-2 py-2 text-right">{{ t("Input width") }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="(blank, blankIndex) in question.fillBlanks.items"
                                :key="`${question.id}-blank-${blankIndex}`"
                              >
                                <td class="border border-gray-20 px-2 py-2 align-top">{{ blankIndex + 1 }}</td>
                                <td class="border border-gray-20 px-2 py-2 align-top">
                                  <code class="rounded bg-gray-15 px-2 py-1 text-gray-90">{{ blank.answer }}</code>
                                </td>
                                <td class="border border-gray-20 px-2 py-2 text-right align-top">
                                  {{ formatScore(blank.score) }}
                                </td>
                                <td class="border border-gray-20 px-2 py-2 text-right align-top">
                                  {{ blank.inputSize || 200 }}
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <div
                          v-if="hasHtmlContent(question.fillBlanks.comment)"
                          class="prose prose-sm max-w-none rounded bg-white p-3 text-gray-80"
                          v-html="question.fillBlanks.comment"
                        />
                      </div>

                      <div
                        v-else-if="isMatchingQuestionType(question.type) && question.matching"
                        class="space-y-3"
                      >
                        <div
                          v-if="Array.isArray(question.matching.options) && question.matching.options.length"
                          class="flex flex-wrap gap-2 text-xs font-semibold"
                        >
                          <span
                            v-for="option in question.matching.options"
                            :key="`${question.id}-matching-option-${option.position}`"
                            class="rounded-full bg-gray-15 px-2 py-1 text-gray-80"
                          >
                            {{ option.label }}: {{ displayText(option.answer, t("Option")) }}
                          </span>
                        </div>

                        <div
                          v-if="Array.isArray(question.matching.pairs) && question.matching.pairs.length"
                          class="overflow-x-auto"
                        >
                          <table class="min-w-full border-collapse bg-white text-sm">
                            <thead class="bg-gray-15 text-gray-90">
                              <tr>
                                <th class="w-16 border border-gray-20 px-2 py-2 text-left">{{ t("N°") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Question") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Matches To") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Feedback") }}</th>
                                <th
                                  v-if="!isMatchingCombinationQuestionType(question.type)"
                                  class="w-24 border border-gray-20 px-2 py-2 text-right"
                                >
                                  {{ t("Score") }}
                                </th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="(pair, pairIndex) in question.matching.pairs"
                                :key="`${question.id}-matching-pair-${pair.position}`"
                              >
                                <td class="border border-gray-20 px-2 py-2 align-top">{{ pairIndex + 1 }}</td>
                                <td class="border border-gray-20 px-2 py-2 align-top">
                                  <div
                                    class="prose prose-sm max-w-none"
                                    v-html="pair.answer"
                                  />
                                </td>
                                <td class="border border-gray-20 px-2 py-2 align-top">
                                  <span class="font-semibold text-gray-90">{{ pair.optionLabel }}</span>
                                  <div
                                    class="prose prose-sm max-w-none"
                                    v-html="pair.optionAnswer"
                                  />
                                </td>
                                <td class="border border-gray-20 px-2 py-2 align-top">
                                  <div
                                    class="prose prose-sm max-w-none"
                                    v-html="pair.comment"
                                  />
                                </td>
                                <td
                                  v-if="!isMatchingCombinationQuestionType(question.type)"
                                  class="border border-gray-20 px-2 py-2 text-right align-top"
                                >
                                  {{ formatScore(pair.score) }}
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <div
                        v-else-if="isDraggableQuestionType(question.type) && question.draggable"
                        class="space-y-3"
                      >
                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                          <span class="rounded-full bg-gray-15 px-2 py-1 text-gray-80">
                            {{ t("Orientation") }}: {{ question.draggable.orientation === 'v' ? t("Vertical") : t("Horizontal") }}
                          </span>
                        </div>

                        <div
                          v-if="Array.isArray(question.draggable.items) && question.draggable.items.length"
                          class="overflow-x-auto"
                        >
                          <table class="min-w-full border-collapse bg-white text-sm">
                            <thead class="bg-gray-15 text-gray-90">
                              <tr>
                                <th class="w-16 border border-gray-20 px-2 py-2 text-left">{{ t("N°") }}</th>
                                <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Answer") }}</th>
                                <th class="w-32 border border-gray-20 px-2 py-2 text-center">{{ t("Matches To") }}</th>
                                <th class="w-24 border border-gray-20 px-2 py-2 text-right">{{ t("Score") }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="(item, itemIndex) in question.draggable.items"
                                :key="`${question.id}-draggable-${item.position}`"
                              >
                                <td class="border border-gray-20 px-2 py-2 align-top">{{ itemIndex + 1 }}</td>
                                <td class="border border-gray-20 px-2 py-2 align-top">
                                  <div
                                    class="prose prose-sm max-w-none"
                                    v-html="item.answer"
                                  />
                                </td>
                                <td class="border border-gray-20 px-2 py-2 text-center align-top">
                                  {{ item.targetPosition }}
                                </td>
                                <td class="border border-gray-20 px-2 py-2 text-right align-top">
                                  {{ formatScore(item.score) }}
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <div
                        v-else-if="Array.isArray(question.answers) && question.answers.length"
                        class="overflow-x-auto"
                      >
                        <table class="min-w-full border-collapse bg-white text-sm">
                          <thead class="bg-gray-15 text-gray-90">
                            <tr>
                              <th class="w-28 border border-gray-20 px-2 py-2 text-left">
                                {{ isTrueFalseQuestionType(question.type) ? t("Expected choice") : t("True") }}
                              </th>
                              <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Options") }}</th>
                              <th class="border border-gray-20 px-2 py-2 text-left">{{ t("Feedback") }}</th>
                              <th class="w-24 border border-gray-20 px-2 py-2 text-right">{{ t("Score") }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr
                              v-for="answer in question.answers"
                              :key="`${question.id}-${answer.position}`"
                            >
                              <td class="border border-gray-20 px-2 py-2 align-top">
                                <span
                                  v-if="isTrueFalseQuestionType(question.type)"
                                  class="rounded-full bg-gray-15 px-2 py-1 text-xs font-semibold text-gray-80"
                                >
                                  {{ trueFalseChoiceLabel(answer) }}
                                </span>
                                <input
                                  v-else
                                  :checked="answer.correct"
                                  disabled
                                  :type="isSingleCorrectAnswerQuestion(question.type) ? 'radio' : 'checkbox'"
                                />
                              </td>
                              <td class="border border-gray-20 px-2 py-2 align-top">
                                <div
                                  class="prose prose-sm max-w-none"
                                  v-html="answer.answer"
                                />
                              </td>
                              <td class="border border-gray-20 px-2 py-2 align-top">
                                <div
                                  class="prose prose-sm max-w-none"
                                  v-html="answer.comment"
                                />
                              </td>
                              <td class="border border-gray-20 px-2 py-2 text-right align-top">
                                {{ formatScore(answer.score) }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </article>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import exerciseService from "../../services/exerciseService"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isActionSaving = ref(false)
const isAttachingToLearningPath = ref(false)
const errorMessage = ref("")
const title = ref("")
const questionCount = ref(0)
const totalScore = ref(0)
const questionTypes = ref([])
const questions = ref([])
const csrfToken = ref("")
const isReadOnlyFromLearningPath = ref(false)
const learningPathReadOnlyMessage = ref(
  "This exercise has been included in a learning path, so it cannot be accessed by students directly from here. If you want to put the same exercise available through the exercises tool, please make a copy of the current exercise using the copy icon.",
)
const expandedQuestionId = ref(null)
const draggedQuestionId = ref(null)

const exerciseId = Number(getQueryValue(route.params.exerciseId) || 0)
const summaryText = computed(() =>
  formatTranslatedText("{0} questions, for a total score (all questions) of {1}.", [questionCount.value, formatScore(totalScore.value)]),
)
const learningPathContext = computed(() => isLearningPathContext())
const learningPathBackUrl = computed(() => buildLearningPathBackUrl())
const backButtonLabel = computed(() => (learningPathContext.value ? t("Back to learning path") : t("Return to exercises list")))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }

  for (const key of ["origin", "lp_id", "learnpath_id", "node", "type", "returnToLp", "isStudentView", "gradebook"]) {
    const value = getQueryValue(route.query[key])
    if (value !== undefined && value !== null && String(value) !== "") {
      params[key] = value
    }
  }

  return params
}

function isLearningPathContext() {
  const origin = String(getQueryValue(route.query.origin) || "").toLowerCase()
  const returnToLp = String(getQueryValue(route.query.returnToLp) || "").toLowerCase()
  const lpId = Number(getQueryValue(route.query.lp_id) || getQueryValue(route.query.learnpath_id) || 0)

  return lpId > 0 && (origin === "learnpath" || ["1", "true", "yes"].includes(returnToLp))
}

function buildLearningPathBackUrl() {
  const params = new URLSearchParams()
  params.set("action", "build")
  params.set("type", getQueryValue(route.query.type) || "step")
  params.set("lp_id", getQueryValue(route.query.lp_id) || getQueryValue(route.query.learnpath_id) || "0")

  for (const key of ["cid", "sid", "gid", "gradebook", "origin", "node", "isStudentView"]) {
    const value = getQueryValue(route.query[key])
    if (value !== undefined && value !== null && String(value) !== "") {
      params.set(key, String(value))
    }
  }

  return `/main/lp/lp_controller.php?${params.toString()}#resource_tab-2`
}

function buildQueryString(params = {}) {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && String(value) !== "") {
      query.set(key, String(value))
    }
  }

  const queryString = query.toString()

  return queryString ? `?${queryString}` : ""
}

function formatTranslatedText(key, replacements = []) {
  return replacements.reduce(
    (text, value, index) => String(text).split(`{${index}}`).join(String(value)),
    t(key),
  )
}

function legacyUrl(path, params = {}) {
  return `/main/exercise/${path}${buildQueryString({ ...getContextParams(), ...params })}`
}

function buildLegacyCreateQuestionUrl(answerType) {
  return legacyUrl("admin.php", {
    exerciseId,
    newQuestion: "yes",
    isContent: 1,
    answerType,
  })
}

function getQuestionTypeId(questionTypeOrId) {
  return Number(typeof questionTypeOrId === "object" ? questionTypeOrId?.type : questionTypeOrId)
}

function isVueQuestionType(questionTypeOrId) {
  if (typeof questionTypeOrId === "object" && questionTypeOrId?.migratedToVue === true) {
    return true
  }

  return [1, 2, 3, 4, 5, 6, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 31].includes(getQuestionTypeId(questionTypeOrId))
}

function questionTypeHelp(questionTypeOrId) {
  const type = getQuestionTypeId(questionTypeOrId)

  return {
    1: "Choose one correct answer.",
    2: "Select one or more correct answers.",
    3: "Complete missing words inside a text.",
    4: "Match each item with the correct option. Score per item.",
    5: "Learners write a free text answer.",
    6: "Select areas in an image.",
    8: "Draw or delineate an area.",
    9: "Score only when the exact selection is correct.",
    10: "Single choice with a fixed Don't know option.",
    11: "Mark each statement as True, False or Don't know.",
    12: "Full True/False combination must be correct.",
    13: "Record or evaluate an oral answer.",
    14: "Multiple answer with one global score.",
    15: "Add content, media or reading context without score.",
    16: "Calculated question with formulas and variables.",
    17: "Single choice question using images.",
    18: "Put items in the expected order.",
    19: "Drag items to match them with options. Score per item.",
    20: "Annotation question with an image upload.",
    21: "Add a reading text with scrolling speed settings.",
    22: "True/False answer with certainty percentage selected during the attempt.",
    23: "Learners upload a file as their answer. The teacher assigns the score during correction.",
    24: "Matching with one global combination score.",
    25: "Full draggable matching combination with one global score.",
    26: "Hotspot with one global combination score.",
    27: "Fill blanks with one global combination score.",
    28: "Dropdown options with one global combination score.",
    29: "Dropdown options where selected answers can have their own score.",
    31: "Insert a page break inside the test without score.",
  }[type] || "Question type"
}

function questionTypeHref(questionType) {
  if (isQuestionTypeDisabled(questionType)) {
    return undefined
  }

  if (isVueQuestionType(questionType)) {
    return router.resolve(questionCreateRoute(questionType)).href
  }

  if (learningPathContext.value) {
    return undefined
  }

  return buildLegacyCreateQuestionUrl(questionType.type)
}

function questionTypeTitle(questionType) {
  const label = t(questionType.label)

  if (isReadOnlyFromLearningPath.value) {
    return `${label} - ${t("This exercise is read-only because it is included in a learning path.")}`
  }

  if (!questionType.enabled) {
    return `${label} - ${t("Not available with the current exercise feedback mode")}`
  }

  if (learningPathContext.value && !isVueQuestionType(questionType)) {
    return `${label} - ${t("Not available in the learning path creation flow")}`
  }

  return `${label} - ${t(questionTypeHelp(questionType))}`
}

function isQuestionTypeDisabled(questionType) {
  return (
    isReadOnlyFromLearningPath.value ||
    !questionType.enabled ||
    (learningPathContext.value && !isVueQuestionType(questionType))
  )
}

function questionTypeCardClass(questionType) {
  return [
    "group inline-flex h-20 w-20 items-center justify-center rounded-md border-2 border-transparent text-center transition hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary/30",
    isQuestionTypeDisabled(questionType) ? "pointer-events-none opacity-50" : "",
  ]
}

function questionTypeIconClass(questionType) {
  return [
    "relative flex h-16 w-16 items-center justify-center transition group-hover:scale-105",
  ]
}

function isSingleCorrectAnswerQuestion(type) {
  return [1, 10].includes(Number(type))
}

function isTrueFalseQuestionType(type) {
  return [11, 12, 22].includes(Number(type))
}

function isFillBlanksQuestionType(type) {
  return [3, 27].includes(Number(type))
}

function isAnnotationQuestionType(type) {
  return 20 === Number(type)
}

function isHotspotQuestionType(type) {
  return [6, 26].includes(Number(type))
}

function isHotspotCombinationQuestionType(type) {
  return 26 === Number(type)
}

function isMatchingQuestionType(type) {
  return [4, 19, 24, 25].includes(Number(type))
}

function isMatchingCombinationQuestionType(type) {
  return [24, 25].includes(Number(type))
}

function isDraggableQuestionType(type) {
  return 18 === Number(type)
}

function trueFalseChoiceLabel(answer) {
  const choice = Number(answer.correctChoice || 0)

  if (1 === choice) {
    return t("True")
  }

  if (2 === choice) {
    return t("False")
  }

  return t("Don't know")
}

function questionCreateRoute(questionType) {
  return {
    name: "ExerciseQuestionCreate",
    params: { ...route.params, exerciseId, questionType: Number(questionType.type) },
    query: getContextParams(),
  }
}

function questionEditRoute(question) {
  return {
    name: "ExerciseQuestionEdit",
    params: { ...route.params, exerciseId, questionId: Number(question.id) },
    query: getContextParams(),
  }
}

async function openQuestionType(questionType, event) {
  if (isQuestionTypeDisabled(questionType)) {
    event.preventDefault()
    return
  }

  if (!isVueQuestionType(questionType)) {
    if (learningPathContext.value) {
      event.preventDefault()
    }
    return
  }

  event.preventDefault()
  await router.push(questionCreateRoute(questionType))
}

function legacyQuestionEditUrl(questionId) {
  return legacyUrl("admin.php", {
    exerciseId,
    editQuestion: questionId,
  })
}

function questionIconUrl(questionType) {
  const icon = questionType.enabled ? questionType.icon : questionType.icon?.replace(/\.png$/, "_na.png")

  return `/img/icons/64/${icon || "new_question.png"}`
}

function useFallbackIcon(event) {
  event.target.src = "/img/icons/64/new_question.png"
}

function useSmallFallbackIcon(event) {
  event.target.src = "/img/icons/64/new_question.png"
}


function decodeHtml(value) {
  if (!value) {
    return ""
  }

  if (typeof document === "undefined") {
    return String(value)
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

function hasHtmlContent(value) {
  return displayText(value).length > 0
}

function formatScore(score) {
  const value = Number(score || 0)

  return Number.isInteger(value) ? String(value) : value.toFixed(2)
}

function togglePreview(questionId) {
  expandedQuestionId.value = expandedQuestionId.value === questionId ? null : questionId
}

function startDrag(question) {
  draggedQuestionId.value = Number(question.id)
}

async function dropQuestion(targetQuestion) {
  if (isReadOnlyFromLearningPath.value) {
    draggedQuestionId.value = null
    return
  }

  const draggedId = Number(draggedQuestionId.value || 0)
  const targetId = Number(targetQuestion.id || 0)
  draggedQuestionId.value = null

  if (!draggedId || !targetId || draggedId === targetId) {
    return
  }

  const fromIndex = questions.value.findIndex((question) => Number(question.id) === draggedId)
  const toIndex = questions.value.findIndex((question) => Number(question.id) === targetId)

  if (fromIndex < 0 || toIndex < 0) {
    return
  }

  const nextQuestions = [...questions.value]
  const [movedQuestion] = nextQuestions.splice(fromIndex, 1)
  nextQuestions.splice(toIndex, 0, movedQuestion)
  questions.value = nextQuestions.map((question, index) => ({ ...question, position: index + 1 }))

  await runQuestionAction({
    action: "reorder",
    questionIds: questions.value.map((question) => Number(question.id)),
  })
}

function confirmDeleteQuestion(question) {
  if (isReadOnlyFromLearningPath.value) {
    return
  }

  requireConfirmation({
    title: t("Delete question"),
    message: t("Are you sure you want to delete the question?"),
    accept: () => deleteQuestion(question),
  })
}

async function deleteQuestion(question) {
  await runQuestionAction({
    action: "delete",
    questionId: Number(question.id),
  })
}

async function duplicateQuestion(question) {
  if (isReadOnlyFromLearningPath.value) {
    return
  }

  await runQuestionAction({
    action: "duplicate",
    questionId: Number(question.id),
  })
}

async function runQuestionAction(payload) {
  if (isActionSaving.value) {
    return
  }

  isActionSaving.value = true
  errorMessage.value = ""

  try {
    await exerciseService.saveExerciseQuestionAction(
      {
        exerciseId,
        submittedCsrfToken: csrfToken.value,
        ...payload,
      },
      getContextParams(),
      exerciseId,
    )
    await loadQuestionSelector()
  } catch (error) {
    console.error("Error processing exercise question action", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not update exercise questions")
    await loadQuestionSelector()
  } finally {
    isActionSaving.value = false
  }
}

async function finishLearningPathCreation() {
  errorMessage.value = ""

  if (!learningPathContext.value) {
    return
  }

  if (questionCount.value <= 0) {
    errorMessage.value = t("Add at least one question before returning to the learning path.")
    return
  }

  isAttachingToLearningPath.value = true

  try {
    await exerciseService.attachExerciseToLearningPath(
      {
        exerciseId,
        submittedCsrfToken: csrfToken.value,
      },
      getContextParams(),
      exerciseId,
    )
    window.location.href = learningPathBackUrl.value
  } catch (error) {
    console.error("Error adding exercise to learning path", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not add the exercise to the learning path")
  } finally {
    isAttachingToLearningPath.value = false
  }
}

async function loadQuestionSelector() {
  if (!exerciseId) {
    errorMessage.value = t("A valid exercise id is required.")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseQuestions(getContextParams(), exerciseId)
    title.value = response.title || ""
    isReadOnlyFromLearningPath.value = true === response.isReadOnlyFromLearningPath
    learningPathReadOnlyMessage.value = response.learningPathReadOnlyMessage || learningPathReadOnlyMessage.value
    questionCount.value = Number(response.questionCount || 0)
    totalScore.value = Number(response.totalScore || 0)
    questionTypes.value = Array.isArray(response.questionTypes) ? response.questionTypes : []
    questions.value = Array.isArray(response.questions) ? response.questions : []
    csrfToken.value = response.csrfToken || ""
  } catch (error) {
    console.error("Error loading exercise questions", error)
    errorMessage.value = t("Could not load exercise questions")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuestionSelector)
</script>

<style scoped>
:deep(.exercise-question-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-question-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}
</style>
