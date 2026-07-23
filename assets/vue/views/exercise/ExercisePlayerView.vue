<template>
  <section class="space-y-5" @click="handleRuntimeContentClick">
    <div
      v-if="!isLearnpathContext"
      class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit"
    >
      <BaseButton
        :label="t('Return to exercises list')"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="showLegacyRuntimeFallback"
        :label="t('Open test')"
        :to-url="legacyUrls.overview"
        icon="play-box-outline"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="canManage"
        :label="t('Results')"
        :route="{ name: 'ExerciseReport', params: { ...route.params, exerciseId: getExerciseId() }, query: getContextParams() }"
        icon="tracking"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

    <div v-if="!isLearnpathContext" class="border-b border-gray-20" />

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
      <header class="space-y-3 rounded-xl border border-gray-20 bg-white p-5 shadow-sm">
        <div class="space-y-2">
          <h1 class="text-2xl font-semibold text-gray-90">
            {{ displayText(title, t("Untitled")) }}
          </h1>
          <div
            v-if="description"
            class="exercise-runtime-html text-sm text-gray-700"
            v-html="description"
          />
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
          <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
            {{ t("Questions") }}: {{ questionCount }}
          </span>
          <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
            {{ t("Total score") }}: {{ totalScore }}
          </span>
          <span
            v-if="settings.duration"
            class="rounded-full bg-blue-100 px-2 py-1 text-blue-700"
          >
            {{ t("Duration") }}: {{ t("{0} min", [settings.duration]) }}
          </span>
          <span
            v-if="settings.maxAttempt"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Attempts") }}: {{ settings.maxAttempt }}
          </span>
          <span
            v-if="settings.oneQuestionPerPage"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("One question per page") }}
          </span>
          <span
            v-if="settings.randomQuestions"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Random questions") }}: {{ settings.randomQuestions }}
          </span>
          <span
            v-if="settings.randomAnswers"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Random answers") }}
          </span>
          <span
            v-if="settings.preventBackwards"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Prevent moving backwards") }}
          </span>
          <span
            v-if="settings.showPreviousButton === false"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Previous button hidden") }}
          </span>
        </div>
      </header>

      <div
        v-if="showLegacyRuntimeFallback"
        class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="space-y-2">
            <p>
              {{ t("This exercise uses options that require a different test player.") }}
            </p>
            <ul
              v-if="legacyRuntimeReasons.length"
              class="list-disc pl-5 text-xs"
            >
              <li
                v-for="reason in legacyRuntimeReasons"
                :key="reason"
              >
                {{ t(reason) }}
              </li>
            </ul>
          </div>
          <BaseButton
            v-if="legacyUrls.overview"
            :label="t('Continue test')"
            :to-url="legacyUrls.overview"
            icon="play-box-outline"
            type="primary"
          />
        </div>
      </div>

      <div
        v-if="!showLegacyRuntimeFallback"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="space-y-1 text-sm text-gray-700">
            <div class="font-semibold text-gray-90">
              {{ activeAttempt ? t("Attempt started") : t("Attempt") }}
            </div>
            <div v-if="canManage">
              {{ t("Teacher preview does not create a tracked attempt.") }}
            </div>
            <div v-else-if="activeAttempt">
              {{ currentAttemptLabel }} · {{ progressLabel }}
              <span v-if="hasTimeControl">
                · {{ timeControlLabel }}:
                <strong :class="isDisplayedTimeExpired ? 'text-danger' : 'text-gray-90'">{{ formatSeconds(displayedRemainingSeconds) }}</strong>
              </span>
            </div>
            <div v-else>
              {{ t("Start or resume an exercise attempt. Draft answers can be saved before final submission.") }}
            </div>
            <div v-if="currentCategoryLabel" class="text-support-4">
              {{ t("Category") }}: {{ currentCategoryLabel }}
            </div>
            <div v-if="attemptMessage" class="text-support-4">
              {{ attemptMessage }}
            </div>
            <div v-if="attemptError" class="text-danger">
              {{ attemptError }}
            </div>
            <div v-if="answerSaveMessage" class="text-green-700">
              {{ answerSaveMessage }}
            </div>
            <div v-if="answerSaveError" class="text-danger">
              {{ answerSaveError }}
            </div>
            <div v-if="isTimeExpired" class="text-danger">
              {{ t("Time limit reached. Finishing the attempt.") }}
            </div>
            <div v-if="isQuestionTimeExpired" class="text-warning">
              {{ t("Question time reached. Saving your answer.") }}
            </div>
            <div v-if="finishMessage" class="text-support-4">
              {{ finishMessage }}
            </div>
            <div v-if="finishError" class="text-danger">
              {{ finishError }}
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="!canManage && !activeAttempt && canStartAttempt"
              :disabled="isStartingAttempt"
              :label="isStartingAttempt ? t('Starting') : t('Start test')"
              icon="play-box-outline"
              type="primary"
              @click="startAttempt"
            />
          </div>
        </div>
      </div>

      <div
        v-if="showReviewReminderScreen"
        class="space-y-4 rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
      >
        <div class="space-y-2">
          <h2 class="text-xl font-semibold text-gray-90">
            {{ reviewReminderTitle }}
          </h2>
          <p v-if="isReviewAnswersEnabled" class="text-sm text-gray-700">
            {{ t("Review selected questions") }}
          </p>
          <div
            v-if="reviewFlagError"
            class="text-sm text-danger"
          >
            {{ reviewFlagError }}
          </div>
        </div>

        <div class="space-y-2">
          <label
            v-for="question in reviewQuestionList"
            :key="`review-question-${question.id}`"
            class="flex items-start gap-3 rounded-lg border border-gray-20 p-3 text-sm text-gray-700"
            :class="question.isAnswered ? 'bg-white' : 'border-danger/30 bg-danger/10 text-danger'"
          >
            <input
              v-if="isReviewAnswersEnabled"
              :checked="question.isMarked"
              class="mt-1"
              :disabled="isReviewFlagSaving"
              :name="`remind_list_${question.id}`"
              type="checkbox"
              @change="toggleReviewQuestion(question.id, $event.target.checked)"
            />
            <span class="min-w-0 flex-1">
              <span class="block font-semibold text-gray-90">
                {{ question.position }}. {{ displayText(question.title, t("Untitled")) }}
              </span>
              <span
                v-if="!question.isAnswered"
                class="mt-1 inline-block rounded bg-danger/10 px-2 py-0.5 text-xs text-danger"
              >
                {{ t("Questions without answer") }}
              </span>
            </span>
          </label>
        </div>

        <div class="flex flex-wrap gap-2">
          <BaseButton
            v-if="!isReviewAnswersEnabled && firstUnansweredReviewQuestionId > 0"
            :disabled="isReviewFlagSaving"
            :label="t('Proceed with the test')"
            icon="back"
            type="secondary"
            @click="returnToFirstUnansweredQuestion"
          />
          <BaseButton
            v-if="isReviewAnswersEnabled"
            :disabled="isReviewFlagSaving || selectedReviewQuestionIds.length === 0"
            :label="t('Review selected questions')"
            icon="eye"
            type="primary"
            @click="reviewSelectedQuestions"
          />
          <BaseButton
            v-if="isReviewAnswersEnabled"
            :disabled="isReviewFlagSaving"
            :label="t('Select all')"
            icon="checkbox-marked-outline"
            type="plain"
            @click="setAllReviewQuestions(true)"
          />
          <BaseButton
            v-if="isReviewAnswersEnabled"
            :disabled="isReviewFlagSaving"
            :label="t('Unselect all')"
            icon="checkbox-blank-outline"
            type="plain"
            @click="setAllReviewQuestions(false)"
          />
          <BaseButton
            :disabled="isReviewFlagSaving || isFinishingAttempt || isAutoFinishingExpiredAttempt"
            :label="isFinishingAttempt ? t('Finishing') : t('End test')"
            icon="check"
            type="primary"
            @click="finishAttempt({ skipDraftSave: true, skipReviewAnswers: true, ignoreFeedback: true })"
          />
        </div>
      </div>

      <form v-if="!showReviewReminderScreen && (canManage || activeAttempt)" class="space-y-4" @submit.prevent="submitDisabled">
        <div
          v-if="currentRuntimePage?.pageBreak || currentRuntimePage?.media"
          class="space-y-4 rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
        >
          <div
            v-if="currentRuntimePage?.pageBreak"
            class="rounded-lg border border-gray-20 bg-gray-10 p-4"
          >
            <div
              v-if="currentRuntimePage.pageBreak.title"
              class="exercise-runtime-html text-lg font-semibold text-gray-90"
              v-html="currentRuntimePage.pageBreak.title"
            />
            <div
              v-if="currentRuntimePage.pageBreak.description"
              class="exercise-runtime-html mt-2 text-sm text-gray-700"
              v-html="currentRuntimePage.pageBreak.description"
            />
          </div>

          <div
            v-if="currentRuntimePage?.media"
            class="rounded-lg border border-dashed border-gray-40 bg-gray-10 p-4"
          >
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
              {{ t("Media question") }}
            </div>
            <h2
              v-if="currentRuntimePage.media.title"
              class="exercise-runtime-html text-lg font-semibold text-gray-90"
              v-html="currentRuntimePage.media.title"
            />
            <div
              v-if="currentRuntimePage.media.description || currentRuntimePage.media.content?.description"
              class="exercise-runtime-html mt-2 text-sm text-gray-700"
              v-html="currentRuntimePage.media.description || currentRuntimePage.media.content?.description"
            />
          </div>
        </div>

        <article
          v-for="(question, index) in visibleQuestions"
          :key="question.id"
          class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
        >
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="space-y-1">
              <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                {{ questionNumberLabel(question, index) }} · {{ t(question.typeLabel) }}
              </div>
              <h2
                v-if="!settings.hideQuestionTitle"
                class="exercise-runtime-html text-lg font-semibold text-gray-90"
                v-html="question.title"
              />
              <div
                v-if="question.description && !isReadingQuestion(question)"
                class="exercise-runtime-html text-sm text-gray-700"
                v-html="question.description"
              />
            </div>
            <div class="flex flex-wrap gap-2">
              <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                {{ t("Score") }}: {{ question.score }}
              </span>
              <span
                v-if="savedQuestionIds.has(Number(question.id))"
                class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700"
              >
                {{ t("Draft saved") }}
              </span>
            </div>
          </div>

          <div v-if="answers[question.id]" class="space-y-4">
            <div v-if="isRadioChoice(question)" class="space-y-3">
              <div
                v-if="isReadingQuestion(question) && question.reading?.text"
                class="exercise-runtime-html rounded-lg border border-gray-20 bg-gray-10 p-4 text-gray-800"
                v-html="question.reading.text"
              />
              <label
                v-for="choice in question.choices"
                :key="choice.id"
                class="flex items-start gap-3 rounded-lg border border-gray-20 p-3 hover:bg-gray-10"
              >
                <input
                  v-model="answers[question.id].choice"
                  class="mt-1"
                  :name="`question_${question.id}`"
                  type="radio"
                  :value="choice.id"
                />
                <span class="exercise-runtime-html flex-1" v-html="choice.answer" />
              </label>
            </div>

            <div v-else-if="isCheckboxChoice(question)" class="space-y-3">
              <label
                v-for="choice in question.choices"
                :key="choice.id"
                class="flex items-start gap-3 rounded-lg border border-gray-20 p-3 hover:bg-gray-10"
              >
                <input
                  v-model="answers[question.id].choices"
                  class="mt-1"
                  :name="`question_${question.id}[]`"
                  type="checkbox"
                  :value="choice.id"
                />
                <span class="exercise-runtime-html flex-1" v-html="choice.answer" />
              </label>
            </div>

            <div v-else-if="isTrueFalseQuestion(question)" class="space-y-3">
              <div
                v-if="isDegreeCertaintyQuestion(question)"
                class="overflow-x-auto rounded-lg border border-gray-20"
              >
                <table class="min-w-full divide-y divide-gray-20 text-sm">
                  <thead class="bg-gray-10 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                      <th class="w-full px-4 py-3">
                        {{ t("Statement") }}
                      </th>
                      <th
                        v-for="option in trueFalseChoiceOptions(question)"
                        :key="`degree-certainty-answer-header-${option.value}`"
                        class="whitespace-nowrap px-3 py-3 text-center"
                      >
                        {{ option.label }}
                      </th>
                      <th
                        v-for="option in degreeCertaintyOptions(question)"
                        :key="`degree-certainty-header-${option.value}`"
                        class="whitespace-nowrap px-3 py-3 text-center"
                      >
                        {{ option.label }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-20 bg-white">
                    <tr
                      v-for="choice in question.choices"
                      :key="choice.id"
                    >
                      <td class="min-w-[18rem] px-4 py-3 align-top">
                        <div class="exercise-runtime-html font-medium text-gray-90" v-html="choice.answer" />
                      </td>
                      <td
                        v-for="option in trueFalseChoiceOptions(question)"
                        :key="`${choice.id}-${option.value}`"
                        class="px-3 py-3 text-center align-middle"
                      >
                        <input
                          v-model="answers[question.id].trueFalse[choice.id]"
                          :aria-label="`${displayText(choice.answer)} - ${option.label}`"
                          :name="`question_${question.id}_${choice.id}`"
                          type="radio"
                          :value="option.value"
                        />
                      </td>
                      <td
                        v-for="option in degreeCertaintyOptions(question)"
                        :key="`${choice.id}-degree-${option.value}`"
                        class="px-3 py-3 text-center align-middle"
                      >
                        <input
                          v-model="answers[question.id].degreeCertainty[choice.id]"
                          :aria-label="`${displayText(choice.answer)} - ${option.label}`"
                          :name="`question_${question.id}_${choice.id}_degree`"
                          type="radio"
                          :value="option.value"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <template v-else>
                <div
                  v-for="choice in question.choices"
                  :key="choice.id"
                  class="rounded-lg border border-gray-20 p-3"
                >
                  <div class="exercise-runtime-html mb-3 font-medium text-gray-90" v-html="choice.answer" />
                  <div class="flex flex-wrap gap-3">
                    <label
                      v-for="option in trueFalseChoiceOptions(question)"
                      :key="`${choice.id}-${option.value}`"
                      class="inline-flex items-center gap-2 text-sm text-gray-700"
                    >
                      <input
                        v-model="answers[question.id].trueFalse[choice.id]"
                        :name="`question_${question.id}_${choice.id}`"
                        type="radio"
                        :value="option.value"
                      />
                      <span>{{ option.label }}</span>
                    </label>
                  </div>
                </div>
              </template>
            </div>

            <div v-else-if="isFillBlanksQuestion(question)" class="rounded-lg border border-gray-20 p-4 text-gray-800">
              <template
                v-for="(segment, segmentIndex) in question.fillBlanks.segments"
                :key="`${question.id}-blank-segment-${segmentIndex}`"
              >
                <span
                  v-if="segment.type === 'text'"
                  class="exercise-runtime-html inline"
                  v-html="segment.text"
                />
                <input
                  v-else
                  v-model="answers[question.id].blanks[segment.position]"
                  class="mx-1 inline-block rounded border border-gray-30 px-2 py-1 text-sm"
                  :name="`question_${question.id}_blank_${segment.position}`"
                  :style="{ width: `${Math.min(Math.max(Number(segment.inputSize || 160), 80), 320)}px` }"
                  type="text"
                />
              </template>
            </div>

            <div v-else-if="isMatchingDraggableQuestion(question)" class="space-y-4">
              <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,22rem)]">
                <div class="space-y-3">
                  <div class="text-sm font-semibold text-gray-80">
                    {{ t("Drop each option on the matching answer") }}
                  </div>

                  <div
                    v-for="prompt in question.matching.prompts"
                    :key="prompt.id"
                    class="grid gap-3 rounded-lg border border-gray-20 bg-white p-3 md:grid-cols-[minmax(0,1fr)_minmax(14rem,18rem)] md:items-stretch"
                    @dragover.prevent
                    @drop.prevent="onMatchingDrop(question, prompt.id)"
                  >
                    <div class="exercise-runtime-html min-w-0 text-gray-900" v-html="prompt.answer" />

                    <button
                      type="button"
                      class="min-h-[3.25rem] rounded-lg border border-dashed px-3 py-2 text-left text-sm transition"
                      :class="selectedMatchingOptionId(question, prompt.id)
                        ? 'border-primary bg-primary/5 text-gray-900'
                        : 'border-gray-30 bg-gray-15 text-gray-500 hover:border-primary hover:bg-primary/5'"
                      @click="assignSelectedMatchingOption(question, prompt.id)"
                    >
                      <template v-if="selectedMatchingOption(question, prompt.id)">
                        <span class="mb-1 block text-xs font-semibold uppercase text-primary">
                          {{ matchingOptionDisplayLabel(selectedMatchingOption(question, prompt.id)) }}
                        </span>
                        <span
                          class="exercise-runtime-html block"
                          v-html="selectedMatchingOption(question, prompt.id).answer"
                        />
                        <span
                          class="mt-2 inline-flex text-xs text-danger"
                          @click.stop="clearMatchingOption(question, prompt.id)"
                        >
                          {{ t("Clear match") }}
                        </span>
                      </template>
                      <template v-else>
                        {{ selectedMatchingOptionForQuestion(question)
                          ? t("Click to place the selected option here")
                          : t("Select an option, then click here") }}
                      </template>
                    </button>
                  </div>
                </div>

                <aside class="space-y-3 rounded-lg border border-gray-20 bg-gray-15 p-3">
                  <div class="text-sm font-semibold text-gray-80">
                    {{ t("Options") }}
                  </div>

                  <button
                    v-for="option in question.matching.options"
                    :key="option.id"
                    type="button"
                    draggable="true"
                    class="w-full rounded-lg border px-3 py-2 text-left text-sm transition"
                    :class="matchingOptionButtonClass(question, option.id)"
                    @click="selectMatchingOption(question, option.id)"
                    @dragstart="onMatchingDragStart(option.id)"
                  >
                    <span class="mb-1 block text-xs font-semibold uppercase">
                      {{ matchingOptionDisplayLabel(option) }}
                    </span>
                    <span class="exercise-runtime-html block" v-html="option.answer" />
                    <span v-if="isMatchingOptionAssigned(question, option.id)" class="mt-1 block text-xs text-gray-500">
                      {{ t("Already matched") }}
                    </span>
                  </button>

                  <p class="text-xs text-gray-500">
                    {{ t("You can drag an option to an answer, or select an option and then click a target box.") }}
                  </p>
                </aside>
              </div>
            </div>

            <div v-else-if="isMatchingQuestion(question)" class="space-y-3">
              <div
                v-for="prompt in question.matching.prompts"
                :key="prompt.id"
                class="grid gap-3 rounded-lg border border-gray-20 p-3 md:grid-cols-[1fr_16rem] md:items-center"
              >
                <div class="exercise-runtime-html" v-html="prompt.answer" />
                <select
                  v-model="answers[question.id].matching[prompt.id]"
                  class="rounded border border-gray-30 px-3 py-2 text-sm"
                  :name="`question_${question.id}_matching_${prompt.id}`"
                >
                  <option value="">{{ t("Select") }}</option>
                  <option
                    v-for="option in question.matching.options"
                    :key="option.id"
                    :value="option.id"
                  >
                    {{ option.label }}. {{ displayText(option.answer) }}
                  </option>
                </select>
              </div>
            </div>

            <div v-else-if="isDraggableQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-gray-20 bg-gray-15 p-3 text-sm text-gray-700">
                {{ t("Put the items in the correct order.") }}
              </div>

              <div
                v-if="isDraggableHorizontal(question)"
                class="overflow-x-auto rounded-lg border border-gray-20 bg-white p-3"
              >
                <ol class="flex min-w-max items-stretch gap-3">
                  <li
                    v-for="(item, index) in draggableAnswerItems(question)"
                    :key="item.id"
                    class="flex w-64 shrink-0 flex-col gap-3 rounded-lg border border-gray-20 bg-white p-3 shadow-sm"
                    draggable="true"
                    @dragstart="onDraggableOrderDragStart(item.id)"
                    @dragover.prevent
                    @drop="onDraggableOrderDrop(question, item.id)"
                  >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                      <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                        {{ index + 1 }}
                      </span>
                      <span class="exercise-runtime-html min-w-0 flex-1" v-html="item.answer" />
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                      <button
                        type="button"
                        class="rounded border border-gray-30 px-2 py-1 text-xs text-gray-700 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="index === 0"
                        @click="moveDraggableItem(question, index, index - 1)"
                      >
                        {{ t("Move left") }}
                      </button>
                      <button
                        type="button"
                        class="rounded border border-gray-30 px-2 py-1 text-xs text-gray-700 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="index === draggableAnswerItems(question).length - 1"
                        @click="moveDraggableItem(question, index, index + 1)"
                      >
                        {{ t("Move right") }}
                      </button>
                    </div>
                  </li>
                </ol>
              </div>

              <ol v-else class="space-y-2">
                <li
                  v-for="(item, index) in draggableAnswerItems(question)"
                  :key="item.id"
                  class="rounded-lg border border-gray-20 bg-white p-3 shadow-sm"
                  draggable="true"
                  @dragstart="onDraggableOrderDragStart(item.id)"
                  @dragover.prevent
                  @drop="onDraggableOrderDrop(question, item.id)"
                >
                  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                      <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                        {{ index + 1 }}
                      </span>
                      <span class="exercise-runtime-html min-w-0 flex-1" v-html="item.answer" />
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                      <button
                        type="button"
                        class="rounded border border-gray-30 px-2 py-1 text-xs text-gray-700 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="index === 0"
                        @click="moveDraggableItem(question, index, index - 1)"
                      >
                        {{ t("Move up") }}
                      </button>
                      <button
                        type="button"
                        class="rounded border border-gray-30 px-2 py-1 text-xs text-gray-700 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="index === draggableAnswerItems(question).length - 1"
                        @click="moveDraggableItem(question, index, index + 1)"
                      >
                        {{ t("Move down") }}
                      </button>
                    </div>
                  </div>
                </li>
              </ol>

              <p class="text-xs text-gray-500">
                {{ isDraggableHorizontal(question)
                  ? t("You can drag items left or right, or use the move buttons to change the order.")
                  : t("You can drag items or use the move buttons to change the order.") }}
              </p>
            </div>

            <div v-else-if="isDropdownQuestion(question)" class="space-y-3">
              <select
                v-model="answers[question.id].dropdown"
                class="rounded border border-gray-30 px-3 py-2 text-sm"
                :name="`question_${question.id}_dropdown`"
              >
                <option value="">{{ t("Select") }}</option>
                <option
                  v-for="option in question.dropdown.options"
                  :key="option.id"
                  :value="option.id"
                >
                  {{ displayText(option.answer) }}
                </option>
              </select>
            </div>

            <div v-else-if="isCalculatedQuestion(question)" class="space-y-3">
              <div
                v-if="currentCalculatedVariation(question).text"
                class="exercise-runtime-html rounded-lg border border-gray-20 p-3 text-sm text-gray-800"
                v-html="currentCalculatedVariation(question).text"
              />
              <input
                v-model="answers[question.id].calculated"
                class="w-full rounded border border-gray-30 px-3 py-2 text-sm md:w-80"
                :name="`question_${question.id}_calculated`"
                type="text"
              />
            </div>

            <div v-else-if="isOpenQuestion(question)" class="space-y-2">
              <textarea
                v-model="answers[question.id].text"
                class="min-h-32 w-full rounded border border-gray-30 px-3 py-2 text-sm"
                :name="`question_${question.id}_text`"
              />
            </div>

            <div v-else-if="isUploadQuestion(question)" class="space-y-2">
              <input
                class="block w-full text-sm text-gray-700"
                :name="`question_${question.id}_file`"
                type="file"
                @change="onUploadAnswerFileChange(question, $event)"
              />
              <div
                v-if="answers[question.id]?.uploadFileName"
                class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
              >
                {{ t("Selected files") }}: {{ answers[question.id].uploadFileName }}
              </div>
              <div
                v-if="answers[question.id]?.uploadedFiles?.length"
                class="space-y-2 rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
              >
                <div class="font-semibold">{{ t("Upload file") }}</div>
                <div
                  v-for="file in answers[question.id].uploadedFiles"
                  :key="file.id || file.name"
                >
                  <a
                    v-if="file.url"
                    class="text-primary underline"
                    :href="file.url"
                    target="_blank"
                    rel="noopener"
                  >
                    {{ file.name || t("Upload file") }}
                  </a>
                  <span v-else>{{ file.name || t("Upload file") }}</span>
                </div>
              </div>
            </div>

            <div v-else-if="isOralQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-gray-20 bg-gray-10 p-3">
                <div class="mb-2 text-sm font-semibold text-gray-800">
                  {{ t("Record answer") }}
                </div>
                <AudioRecorder
                  :multiple="false"
                  :show-recorded-audios="false"
                  @recorded-audio="onOralRecorded(question, $event)"
                />
              </div>

              <div class="space-y-2 rounded-lg border border-gray-20 bg-white p-3">
                <div class="text-sm font-semibold text-gray-800">
                  {{ t("Or upload an audio file") }}
                </div>
                <input
                  class="block w-full text-sm text-gray-700"
                  :name="`question_${question.id}_audio`"
                  type="file"
                  accept=".wav,.ogg,audio/wav,audio/ogg"
                  @change="onOralFileChange(question, $event)"
                />
              </div>

              <audio
                v-if="answers[question.id]?.oralPreviewUrl"
                class="max-w-full"
                controls
                :src="answers[question.id].oralPreviewUrl"
              />

              <div
                v-if="answers[question.id]?.oralFileName"
                class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
              >
                {{ t("Selected audio") }}: {{ answers[question.id].oralFileName }}
              </div>
              <div
                v-if="answers[question.id]?.uploadedFiles?.length"
                class="space-y-2 rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
              >
                <div class="font-semibold">{{ t("Uploaded audio") }}</div>
                <div
                  v-for="file in answers[question.id].uploadedFiles"
                  :key="file.id || file.name"
                >
                  <a
                    v-if="file.url"
                    class="text-primary underline"
                    :href="file.url"
                    target="_blank"
                    rel="noopener"
                  >
                    {{ file.name || t("Uploaded audio") }}
                  </a>
                  <span v-else>{{ file.name || t("Uploaded audio") }}</span>
                </div>
              </div>
            </div>

            <div v-else-if="isOnlyofficeQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-info/30 bg-support-1 p-4 text-sm text-support-4">
                <div class="mb-2 font-semibold text-gray-90">
                  {{ t("Office document") }}
                </div>
                <p v-if="question.onlyoffice?.templateName" class="mb-3">
                  {{ t("Template") }}: {{ question.onlyoffice.templateName }}
                </p>
                <p class="mb-3">
                  {{ t("Complete the document in the editor below. The file will be attached to this attempt for teacher correction.") }}
                </p>
                <div class="flex flex-wrap gap-2">
                  <BaseButton
                    :disabled="answers[question.id]?.onlyofficePreparing || isSavingDraft"
                    :label="onlyofficeEditorUrl(question) ? t('Reload Office editor') : t('Prepare Office document')"
                    icon="onlyoffice"
                    size="small"
                    type="primary"
                    @click="prepareOnlyofficeDocument(question, true)"
                  />
                  <BaseButton
                    v-if="onlyofficeEditorUrl(question)"
                    :label="t('Open in new tab')"
                    icon="link-external"
                    size="small"
                    type="primary-text"
                    @click="openOnlyofficeDocumentInNewTab(question)"
                  />
                </div>
                <p
                  v-if="answers[question.id]?.onlyofficePreparing"
                  class="mt-3 text-xs text-gray-600"
                >
                  {{ t("Preparing Office document") }}
                </p>
                <p
                  v-if="answers[question.id]?.onlyofficeError"
                  class="mt-3 text-xs text-danger"
                >
                  {{ answers[question.id].onlyofficeError }}
                </p>
              </div>

              <div
                v-if="onlyofficeEditorUrl(question)"
                class="overflow-hidden rounded-lg border border-gray-20 bg-white shadow-sm"
              >
                <iframe
                  :key="onlyofficeEditorUrl(question)"
                  :src="onlyofficeEditorUrl(question)"
                  class="h-[72vh] min-h-[560px] w-full"
                  :title="question.onlyoffice?.templateName || t('Office document')"
                />
              </div>

              <div
                v-if="onlyofficeAttemptFiles(question).length"
                class="space-y-2 rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
              >
                <div class="font-semibold">{{ t("Submitted document") }}</div>
                <div
                  v-for="file in onlyofficeAttemptFiles(question)"
                  :key="file.id || file.name"
                >
                  <a
                    v-if="file.url"
                    class="text-primary underline"
                    :href="file.url"
                    target="_blank"
                    rel="noopener"
                  >
                    {{ file.name || t("Office document") }}
                  </a>
                  <span v-else>{{ file.name || t("Office document") }}</span>
                </div>
              </div>
            </div>

            <div v-else-if="isAnnotationQuestion(question)" class="space-y-3">
              <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div
                  v-if="question.annotation.imageUrl"
                  class="inline-block max-w-full rounded-lg border border-gray-20 bg-gray-10 p-2"
                >
                  <div class="relative inline-block max-w-full">
                    <img
                      class="max-h-[32rem] max-w-full cursor-crosshair object-contain"
                      :alt="question.annotation.imageName || t('Question image')"
                      :src="question.annotation.imageUrl"
                      @click="onAnnotationImageClick(question, $event)"
                      @load="onAnnotationImageLoad(question, $event)"
                    />
                    <svg
                      v-if="annotationImageReady(question)"
                      class="pointer-events-none absolute inset-0 h-full w-full"
                      :viewBox="annotationViewBox(question)"
                      preserveAspectRatio="none"
                    >
                      <polyline
                        v-for="(path, pathIndex) in annotationPaths(question)"
                        :key="`${question.id}-annotation-path-${pathIndex}`"
                        fill="none"
                        :points="annotationPolylinePoints(path)"
                        stroke="currentColor"
                        stroke-width="3"
                        class="text-primary"
                      />
                    </svg>
                    <span
                      v-for="(textAnnotation, textIndex) in annotationTexts(question)"
                      :key="`${question.id}-annotation-text-${textIndex}`"
                      class="pointer-events-none absolute -translate-x-1/2 -translate-y-1/2 rounded bg-white/90 px-2 py-1 text-xs font-semibold text-primary shadow"
                      :style="annotationPointStyle(question, textAnnotation)"
                    >
                      {{ textAnnotation.text }}
                    </span>
                  </div>
                </div>
                <div
                  v-else
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("No annotation image available") }}
                </div>

                <aside class="space-y-3 rounded-lg border border-gray-20 bg-white p-3">
                  <div class="text-sm font-semibold text-gray-90">
                    {{ t("Annotation tools") }}
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      class="rounded border px-3 py-2 text-sm font-semibold"
                      :class="answers[question.id]?.annotationMode === 'path' ? 'border-primary bg-primary/10 text-primary' : 'border-gray-30 text-gray-700'"
                      type="button"
                      @click="setAnnotationMode(question, 'path')"
                    >
                      {{ t("Add annotation path") }}
                    </button>
                    <button
                      class="rounded border px-3 py-2 text-sm font-semibold"
                      :class="answers[question.id]?.annotationMode === 'text' ? 'border-primary bg-primary/10 text-primary' : 'border-gray-30 text-gray-700'"
                      type="button"
                      @click="setAnnotationMode(question, 'text')"
                    >
                      {{ t("Add annotation text") }}
                    </button>
                  </div>

                  <div
                    v-if="answers[question.id]?.annotationMode === 'text'"
                    class="space-y-2"
                  >
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                      {{ t("Text to place") }}
                    </label>
                    <input
                      v-model="answers[question.id].annotationTextDraft"
                      class="w-full rounded border border-gray-30 px-3 py-2 text-sm"
                      :name="`question_${question.id}_annotation_text`"
                      type="text"
                    />
                    <p class="text-xs text-gray-500">
                      {{ t("Click the image to place the text annotation.") }}
                    </p>
                  </div>
                  <p
                    v-else
                    class="text-xs text-gray-500"
                  >
                    {{ t("Click the image to add path points. Use Start new path to separate paths.") }}
                  </p>

                  <div class="flex flex-wrap gap-2">
                    <button
                      class="rounded border border-gray-30 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-10"
                      type="button"
                      @click="startNewAnnotationPath(question)"
                    >
                      {{ t("Start new path") }}
                    </button>
                    <button
                      class="rounded border border-gray-30 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-10"
                      type="button"
                      @click="undoAnnotation(question)"
                    >
                      {{ t("Undo") }}
                    </button>
                    <button
                      class="rounded border border-danger/30 px-3 py-2 text-xs font-semibold text-danger hover:bg-danger/10"
                      type="button"
                      @click="clearAnnotation(question)"
                    >
                      {{ t("Clear") }}
                    </button>
                  </div>

                  <div class="space-y-2 text-xs text-gray-700">
                    <div class="font-semibold text-gray-90">
                      {{ t("Current annotations") }}
                    </div>
                    <div>{{ t("Path") }}: {{ annotationPaths(question).length }}</div>
                    <div>{{ t("Text") }}: {{ annotationTexts(question).length }}</div>
                  </div>
                </aside>
              </div>
            </div>

            <div v-else-if="isHotspotQuestion(question)" class="space-y-3">
              <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div
                  v-if="question.hotspot.imageUrl"
                  class="inline-block max-w-full rounded-lg border border-gray-20 bg-gray-10 p-2"
                >
                  <div class="relative inline-block max-w-full">
                    <img
                      class="max-h-[32rem] max-w-full cursor-crosshair object-contain"
                      :alt="question.hotspot.imageName || t('Question image')"
                      :src="question.hotspot.imageUrl"
                      @click="onHotspotImageClick(question, $event)"
                      @load="onHotspotImageLoad(question, $event)"
                    />
                    <svg
                      v-if="isHotspotDelineationQuestion(question) && hotspotPlacedPoints(question).length"
                      class="pointer-events-none absolute inset-0 h-full w-full text-primary"
                      :viewBox="hotspotImageViewBox(question)"
                    >
                      <polygon
                        v-if="hotspotPlacedPoints(question).length >= 3"
                        class="fill-primary/20 stroke-primary"
                        :points="hotspotDelineationSvgPoints(question)"
                        stroke-linejoin="round"
                        stroke-width="3"
                        vector-effect="non-scaling-stroke"
                      />
                      <polyline
                        v-else-if="hotspotPlacedPoints(question).length >= 2"
                        class="stroke-primary"
                        fill="none"
                        :points="hotspotDelineationSvgPoints(question)"
                        stroke-linejoin="round"
                        stroke-width="3"
                        vector-effect="non-scaling-stroke"
                      />
                    </svg>
                    <button
                      v-for="point in hotspotPlacedPoints(question)"
                      :key="`${question.id}-hotspot-point-${point.answerId || 0}-${point.index ?? point.label}`"
                      class="absolute flex h-7 w-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-primary text-xs font-bold text-white shadow"
                      :style="hotspotPointStyle(question, point)"
                      type="button"
                      :title="t('Remove point')"
                      @click.stop="removeHotspotPoint(question, point.answerId, point.index)"
                    >
                      {{ hotspotPointLabel(question, point) }}
                    </button>
                  </div>
                </div>
                <div
                  v-else
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("No hotspot image available") }}
                </div>

                <aside class="space-y-3 rounded-lg border border-gray-20 bg-white p-3">
                  <template v-if="isHotspotDelineationQuestion(question)">
                    <div class="text-sm font-semibold text-gray-90">
                      {{ t("Delineation") }}
                    </div>
                    <div class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4">
                      {{ t("Click the image to draw the delineation polygon.") }}
                      <span v-if="hotspotPlacedPoints(question).length">
                        {{ t("Click a marker to remove it.") }}
                      </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <button
                        class="rounded border border-gray-30 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-10"
                        type="button"
                        @click="undoHotspotDelineation(question)"
                      >
                        {{ t("Undo") }}
                      </button>
                      <button
                        class="rounded border border-danger/30 px-3 py-2 text-xs font-semibold text-danger hover:bg-danger/10"
                        type="button"
                        @click="clearHotspotDelineation(question)"
                      >
                        {{ t("Clear") }}
                      </button>
                    </div>
                    <div class="text-xs text-gray-70">
                      {{ t("Points") }}: {{ hotspotPlacedPoints(question).length }}
                    </div>
                  </template>
                  <template v-else>
                    <div class="text-sm font-semibold text-gray-90">
                      {{ t("Answers") }}
                    </div>
                    <div class="space-y-2">
                      <button
                        v-for="(zone, zoneIndex) in hotspotZones(question)"
                        :key="zone.id"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm transition hover:bg-gray-10"
                        :class="Number(answers[question.id].selectedHotspotAnswerId) === Number(zone.id) ? 'border-primary bg-primary/5 text-primary' : 'border-gray-20 text-gray-700'"
                        type="button"
                        @click="selectHotspotZone(question, zone.id)"
                      >
                        <div class="flex items-start gap-2">
                          <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                            {{ zone.position || zoneIndex + 1 }}
                          </span>
                          <span class="min-w-0 flex-1">
                            <span class="exercise-runtime-html block font-medium" v-html="zone.answer" />
                            <span
                              v-if="hotspotPointByAnswer(question, zone.id)"
                              class="mt-1 block text-xs text-success"
                            >
                              {{ t("Placed") }}
                            </span>
                          </span>
                        </div>
                      </button>
                    </div>
                    <div class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4">
                      {{ t("Select an answer, then click on the image to mark it.") }}
                      <span v-if="hotspotPlacedPoints(question).length">
                        {{ t("Click a marker to remove it.") }}
                      </span>
                    </div>
                  </template>
                </aside>
              </div>
            </div>

            <div v-else-if="isMediaQuestion(question)" class="space-y-3">
              <div
                v-if="question.content?.description || question.description"
                class="exercise-runtime-html rounded-lg border border-gray-20 bg-gray-10 p-4 text-gray-800"
                v-html="question.content?.description || question.description"
              />
              <div
                v-else
                class="rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
              >
                {{ t("Media question") }}
              </div>
            </div>

            <div v-else-if="isReadingQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-gray-20 p-3 text-sm text-gray-700">
                {{ t("Reading speed") }}: {{ t("%s words per minute", [question.reading.speed]) }}
              </div>
              <div
                class="exercise-runtime-html rounded-lg border border-gray-20 p-4 text-gray-800"
                v-html="question.reading.text || question.description"
              />
            </div>

            <div v-else-if="isPageBreak(question)" class="space-y-3">
              <div
                v-if="question.content?.description || question.description"
                class="exercise-runtime-html rounded-lg border border-gray-20 bg-gray-10 p-4 text-gray-800"
                v-html="question.content?.description || question.description"
              />
              <div
                v-else
                class="rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
              >
                {{ t("Page break") }}
              </div>
            </div>

            <div
              v-else
              class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800"
            >
              {{ t("This question type cannot be displayed in this player.") }}
            </div>
          </div>

          <div
            v-if="showReviewLaterOption(question)"
            class="mt-4 rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
          >
            <label class="flex items-center gap-2">
              <input
                v-model="answers[question.id].reviewLater"
                :disabled="isReviewFlagSaving"
                :name="`remind_list_${question.id}`"
                type="checkbox"
                @change="onReviewLaterChange(question)"
              />
              <span>{{ t("Revise question later") }}</span>
            </label>
          </div>

          <div
            v-if="directFeedbackForQuestion(question)"
            class="mt-4 space-y-3 rounded-lg border p-4 text-sm"
            :class="feedbackStatusClass(directFeedbackForQuestion(question))"
          >
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="font-semibold">
                  {{ t(directFeedbackForQuestion(question).title || "Feedback") }}
                </div>
                <div>
                  {{ t("Score") }}: {{ formatScore(directFeedbackForQuestion(question).score) }} / {{ formatScore(directFeedbackForQuestion(question).maxScore) }}
                </div>
              </div>
              <div class="flex flex-wrap gap-2">
                <BaseButton
                  :label="directFeedbackForQuestion(question).afterAction === 'finish' ? t('End test') : t('Proceed with the test')"
                  icon="check"
                  type="primary"
                  @click="proceedAfterFeedback(directFeedbackForQuestion(question))"
                />
              </div>
            </div>

            <div
              v-if="feedbackEntries(directFeedbackForQuestion(question)).length"
              class="space-y-2"
            >
              <div
                v-for="(entry, entryIndex) in feedbackEntries(directFeedbackForQuestion(question))"
                :key="`${question.id}-feedback-${entryIndex}`"
                class="rounded border border-gray-20 bg-white/80 p-3"
              >
                <div
                  v-if="entry.answer"
                  class="exercise-runtime-html font-medium text-gray-90"
                  v-html="entry.answer"
                />
                <div
                  v-if="entry.comment"
                  class="exercise-runtime-html mt-1 text-gray-700"
                  v-html="entry.comment"
                />
              </div>
            </div>
            <div v-else>
              {{ t("No detailed feedback is available for this question.") }}
            </div>
          </div>
        </article>

        <div
          v-if="requiresSavedAnswerConfirmation"
          class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
        >
          <label class="flex items-start gap-3">
            <input
              v-model="confirmedSavedAnswers"
              class="mt-1"
              name="confirm_saved_answers"
              type="checkbox"
            />
            <span>
              {{ t("I confirm that my answers were saved.") }}
              <span class="block text-xs">
                {{ t("Saved") }} {{ t("answers") }}: {{ savedQuestionIds.size }} / {{ visibleQuestionTotal }}
              </span>
            </span>
          </label>
        </div>

        <div
          v-if="showRuntimeNavigationControls"
          class="flex flex-wrap justify-between gap-2 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
        >
          <BaseButton
            v-if="showPreviousNavigationButton"
            :disabled="!canMovePrevious || isSavingAnswer || isQuestionTimeExpired || isAutoAdvancingTimedQuestion"
            :label="previousNavigationLabel"
            icon="back"
            type="secondary"
            @click="goToPreviousQuestion"
          />
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="!canManage && activeAttempt"
              :disabled="isSavingAnswer || isTimeExpired || isQuestionTimeExpired || isAutoAdvancingTimedQuestion || !visibleQuestions.some(isDraftSaveSupported)"
              :label="isSavingAnswer ? t('Saving') : t('Save draft')"
              icon="check"
              type="success"
              @click="saveVisibleAnswers()"
            />
            <BaseButton
              v-if="canMoveNext"
              :disabled="isSavingAnswer || isTimeExpired || isQuestionTimeExpired || isAutoAdvancingTimedQuestion"
              :label="nextNavigationLabel"
              type="primary"
              @click="goToNextQuestion"
            />
            <BaseButton
              v-if="isReviewingMarkedQuestions && canFinishCurrentPage"
              :disabled="isSavingAnswer || isQuestionTimeExpired || isAutoAdvancingTimedQuestion"
              :label="t('Questions to be reviewed')"
              icon="back"
              type="primary"
              @click="returnToReviewReminder"
            />
            <BaseButton
              v-if="!isReviewingMarkedQuestions && !canManage && activeAttempt && canFinishCurrentPage"
              :disabled="!canSubmit || !canFinishWithConfirmation || isSavingAnswer || isFinishingAttempt || isAutoFinishingExpiredAttempt || isQuestionTimeExpired || isAutoAdvancingTimedQuestion"
              :label="finishButtonLabel"
              icon="check"
              type="primary"
              @click="finishAttempt"
            />
          </div>
        </div>
      </form>
    </template>

    <BaseDialog
      v-if="feedbackDialog"
      v-model:is-visible="isFeedbackDialogVisible"
      :show-close-button="false"
      :title="feedbackDialogTitle"
      header-icon="information"
    >
      <div class="space-y-3 text-sm">
        <div
          class="rounded-lg border p-4"
          :class="feedbackStatusClass(feedbackDialog)"
        >
          <div class="font-semibold">
            {{ t(feedbackDialog.title || "Feedback") }}
          </div>
          <div>
            {{ t("Score") }}: {{ formatScore(feedbackDialog.score) }} / {{ formatScore(feedbackDialog.maxScore) }}
          </div>
        </div>

        <div
          v-if="feedbackEntries(feedbackDialog).length"
          class="space-y-2"
        >
          <div
            v-for="(entry, entryIndex) in feedbackEntries(feedbackDialog)"
            :key="`popup-feedback-${entryIndex}`"
            class="rounded border border-gray-20 bg-white p-3"
          >
            <div
              v-if="entry.answer"
              class="exercise-runtime-html font-medium text-gray-90"
              v-html="entry.answer"
            />
            <div
              v-if="entry.comment"
              class="exercise-runtime-html mt-1 text-gray-700"
              v-html="entry.comment"
            />
          </div>
        </div>
        <div v-else>
          {{ t("No detailed feedback is available for this question.") }}
        </div>
      </div>

      <template #footer>
        <BaseButton
          :label="feedbackDialog.afterAction === 'finish' ? t('End test') : t('Proceed with the test')"
          icon="check"
          type="primary"
          @click="proceedAfterFeedback(feedbackDialog)"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isCategoryReminderDialogVisible"
      :show-close-button="false"
      :title="displayText(categoryReminder?.categoryTitle, t('Question category'))"
      header-icon="alert-circle-outline"
    >
      <div class="space-y-4 text-sm text-gray-700">
        <p>
          {{ t("You finished the questions related to this question category, it is your last chance to go back and revise those questions.") }}
        </p>
        <p v-if="categoryReminder?.categoryDescription" class="rounded-lg bg-gray-10 p-3">
          {{ categoryReminder.categoryDescription }}
        </p>
        <div v-if="categoryReminderError" class="text-danger">
          {{ categoryReminderError }}
        </div>
        <div v-if="isReviewAnswersEnabled && categoryReminderQuestions.length > 0" class="space-y-2">
          <label
            v-for="question in categoryReminderQuestions"
            :key="`category-reminder-question-${question.id}`"
            class="flex items-start gap-3 rounded-lg border border-gray-20 p-3"
          >
            <input
              :checked="reviewQuestionIds.has(Number(question.id))"
              class="mt-1"
              :disabled="isCategoryReminderProceeding"
              :name="`category_remind_list_${question.id}`"
              type="checkbox"
              @change="toggleReviewQuestion(question.id, $event.target.checked)"
            />
            <span class="min-w-0 flex-1">
              <span class="block font-semibold text-gray-90">
                {{ question.position }}. {{ displayText(question.title, t("Untitled")) }}
              </span>
              <span
                v-if="!savedQuestionIds.has(Number(question.id))"
                class="mt-1 inline-block rounded bg-danger/10 px-2 py-0.5 text-xs text-danger"
              >
                {{ t("Questions without answer") }}
              </span>
            </span>
          </label>
        </div>
      </div>

      <template #footer>
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :disabled="isCategoryReminderProceeding"
            :label="t('Go back')"
            icon="back"
            type="plain"
            @click="closeCategoryReminder"
          />
          <BaseButton
            v-if="isReviewAnswersEnabled"
            :disabled="isCategoryReminderProceeding || selectedCategoryReminderQuestionIds.length === 0"
            :label="t('Review selected questions')"
            icon="playlist-check"
            type="primary"
            @click="reviewSelectedCategoryQuestions"
          />
          <BaseButton
            v-if="isReviewAnswersEnabled"
            :disabled="isCategoryReminderProceeding"
            :label="t('Select all')"
            icon="checkbox-marked-outline"
            type="plain"
            @click="setCategoryReminderQuestions(true)"
          />
          <BaseButton
            v-if="isReviewAnswersEnabled"
            :disabled="isCategoryReminderProceeding"
            :label="t('Unselect all')"
            icon="checkbox-blank-outline"
            type="plain"
            @click="setCategoryReminderQuestions(false)"
          />
          <BaseButton
            :disabled="isCategoryReminderProceeding"
            :label="categoryReminder?.lastCategory ? t('End test') : t('Proceed with the test')"
            icon="check"
            type="primary"
            @click="confirmCategoryReminder"
          />
        </div>
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isZoomDialogVisible"
      :show-close-button="false"
      :title="zoomImageAlt || t('Image')"
      header-icon="file-image"
    >
      <div class="flex justify-center rounded-lg bg-gray-10 p-3">
        <img
          v-if="zoomImageSrc"
          class="max-h-[80vh] max-w-full object-contain"
          :alt="zoomImageAlt || t('Image')"
          :src="zoomImageSrc"
        />
      </div>

      <template #footer>
        <BaseButton
          :label="t('Close')"
          type="plain"
          @click="isZoomDialogVisible = false"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import AudioRecorder from "../../components/AudioRecorder.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const title = ref("")
const description = ref("")
const settings = ref({})
const questions = ref([])
const legacyUrls = ref({})
const questionCount = ref(0)
const totalScore = ref(0)
const canManage = ref(false)
const canStartAttempt = ref(false)
const canSubmit = ref(false)
const usesLegacySubmit = ref(true)
const answers = ref({})
const activeAttempt = ref(null)
const currentQuestionIndex = ref(0)
const isStartingAttempt = ref(false)
const attemptMessage = ref("")
const attemptError = ref("")
const isSavingAnswer = ref(false)
const answerSaveError = ref("")
const answerSaveMessage = ref("")
const isFinishingAttempt = ref(false)
const finishError = ref("")
const finishMessage = ref("")
const savedQuestionIds = ref(new Set())
const reviewQuestionIds = ref(new Set())
const isReviewReminderVisible = ref(false)
const reviewQueue = ref([])
const reviewQueueIndex = ref(0)
const isReviewFlagSaving = ref(false)
const reviewFlagError = ref("")
const draggedMatchingOptionId = ref(null)
const selectedMatchingOptions = ref({})
const draggedDraggableItemId = ref(null)
const countdownRemainingSeconds = ref(null)
const countdownTimer = ref(null)
const questionCountdownRemainingSeconds = ref(null)
const questionCountdownTimer = ref(null)
const questionTimerStartedAt = ref(null)
const questionTimerSavedSeconds = ref({})
const isTimeExpired = ref(false)
const isQuestionTimeExpired = ref(false)
const isAutoFinishingExpiredAttempt = ref(false)
const isAutoAdvancingTimedQuestion = ref(false)
const directFeedbackByQuestion = ref({})
const feedbackDialog = ref(null)
const isFeedbackDialogVisible = ref(false)
const feedbackShownOnLastSave = ref(false)
const categoryReminder = ref(null)
const isCategoryReminderDialogVisible = ref(false)
const isCategoryReminderProceeding = ref(false)
const categoryReminderError = ref("")
const confirmedSavedAnswers = ref(false)
const isZoomDialogVisible = ref(false)
const zoomImageSrc = ref("")
const zoomImageAlt = ref("")
let copyPasteCleanup = null
let keepAliveTimer = null

const questionMap = computed(() => new Map(questions.value.map((question) => [Number(question.id), question])))

const runtimePages = computed(() => {
  const pages = settings.value?.runtimePages
  if (!Array.isArray(pages)) {
    return []
  }

  return normalizeRuntimePages(pages)
})

const usesPagedNavigation = computed(() => {
  if (runtimePages.value.length > 0 && (settings.value.effectiveOneQuestionPerPage || settings.value.usesStructuralPages)) {
    return true
  }

  return true === settings.value.oneQuestionPerPage
})

const showLegacyRuntimeFallback = computed(() => {
  return !canManage.value
    && Boolean(legacyUrls.value?.overview)
    && (true === settings.value.requiresLegacyRuntime || (Boolean(activeAttempt.value) && true === usesLegacySubmit.value))
})

const currentRuntimePage = computed(() => {
  if (isReviewingMarkedQuestions.value) {
    return null
  }

  if (runtimePages.value.length === 0) {
    return null
  }

  return runtimePages.value[Math.min(Math.max(0, currentQuestionIndex.value), runtimePages.value.length - 1)] || null
})

const visibleQuestions = computed(() => {
  if (isReviewingMarkedQuestions.value) {
    const questionId = Number(reviewQueue.value[reviewQueueIndex.value] || 0)
    const question = questionMap.value.get(questionId)

    return question ? [question] : []
  }

  if (currentRuntimePage.value && usesPagedNavigation.value) {
    return (currentRuntimePage.value.questionIds || [])
      .map((questionId) => questionMap.value.get(Number(questionId)))
      .filter(Boolean)
  }

  if (!settings.value.oneQuestionPerPage) {
    return questions.value.filter((question) => !isStructuralQuestion(question))
  }

  const question = questions.value.filter((item) => !isStructuralQuestion(item))[currentQuestionIndex.value]

  return question ? [question] : []
})

const visibleQuestionTotal = computed(() => answerableQuestions.value.length)
const isImmediateFeedbackRuntime = computed(() => [1, 3].includes(Number(settings.value.feedbackType || 0)))
const isReviewAnswersEnabled = computed(() => !isImmediateFeedbackRuntime.value && Number(settings.value.reviewAnswers || 0) > 0)
const isCheckAnswersBeforeFinishEnabled = computed(() => !isImmediateFeedbackRuntime.value && true === settings.value.checkAllAnswersBeforeEndTest)
const isFinalAnswerChecklistEnabled = computed(() => !isImmediateFeedbackRuntime.value && (isReviewAnswersEnabled.value || isCheckAnswersBeforeFinishEnabled.value))
const isReviewingMarkedQuestions = computed(() => reviewQueue.value.length > 0)
const showReviewReminderScreen = computed(() => {
  return !canManage.value
    && Boolean(activeAttempt.value?.attemptId)
    && isFinalAnswerChecklistEnabled.value
    && isReviewReminderVisible.value
    && !isReviewingMarkedQuestions.value
})
const navigationTotal = computed(() => {
  if (isReviewingMarkedQuestions.value) {
    return Math.max(1, reviewQueue.value.length)
  }

  return usesPagedNavigation.value ? Math.max(1, runtimePages.value.length || visibleQuestionTotal.value) : visibleQuestionTotal.value
})
const previousNavigationAllowed = computed(() => !settings.value.preventBackwards && true !== settings.value.blockCategoryQuestions && settings.value.showPreviousButton !== false)
const showPreviousNavigationButton = computed(() => isReviewingMarkedQuestions.value || (usesPagedNavigation.value && previousNavigationAllowed.value))
const canMovePrevious = computed(() => isReviewingMarkedQuestions.value ? reviewQueueIndex.value > 0 : showPreviousNavigationButton.value && currentQuestionIndex.value > 0)
const canMoveNext = computed(() => isReviewingMarkedQuestions.value ? reviewQueueIndex.value < reviewQueue.value.length - 1 : usesPagedNavigation.value && currentQuestionIndex.value < navigationTotal.value - 1)
const canFinishCurrentPage = computed(() => !usesPagedNavigation.value || !canMoveNext.value)
const currentTimedQuestion = computed(() => {
  if (!activeAttempt.value || canManage.value || true !== settings.value.allowTimePerQuestion) {
    return null
  }

  if (!usesPagedNavigation.value || visibleQuestions.value.length !== 1) {
    return null
  }

  const question = visibleQuestions.value.find((item) => !isStructuralQuestion(item))
  const duration = Number(question?.duration || 0)

  return duration > 0 ? question : null
})
const currentTimedQuestionId = computed(() => Number(currentTimedQuestion.value?.id || 0))
const hasGlobalTimeControl = computed(() => null !== countdownRemainingSeconds.value && undefined !== countdownRemainingSeconds.value)
const hasQuestionTimeControl = computed(() => null !== questionCountdownRemainingSeconds.value && undefined !== questionCountdownRemainingSeconds.value)
const hasTimeControl = computed(() => hasGlobalTimeControl.value || hasQuestionTimeControl.value)
const displayedRemainingSeconds = computed(() => hasQuestionTimeControl.value ? questionCountdownRemainingSeconds.value : countdownRemainingSeconds.value)
const isDisplayedTimeExpired = computed(() => isTimeExpired.value || isQuestionTimeExpired.value)
const timeControlLabel = computed(() => hasQuestionTimeControl.value ? t("Question time left") : t("Time left"))
const answerableQuestions = computed(() => questions.value.filter((question) => !isStructuralQuestion(question)))
const imageZoomEnabled = computed(() => {
  const origin = String(getQueryValue(route.query.origin) || "").toLowerCase()

  return true === settings.value.imageZoomEnabled && !["embeddable", "mobileapp"].includes(origin)
})
const glossaryTerms = computed(() => {
  const terms = settings.value?.glossary?.terms

  return Array.isArray(terms) ? terms : []
})
const glossaryEnabled = computed(() => true === settings.value?.glossary?.enabled && glossaryTerms.value.length > 0)
const currentNavigationIndex = computed(() => {
  const index = isReviewingMarkedQuestions.value ? reviewQueueIndex.value : currentQuestionIndex.value

  return Math.min(Math.max(0, index), Math.max(0, navigationTotal.value - 1))
})
const progressLabel = computed(() => {
  if (usesPagedNavigation.value && currentRuntimePage.value && (settings.value.usesStructuralPages || visibleQuestions.value.length > 1)) {
    return `${t("Page")} ${currentNavigationIndex.value + 1} / ${navigationTotal.value}`
  }

  return `${t("Question")} ${currentNavigationIndex.value + 1} / ${navigationTotal.value}`
})
const currentAttemptLabel = computed(() => {
  const attemptNumber = Number(activeAttempt.value?.attemptNumber || 0)
  const maxAttempt = Number(settings.value.maxAttempt || 0)

  if (attemptNumber > 0 && maxAttempt > 0) {
    return `${t("Attempt")} ${attemptNumber} / ${maxAttempt}`
  }

  if (attemptNumber > 0) {
    return `${t("Attempt")} ${attemptNumber}`
  }

  return t("Current attempt")
})
const currentCategoryLabel = computed(() => {
  if (true !== settings.value.blockCategoryQuestions) {
    return ""
  }

  const question = visibleQuestions.value.find((item) => !isStructuralQuestion(item))
  return displayText(question?.primaryCategoryTitle || "")
})
const previousNavigationLabel = computed(() => isReviewingMarkedQuestions.value || !(settings.value.usesStructuralPages || visibleQuestions.value.length > 1) ? t("Previous question") : t("Previous page"))
const nextNavigationLabel = computed(() => isReviewingMarkedQuestions.value || !(settings.value.usesStructuralPages || visibleQuestions.value.length > 1) ? t("Next question") : t("Next page"))
const finishButtonLabel = computed(() => {
  if (isFinishingAttempt.value) {
    return t("Finishing")
  }

  return isReviewAnswersEnabled.value ? t("Review my answers") : t("Finish test")
})
const selectedReviewQuestionIds = computed(() => Array.from(reviewQuestionIds.value).map(Number).filter((questionId) => questionId > 0))
const categoryReminderQuestions = computed(() => {
  const ids = Array.isArray(categoryReminder.value?.questionIds) ? categoryReminder.value.questionIds.map(Number).filter((id) => id > 0) : []

  return ids
    .map((questionId) => questionMap.value.get(questionId))
    .filter(Boolean)
    .map((question, index) => ({
      ...question,
      position: Number(question.position || index + 1),
    }))
})
const selectedCategoryReminderQuestionIds = computed(() => categoryReminderQuestions.value
  .map((question) => Number(question.id || 0))
  .filter((questionId) => questionId > 0 && reviewQuestionIds.value.has(questionId)))
const reviewQuestionList = computed(() => answerableQuestions.value.map((question, index) => ({
  ...question,
  position: Number(question.position || index + 1),
  isAnswered: savedQuestionIds.value.has(Number(question.id || 0)),
  isMarked: reviewQuestionIds.value.has(Number(question.id || 0)),
})))
const firstUnansweredReviewQuestionId = computed(() => {
  const question = reviewQuestionList.value.find((item) => !item.isAnswered)

  return Number(question?.id || 0)
})
const reviewReminderTitle = computed(() => {
  return isReviewAnswersEnabled.value ? t("Questions to be reviewed") : t("Review my answers")
})
const feedbackDialogTitle = computed(() => t(feedbackDialog.value?.title || "Feedback"))
const hasVisibleDirectFeedback = computed(() => {
  return visibleQuestions.value.some((question) => Boolean(directFeedbackForQuestion(question)))
})
const showRuntimeNavigationControls = computed(() => !hasVisibleDirectFeedback.value)
const requiresSavedAnswerConfirmation = computed(() => {
  return !isImmediateFeedbackRuntime.value && !canManage.value && Boolean(activeAttempt.value?.attemptId) && true === settings.value.confirmSavedAnswers
})
const canFinishWithConfirmation = computed(() => {
  return !requiresSavedAnswerConfirmation.value || true === confirmedSavedAnswers.value
})
const legacyRuntimeReasons = computed(() => {
  return Array.isArray(settings.value.legacyRuntimeReasons) ? settings.value.legacyRuntimeReasons : []
})

watch(navigationTotal, (total) => {
  const safeTotal = Number(total || 0)
  if (safeTotal <= 0) {
    currentQuestionIndex.value = 0
    return
  }

  if (currentQuestionIndex.value > safeTotal - 1) {
    currentQuestionIndex.value = safeTotal - 1
  }
})

function isQuestionMarkedForReview(questionId) {
  return reviewQuestionIds.value.has(Number(questionId || 0))
}

function showReviewLaterOption(question) {
  return !canManage.value
    && Boolean(activeAttempt.value?.attemptId)
    && isReviewAnswersEnabled.value
    && !isStructuralQuestion(question)
}

function syncReviewQuestionIds(questionIds = []) {
  if (!Array.isArray(questionIds)) {
    reviewQuestionIds.value = new Set()
    return
  }

  reviewQuestionIds.value = new Set(questionIds.map(Number).filter((questionId) => questionId > 0))
  syncReviewLaterAnswerState()
}

function syncReviewQuestionIdsFromAttempt(attempt) {
  syncReviewQuestionIds(attempt?.reviewQuestionIds || [])
}

function syncReviewQuestionIdsFromResponse(response) {
  if (Array.isArray(response?.reviewQuestionIds)) {
    syncReviewQuestionIds(response.reviewQuestionIds)
  }
}

function syncReviewLaterAnswerState() {
  for (const [questionId, questionAnswer] of Object.entries(answers.value || {})) {
    if (questionAnswer && typeof questionAnswer === "object") {
      questionAnswer.reviewLater = reviewQuestionIds.value.has(Number(questionId))
    }
  }
}

function setQuestionReviewState(questionId, checked) {
  const safeQuestionId = Number(questionId || 0)
  if (safeQuestionId <= 0) {
    return
  }

  const nextReviewQuestionIds = new Set(reviewQuestionIds.value)
  if (checked) {
    nextReviewQuestionIds.add(safeQuestionId)
  } else {
    nextReviewQuestionIds.delete(safeQuestionId)
  }

  reviewQuestionIds.value = nextReviewQuestionIds
  if (answers.value[safeQuestionId]) {
    answers.value[safeQuestionId].reviewLater = checked
  }
}

async function toggleReviewQuestion(questionId, checked) {
  setQuestionReviewState(questionId, checked)
  await saveQuestionReviewLater(questionId, checked)
}

async function onReviewLaterChange(question) {
  const questionId = Number(question?.id || 0)
  const checked = true === answers.value[questionId]?.reviewLater
  setQuestionReviewState(questionId, checked)
  await saveQuestionReviewLater(questionId, checked)
}

async function saveQuestionReviewLater(questionId, checked) {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  const safeQuestionId = Number(questionId || 0)

  if (!exerciseId || !attemptId || safeQuestionId <= 0 || !isReviewAnswersEnabled.value) {
    return
  }

  isReviewFlagSaving.value = true
  reviewFlagError.value = ""

  try {
    const response = await exerciseService.saveExerciseRuntimeAnswer(
      {
        exerciseId,
        attemptId,
        questionId: safeQuestionId,
        reviewLater: checked,
        reviewLaterOnly: true,
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

    if (!response?.success) {
      throw new Error(response?.message || "Could not save draft answer")
    }

    syncReviewQuestionIdsFromResponse(response)
  } catch (error) {
    console.error("Error saving exercise review flag", error)
    setQuestionReviewState(safeQuestionId, !checked)
    reviewFlagError.value = t("Could not save draft answer")
  } finally {
    isReviewFlagSaving.value = false
  }
}

async function setAllReviewQuestions(checked) {
  if (!activeAttempt.value?.attemptId || isReviewFlagSaving.value) {
    return
  }

  isReviewFlagSaving.value = true
  reviewFlagError.value = ""

  const previousQuestionIds = new Set(reviewQuestionIds.value)

  try {
    for (const question of answerableQuestions.value) {
      const questionId = Number(question.id || 0)
      if (questionId <= 0) {
        continue
      }

      setQuestionReviewState(questionId, checked)
      const exerciseId = getExerciseId()
      const attemptId = Number(activeAttempt.value?.attemptId || 0)
      const response = await exerciseService.saveExerciseRuntimeAnswer(
        {
          exerciseId,
          attemptId,
          questionId,
          reviewLater: checked,
          reviewLaterOnly: true,
        },
        getContextParams(),
        exerciseId,
        attemptId,
      )

      if (!response?.success) {
        throw new Error(response?.message || "Could not save draft answer")
      }

      syncReviewQuestionIdsFromResponse(response)
    }
  } catch (error) {
    console.error("Error saving exercise review list", error)
    reviewQuestionIds.value = previousQuestionIds
    syncReviewLaterAnswerState()
    reviewFlagError.value = t("Could not save draft answer")
  } finally {
    isReviewFlagSaving.value = false
  }
}

function showCategoryReminder(reminder) {
  categoryReminder.value = reminder || null
  categoryReminderError.value = ""
  isCategoryReminderDialogVisible.value = true
  feedbackShownOnLastSave.value = true
}

function closeCategoryReminder() {
  isCategoryReminderDialogVisible.value = false
  categoryReminder.value = null
  categoryReminderError.value = ""
  syncQuestionCountdown()
}

function setCategoryReminderQuestions(checked) {
  const nextReviewQuestionIds = new Set(reviewQuestionIds.value)
  categoryReminderQuestions.value.forEach((question) => {
    const questionId = Number(question.id || 0)
    if (questionId <= 0) {
      return
    }

    if (checked) {
      nextReviewQuestionIds.add(questionId)
    } else {
      nextReviewQuestionIds.delete(questionId)
    }
  })
  reviewQuestionIds.value = nextReviewQuestionIds
}

function reviewSelectedCategoryQuestions() {
  const orderedSelectedIds = categoryReminderQuestions.value
    .map((question) => Number(question.id || 0))
    .filter((questionId) => questionId > 0 && reviewQuestionIds.value.has(questionId))

  if (orderedSelectedIds.length === 0) {
    return
  }

  reviewQueue.value = orderedSelectedIds
  reviewQueueIndex.value = 0
  isCategoryReminderDialogVisible.value = false
  categoryReminder.value = null
  setCurrentQuestionById(orderedSelectedIds[0])
  syncQuestionCountdown()
}

async function confirmCategoryReminder() {
  const reminder = categoryReminder.value || {}
  const action = reminder.lastCategory ? "finish" : (reminder.afterAction || "next")
  const question = visibleQuestions.value.find(isDraftSaveSupported)
  if (!question) {
    closeCategoryReminder()
    return
  }

  isCategoryReminderProceeding.value = true
  categoryReminderError.value = ""

  try {
    feedbackShownOnLastSave.value = false
    await saveQuestionDraftAnswer(question, action, { confirmCategory: true })
    isCategoryReminderDialogVisible.value = false
    categoryReminder.value = null

    if (feedbackShownOnLastSave.value) {
      return
    }

    if (action === "finish" || reminder.lastCategory || !canMoveNext.value) {
      await finishAttempt({ skipDraftSave: true, skipReviewAnswers: true, ignoreFeedback: true })
      return
    }

    currentQuestionIndex.value += 1
    syncQuestionCountdown()
  } catch (error) {
    console.error("Error confirming exercise category reminder", error)
    categoryReminderError.value = t("Could not save draft answer")
  } finally {
    isCategoryReminderProceeding.value = false
  }
}

function showReviewReminder() {
  if (!isFinalAnswerChecklistEnabled.value || canManage.value || !activeAttempt.value?.attemptId) {
    return false
  }

  isReviewReminderVisible.value = true
  reviewQueue.value = []
  reviewQueueIndex.value = 0

  return true
}

function reviewSelectedQuestions() {
  const orderedSelectedIds = answerableQuestions.value
    .map((question) => Number(question.id || 0))
    .filter((questionId) => reviewQuestionIds.value.has(questionId))

  if (orderedSelectedIds.length === 0) {
    return
  }

  reviewQueue.value = orderedSelectedIds
  reviewQueueIndex.value = 0
  isReviewReminderVisible.value = false
  setCurrentQuestionById(orderedSelectedIds[0])
  syncQuestionCountdown()
}

function returnToReviewReminder() {
  reviewQueue.value = []
  reviewQueueIndex.value = 0
  isReviewReminderVisible.value = true
  syncQuestionCountdown()
}

function returnToFirstUnansweredQuestion() {
  const questionId = firstUnansweredReviewQuestionId.value
  if (questionId <= 0) {
    return
  }

  reviewQueue.value = []
  reviewQueueIndex.value = 0
  isReviewReminderVisible.value = false
  setCurrentQuestionById(questionId)
  syncQuestionCountdown()
}

function setCurrentQuestionById(questionId) {
  const navigationIndex = findNavigationIndexForQuestion(questionId)
  if (navigationIndex >= 0) {
    currentQuestionIndex.value = navigationIndex
  }
}

function findNavigationIndexForQuestion(questionId) {
  const safeQuestionId = Number(questionId || 0)
  if (safeQuestionId <= 0) {
    return -1
  }

  if (runtimePages.value.length > 0) {
    return runtimePages.value.findIndex((page) => (page.questionIds || []).map(Number).includes(safeQuestionId))
  }

  return answerableQuestions.value.findIndex((question) => Number(question.id || 0) === safeQuestionId)
}

function normalizeRuntimePages(pages = []) {
  const normalizedPages = []

  for (const page of pages) {
    if (!page || typeof page !== "object") {
      continue
    }

    const questionIds = Array.isArray(page.questionIds)
      ? page.questionIds
        .map((questionId) => Number(questionId || 0))
        .filter((questionId) => questionId > 0 && questionMap.value.has(questionId) && !isStructuralQuestion(questionMap.value.get(questionId)))
      : []

    if (questionIds.length > 0 || hasRuntimePageContent(page)) {
      normalizedPages.push({
        ...page,
        questionIds,
      })
    }
  }

  return normalizedPages
}

function hasRuntimePageContent(page = {}) {
  const media = page.media || null
  const pageBreak = page.pageBreak || null

  return Boolean(
    nonEmptyText(media?.title)
    || nonEmptyText(media?.description)
    || nonEmptyText(media?.content?.description)
    || nonEmptyText(pageBreak?.title)
    || nonEmptyText(pageBreak?.description)
    || nonEmptyText(pageBreak?.content?.description)
  )
}

function nonEmptyText(value) {
  return typeof value === "string" && value.trim() !== ""
}


function syncCountdownFromAttempt(attempt) {
  stopCountdownTimer()
  isTimeExpired.value = false
  isAutoFinishingExpiredAttempt.value = false

  if (!attempt?.expiredAt) {
    countdownRemainingSeconds.value = null
    return
  }

  updateCountdown(attempt.expiredAt)
  countdownTimer.value = window.setInterval(() => updateCountdown(attempt.expiredAt), 1000)
}

function stopCountdownTimer() {
  if (countdownTimer.value) {
    window.clearInterval(countdownTimer.value)
    countdownTimer.value = null
  }
}

function updateCountdown(expiredAt) {
  const expiresAt = new Date(expiredAt).getTime()
  if (Number.isNaN(expiresAt)) {
    countdownRemainingSeconds.value = null
    return
  }

  const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000))
  countdownRemainingSeconds.value = remaining

  if (remaining <= 0 && !canManage.value && activeAttempt.value?.status === "incomplete") {
    handleExpiredTimeLimit()
  }
}

async function handleExpiredTimeLimit() {
  if (isAutoFinishingExpiredAttempt.value || isFinishingAttempt.value) {
    return
  }

  isTimeExpired.value = true
  isAutoFinishingExpiredAttempt.value = true
  attemptMessage.value = t("Time limit reached. Finishing the attempt.")

  await finishAttempt({ skipDraftSave: true, expiredByTimer: true })
}

function syncQuestionCountdown() {
  stopQuestionCountdownTimer()
  isQuestionTimeExpired.value = false
  isAutoAdvancingTimedQuestion.value = false

  const question = currentTimedQuestion.value
  if (!question || activeAttempt.value?.status !== "incomplete") {
    questionCountdownRemainingSeconds.value = null
    questionTimerStartedAt.value = null
    return
  }

  const questionId = Number(question.id || 0)
  questionTimerSavedSeconds.value = {
    ...questionTimerSavedSeconds.value,
    [questionId]: getSavedQuestionSecondsSpent(questionId),
  }
  questionTimerStartedAt.value = Date.now()
  updateQuestionCountdown()
  questionCountdownTimer.value = window.setInterval(updateQuestionCountdown, 1000)
}

function stopQuestionCountdownTimer() {
  if (questionCountdownTimer.value) {
    window.clearInterval(questionCountdownTimer.value)
    questionCountdownTimer.value = null
  }
}

function updateQuestionCountdown() {
  const question = currentTimedQuestion.value
  if (!question || activeAttempt.value?.status !== "incomplete") {
    questionCountdownRemainingSeconds.value = null
    return
  }

  const duration = Number(question.duration || 0)
  if (duration <= 0) {
    questionCountdownRemainingSeconds.value = null
    return
  }

  const remaining = Math.max(0, duration - getQuestionSecondsSpent(question))
  questionCountdownRemainingSeconds.value = remaining

  if (remaining <= 0) {
    handleQuestionTimeLimit()
  }
}

async function handleQuestionTimeLimit() {
  if (isAutoAdvancingTimedQuestion.value || isSavingAnswer.value || isFinishingAttempt.value) {
    return
  }

  isQuestionTimeExpired.value = true
  isAutoAdvancingTimedQuestion.value = true
  answerSaveMessage.value = t("Question time reached. Saving your answer.")

  const saved = await saveVisibleAnswers({ afterFeedback: canMoveNext.value ? "next" : "finish" })
  if (!saved || feedbackShownOnLastSave.value) {
    isAutoAdvancingTimedQuestion.value = false
    return
  }

  if (canMoveNext.value) {
    currentQuestionIndex.value += 1
    isAutoAdvancingTimedQuestion.value = false
    syncQuestionCountdown()
    return
  }

  await finishAttempt({ skipDraftSave: true, expiredByTimer: true })
}

function getSavedQuestionSecondsSpent(questionId) {
  const rows = activeAttempt.value?.savedAnswers?.[questionId] || activeAttempt.value?.savedAnswers?.[String(questionId)] || []
  if (!Array.isArray(rows)) {
    return 0
  }

  return rows.reduce((maxSeconds, row) => Math.max(maxSeconds, Number(row?.secondsSpent || 0)), 0)
}

function getQuestionSecondsSpent(question) {
  const questionId = Number(question?.id || 0)
  if (questionId <= 0) {
    return 0
  }

  const savedSeconds = Number(questionTimerSavedSeconds.value[questionId] ?? getSavedQuestionSecondsSpent(questionId))
  if (questionId !== currentTimedQuestionId.value || !questionTimerStartedAt.value) {
    return savedSeconds
  }

  return savedSeconds + Math.max(0, Math.floor((Date.now() - questionTimerStartedAt.value) / 1000))
}

function rememberQuestionSecondsSpent(question) {
  const questionId = Number(question?.id || 0)
  if (questionId <= 0) {
    return
  }

  questionTimerSavedSeconds.value = {
    ...questionTimerSavedSeconds.value,
    [questionId]: getQuestionSecondsSpent(question),
  }

  if (questionId === currentTimedQuestionId.value) {
    questionTimerStartedAt.value = Date.now()
  }
}

function getQueryValue(value) {
  const normalizedValue = Array.isArray(value) ? value[0] : value

  if (typeof normalizedValue === "string" && normalizedValue.includes("¬_multiple_attempt=")) {
    return normalizedValue.split("¬_multiple_attempt=")[0]
  }

  return normalizedValue
}

function addOptionalQueryParam(params, key) {
  const value = getQueryValue(route.query[key])
  if (value !== undefined && value !== null && value !== "") {
    params[key] = value
  }
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }

  addOptionalQueryParam(params, "origin")
  addOptionalQueryParam(params, "lp_init")
  addOptionalQueryParam(params, "learnpath_id")
  addOptionalQueryParam(params, "learnpath_item_id")
  addOptionalQueryParam(params, "learnpath_item_view_id")
  addOptionalQueryParam(params, "lp_id")
  addOptionalQueryParam(params, "node")
  addOptionalQueryParam(params, "type")
  addOptionalQueryParam(params, "returnToLp")
  addOptionalQueryParam(params, "isStudentView")
  addOptionalQueryParam(params, "preview")
  addOptionalQueryParam(params, "attemptId")

  return params
}

function isEmbeddedInLearnpath() {
  if (typeof window === "undefined") {
    return false
  }

  try {
    if (window.parent && window.parent !== window) {
      const parentPath = window.parent.location?.pathname || ""
      const referrer = document.referrer || ""

      return parentPath.includes("/main/lp/")
        || parentPath.includes("/main/newscorm/")
        || referrer.includes("/main/lp/")
        || referrer.includes("/main/newscorm/")
    }
  } catch (error) {
    return (document.referrer || "").includes("/main/lp/")
      || (document.referrer || "").includes("/main/newscorm/")
  }

  return false
}

const isLearnpathContext = computed(() => {
  const origin = String(getQueryValue(route.query.origin) || "")

  return origin === "learnpath"
    || Boolean(getQueryValue(route.query.lp_init))
    || Boolean(getQueryValue(route.query.learnpath_id))
    || isEmbeddedInLearnpath()
})

function syncLearnpathParentFromFinish(response) {
  const tracking = response?.learnpathTracking || {}
  if (!tracking?.enabled || !isLearnpathContext.value || typeof window === "undefined") {
    return
  }

  let parentWindow = null
  try {
    parentWindow = window.parent && window.parent !== window ? window.parent : null
  } catch (error) {
    parentWindow = null
  }

  if (!parentWindow) {
    return
  }

  const itemId = Number(tracking.lpItemId || getQueryValue(route.query.learnpath_item_id) || 0)
  const status = String(tracking.status || response?.status || "completed")
  const completedItems = Number(tracking.completedItems || 0)
  const totalItems = Number(tracking.totalItems || 0)
  const progressMode = String(tracking.progressMode || "%")

  try {
    if (itemId > 0 && typeof parentWindow.update_toc === "function") {
      parentWindow.update_toc(status, itemId)
    }
  } catch (error) {
    console.warn("Could not update learning path TOC after exercise finish", error)
  }

  try {
    if (completedItems >= 0 && totalItems > 0 && typeof parentWindow.update_progress_bar === "function") {
      parentWindow.update_progress_bar(completedItems, totalItems, progressMode)
    }
  } catch (error) {
    console.warn("Could not update learning path progress after exercise finish", error)
  }

  try {
    if (itemId > 0 && typeof parentWindow.checkCurrentItemPosition === "function") {
      parentWindow.checkCurrentItemPosition(itemId)
    }
  } catch (error) {
    console.warn("Could not update learning path navigation after exercise finish", error)
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

async function loadRuntime() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntime(getContextParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    settings.value = response.settings || {}
    questions.value = Array.isArray(response.questions) ? response.questions : []
    legacyUrls.value = response.legacyUrls || {}
    questionCount.value = Number(response.questionCount || questions.value.length)
    totalScore.value = Number(response.totalScore || 0)
    canManage.value = true === response.canManage
    canStartAttempt.value = true === response.canStartAttempt && true !== settings.value.requiresLegacyRuntime
    activeAttempt.value = response.attempt || null
    canSubmit.value = true === response.canSubmit
    usesLegacySubmit.value = true === response.usesLegacySubmit && Boolean(activeAttempt.value)
    applyAttemptState(activeAttempt.value)
    initializeAnswerState()
    applySavedAnswers(activeAttempt.value?.savedAnswers || {})
    syncReviewQuestionIdsFromAttempt(activeAttempt.value)
    isReviewReminderVisible.value = false
    reviewQueue.value = []
    reviewQueueIndex.value = 0
    directFeedbackByQuestion.value = {}
    feedbackDialog.value = null
    isFeedbackDialogVisible.value = false
    confirmedSavedAnswers.value = false
    syncCountdownFromAttempt(activeAttempt.value)
    syncRuntimeSettingsEffects()
    syncQuestionCountdown()
    scheduleRuntimeHtmlEnhancement()
    await nextTick()
    prepareVisibleOnlyofficeDocuments()
  } catch (error) {
    console.error("Error loading exercise runtime", error)
    errorMessage.value = t("Could not load exercise")
  } finally {
    isLoading.value = false
  }
}

async function startAttempt() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    attemptError.value = t("Invalid exercise")
    return
  }

  isStartingAttempt.value = true
  attemptMessage.value = ""
  attemptError.value = ""

  try {
    const response = await exerciseService.startExerciseAttempt({ exerciseId }, getContextParams(), exerciseId)
    if (response.success) {
      activeAttempt.value = response
      canSubmit.value = true === response.canFinish && true !== response.usesLegacyRuntime
      usesLegacySubmit.value = true === response.usesLegacyRuntime || false === response.canFinish
      attemptMessage.value = response.message || t("Attempt started")
      confirmedSavedAnswers.value = false
      applyAttemptState(response)
      reorderQuestionsFromAttempt(response.questionIds || [])
      initializeAnswerState()
      applySavedAnswers(response.savedAnswers || {})
      syncReviewQuestionIdsFromAttempt(response)
      isReviewReminderVisible.value = false
      reviewQueue.value = []
      reviewQueueIndex.value = 0
      syncCountdownFromAttempt(response)
      syncRuntimeSettingsEffects()
      syncQuestionCountdown()
      await nextTick()
      prepareVisibleOnlyofficeDocuments()
      return
    }

    if (response.usesLegacyRuntime && response.legacyUrls) {
      legacyUrls.value = { ...legacyUrls.value, ...response.legacyUrls }
    }

    canSubmit.value = false
    usesLegacySubmit.value = true === response.usesLegacyRuntime
    attemptError.value = response.message || t("Could not start the attempt")
  } catch (error) {
    console.error("Error starting exercise attempt", error)
    attemptError.value = t("Could not start the attempt")
  } finally {
    isStartingAttempt.value = false
  }
}

function applyAttemptState(attempt) {
  if (!attempt) {
    currentQuestionIndex.value = 0
    syncReviewQuestionIds([])
    isReviewReminderVisible.value = false
    reviewQueue.value = []
    reviewQueueIndex.value = 0
    syncCountdownFromAttempt(null)
    syncQuestionCountdown()
    return
  }

  currentQuestionIndex.value = Math.max(0, Number(attempt.currentQuestionIndex || 0))
  if (Array.isArray(attempt.questionIds) && attempt.questionIds.length > 0) {
    reorderQuestionsFromAttempt(attempt.questionIds)
  }
}

function reorderQuestionsFromAttempt(questionIds = []) {
  if (!Array.isArray(questionIds) || questionIds.length === 0) {
    return
  }

  const selectedIds = new Set(questionIds.map(Number))
  const orderedAttemptIds = questionIds.map(Number)
  const originalQuestionMap = new Map(questions.value.map((question) => [Number(question.id), question]))

  if (settings.value?.usesStructuralPages || settings.value?.forceGroupedByMedia) {
    questionCount.value = answerableQuestions.value.length
    return
  }

  const orderedQuestions = []
  for (const questionId of orderedAttemptIds) {
    const question = originalQuestionMap.get(questionId)
    if (question) {
      orderedQuestions.push(question)
    }
  }

  for (const question of questions.value) {
    if (isStructuralQuestion(question) && !selectedIds.has(Number(question.id))) {
      orderedQuestions.push(question)
    }
  }

  if (orderedQuestions.length > 0) {
    let position = 1
    questions.value = orderedQuestions.map((question) => {
      if (isStructuralQuestion(question)) {
        return question
      }

      return {
        ...question,
        position: position++,
      }
    })
    questionCount.value = answerableQuestions.value.length
  }
}

async function goToPreviousQuestion() {
  if (isTimeExpired.value || isQuestionTimeExpired.value || isAutoAdvancingTimedQuestion.value || !canMovePrevious.value) {
    return
  }

  if (await saveVisibleAnswers({ afterFeedback: "previous" })) {
    if (feedbackShownOnLastSave.value) {
      return
    }

    if (isReviewingMarkedQuestions.value) {
      reviewQueueIndex.value = Math.max(0, reviewQueueIndex.value - 1)
      setCurrentQuestionById(reviewQueue.value[reviewQueueIndex.value])
      syncQuestionCountdown()
      return
    }

    currentQuestionIndex.value -= 1
    syncQuestionCountdown()
  }
}

async function goToNextQuestion() {
  if (isTimeExpired.value || isQuestionTimeExpired.value || isAutoAdvancingTimedQuestion.value || !canMoveNext.value) {
    return
  }

  if (await saveVisibleAnswers({ afterFeedback: "next" })) {
    if (feedbackShownOnLastSave.value) {
      return
    }

    if (isReviewingMarkedQuestions.value) {
      reviewQueueIndex.value = Math.min(reviewQueue.value.length - 1, reviewQueueIndex.value + 1)
      setCurrentQuestionById(reviewQueue.value[reviewQueueIndex.value])
      syncQuestionCountdown()
      return
    }

    currentQuestionIndex.value += 1
    syncQuestionCountdown()
  }
}

async function saveVisibleAnswers(options = {}) {
  if (canManage.value || !activeAttempt.value?.attemptId) {
    return true
  }

  const saveTargets = visibleQuestions.value.filter(isDraftSaveSupported)
  if (saveTargets.length === 0) {
    return true
  }

  isSavingAnswer.value = true
  answerSaveError.value = ""
  answerSaveMessage.value = ""
  feedbackShownOnLastSave.value = false

  try {
    for (const question of saveTargets) {
      await saveQuestionDraftAnswer(question, options.afterFeedback || "none")
    }

    answerSaveMessage.value = t("Draft answer saved")

    return true
  } catch (error) {
    console.error("Error saving exercise draft answer", error)
    answerSaveError.value = t("Could not save draft answer")

    return false
  } finally {
    isSavingAnswer.value = false
  }
}

async function finishAttempt(options = {}) {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  if (canManage.value || !exerciseId || !attemptId) {
    return
  }

  const skipDraftSave = true === options.skipDraftSave
  const ignoreFeedback = true === options.ignoreFeedback
  if (!skipDraftSave && !(await saveVisibleAnswers({ afterFeedback: "finish" }))) {
    return
  }

  if (!ignoreFeedback && feedbackShownOnLastSave.value) {
    return
  }

  if (true !== options.skipReviewAnswers && !options.expiredByTimer && showReviewReminder()) {
    return
  }

  isFinishingAttempt.value = true
  finishError.value = ""
  finishMessage.value = ""

  try {
    const response = await exerciseService.finishExerciseRuntimeAttempt(
      {
        exerciseId,
        attemptId,
        confirmedSavedAnswers: true === confirmedSavedAnswers.value,
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

    if (!response?.success) {
      throw new Error(response?.message || "Could not finish the attempt")
    }

    activeAttempt.value = {
      ...activeAttempt.value,
      status: response.status || "completed",
    }
    canSubmit.value = false
    stopCountdownTimer()
    finishMessage.value = response.message ? t(response.message) : t("Attempt finished")
    syncLearnpathParentFromFinish(response)

    await router.push({
      name: "ExerciseResult",
      params: {
        ...route.params,
        exerciseId,
        attemptId,
      },
      query: getContextParams(),
    })
  } catch (error) {
    console.error("Error finishing exercise attempt", error)
    finishError.value = t("Could not finish the attempt")
  } finally {
    isFinishingAttempt.value = false
    isAutoFinishingExpiredAttempt.value = false
  }
}

async function saveQuestionDraftAnswer(question, afterFeedback = "none", options = {}) {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  if (!exerciseId || !attemptId || !question?.id) {
    return
  }

  const response = isUploadQuestion(question) || isOralQuestion(question)
    ? await saveUploadQuestionAnswer(question, exerciseId, attemptId, afterFeedback, options)
    : await exerciseService.saveExerciseRuntimeAnswer(
      {
        exerciseId,
        attemptId,
        questionId: Number(question.id),
        answer: buildAnswerPayload(question),
        reviewLater: isQuestionMarkedForReview(question.id),
        secondsSpent: getQuestionSecondsSpent(question),
        navigationAction: afterFeedback,
        confirmCategory: true === options.confirmCategory,
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

  if (!response) {
    return
  }

  if (!response?.success) {
    throw new Error(response?.message || "Could not save draft answer")
  }

  syncReviewQuestionIdsFromResponse(response)

  if (Array.isArray(response.answeredQuestionIds)) {
    savedQuestionIds.value = new Set(response.answeredQuestionIds.map(Number))
  } else if (Array.isArray(response.savedAnswer) && response.savedAnswer.length > 0) {
    const nextSavedQuestionIds = new Set(savedQuestionIds.value)
    nextSavedQuestionIds.add(Number(question.id))
    savedQuestionIds.value = nextSavedQuestionIds
  } else {
    const nextSavedQuestionIds = new Set(savedQuestionIds.value)
    nextSavedQuestionIds.delete(Number(question.id))
    savedQuestionIds.value = nextSavedQuestionIds
  }

  if (response?.categoryReminder?.enabled) {
    showCategoryReminder(response.categoryReminder)
    return response
  }

  const onlyofficeEditor = response?.feedback?.onlyoffice?.editorUrl || ""
  if (isOnlyofficeQuestion(question) && onlyofficeEditor && answers.value[question.id]) {
    answers.value[question.id].onlyofficeEditorUrl = onlyofficeEditor
  }

  rememberQuestionSecondsSpent(question)
  updateQuestionCountdown()
  handleRuntimeFeedback(question, response.feedback || null, afterFeedback)

  return response
}


async function saveUploadQuestionAnswer(question, exerciseId, attemptId, afterFeedback = "none", options = {}) {
  const questionAnswer = answers.value[question.id] || {}
  if (!questionAnswer.uploadFile && !questionAnswer.oralFile) {
    return null
  }

  const formData = new FormData()
  formData.append("questionId", String(Number(question.id)))
  formData.append("secondsSpent", String(getQuestionSecondsSpent(question)))
  formData.append("reviewLater", isQuestionMarkedForReview(question.id) ? "1" : "0")
  formData.append("navigationAction", afterFeedback)
  formData.append("confirmCategory", true === options.confirmCategory ? "1" : "0")
  formData.append("file", questionAnswer.uploadFile || questionAnswer.oralFile)

  const response = await exerciseService.uploadExerciseRuntimeAnswer(
    formData,
    getContextParams(),
    exerciseId,
    attemptId,
  )

  if (response?.success) {
    syncReviewQuestionIdsFromResponse(response)
    questionAnswer.uploadFile = null
    questionAnswer.uploadFileName = ""
    questionAnswer.oralFile = null
    questionAnswer.oralFileName = ""
    questionAnswer.oralPreviewUrl = ""
    questionAnswer.uploadedFiles = Array.isArray(response.files) ? response.files : []
  }

  return response
}


function isRuntimeFeedbackMode() {
  return [1, 3].includes(Number(settings.value.feedbackType || 0))
}

function handleRuntimeFeedback(question, feedback, afterAction = "none") {
  if (!isRuntimeFeedbackMode() || !feedback?.enabled) {
    return
  }

  const normalizedFeedback = {
    ...feedback,
    questionId: Number(feedback.questionId || question.id || 0),
    afterAction: feedback.afterAction || afterAction,
    targetQuestionId: Number(feedback.targetQuestionId || 0),
    targetUrl: feedback.targetUrl || "",
  }

  feedbackShownOnLastSave.value = true

  if (feedback.mode === "popup") {
    feedbackDialog.value = normalizedFeedback
    isFeedbackDialogVisible.value = true
    return
  }

  directFeedbackByQuestion.value = {
    ...directFeedbackByQuestion.value,
    [Number(question.id)]: normalizedFeedback,
  }
}

function directFeedbackForQuestion(question) {
  return directFeedbackByQuestion.value[Number(question?.id || 0)] || null
}

function feedbackEntries(feedback) {
  return Array.isArray(feedback?.entries) ? feedback.entries : []
}

function feedbackStatusClass(feedback) {
  const status = feedback?.status || ""

  if (status === "correct") {
    return "border-success/30 bg-success/10 text-success"
  }

  if (status === "partial" || status === "pending") {
    return "border-warning/30 bg-warning/10 text-warning"
  }

  return "border-danger/30 bg-danger/10 text-danger"
}

function formatScore(value) {
  const number = Number(value || 0)

  return Number.isInteger(number) ? String(number) : number.toFixed(2)
}

async function proceedAfterFeedback(feedback) {
  if (!feedback) {
    return
  }

  const questionId = Number(feedback.questionId || 0)
  if (questionId > 0) {
    const nextFeedback = { ...directFeedbackByQuestion.value }
    delete nextFeedback[questionId]
    directFeedbackByQuestion.value = nextFeedback
  }

  isFeedbackDialogVisible.value = false
  feedbackDialog.value = null

  if (feedback.afterAction === "finish") {
    await finishAttempt({ skipDraftSave: true, skipReviewAnswers: true, ignoreFeedback: true })
    return
  }

  if (feedback.afterAction === "next" && canMoveNext.value) {
    currentQuestionIndex.value += 1
    syncQuestionCountdown()
    return
  }

  if (feedback.afterAction === "previous" && canMovePrevious.value) {
    currentQuestionIndex.value -= 1
    syncQuestionCountdown()
    return
  }

  if (feedback.afterAction === "question" && Number(feedback.targetQuestionId || 0) > 0) {
    setCurrentQuestionById(Number(feedback.targetQuestionId))
    syncQuestionCountdown()
    return
  }

  if (feedback.afterAction === "repeat") {
    syncQuestionCountdown()
    return
  }

  if (feedback.afterAction === "url" && feedback.targetUrl) {
    window.location.href = feedback.targetUrl
  }
}

function getMatchingAnswerState(question) {
  if (!answers.value[question.id]) {
    answers.value[question.id] = { matching: {} }
  }

  if (!answers.value[question.id].matching || typeof answers.value[question.id].matching !== "object") {
    answers.value[question.id].matching = {}
  }

  return answers.value[question.id].matching
}

function matchingOptionById(question, optionId) {
  const safeOptionId = Number(optionId || 0)

  return (question.matching?.options || []).find((option) => Number(option.id) === safeOptionId) || null
}

function selectedMatchingOptionId(question, promptId) {
  return Number(getMatchingAnswerState(question)[promptId] || 0)
}

function selectedMatchingOption(question, promptId) {
  return matchingOptionById(question, selectedMatchingOptionId(question, promptId))
}

function selectedMatchingOptionForQuestion(question) {
  return matchingOptionById(question, selectedMatchingOptions.value[question.id])
}

function matchingOptionDisplayLabel(option) {
  if (!option) {
    return ""
  }

  return option.label ? `${option.label}.` : `#${option.position || option.id}`
}

function isMatchingOptionAssigned(question, optionId) {
  const safeOptionId = Number(optionId || 0)
  if (safeOptionId <= 0) {
    return false
  }

  return Object.values(getMatchingAnswerState(question)).some((value) => Number(value) === safeOptionId)
}

function matchingOptionButtonClass(question, optionId) {
  const safeOptionId = Number(optionId || 0)
  const isSelected = Number(selectedMatchingOptions.value[question.id] || 0) === safeOptionId
  const isAssigned = isMatchingOptionAssigned(question, safeOptionId)

  if (isSelected) {
    return "border-primary bg-primary/10 text-primary"
  }

  if (isAssigned) {
    return "border-gray-25 bg-white text-gray-500 opacity-70 hover:border-primary"
  }

  return "border-gray-30 bg-white text-gray-900 hover:border-primary hover:bg-primary/5"
}

function selectMatchingOption(question, optionId) {
  const safeOptionId = Number(optionId || 0)
  if (safeOptionId <= 0) {
    return
  }

  selectedMatchingOptions.value = {
    ...selectedMatchingOptions.value,
    [question.id]: safeOptionId,
  }
}

function assignSelectedMatchingOption(question, promptId) {
  const selectedOptionId = Number(selectedMatchingOptions.value[question.id] || 0)
  if (selectedOptionId <= 0) {
    return
  }

  assignMatchingOption(question, promptId, selectedOptionId)
}

function assignMatchingOption(question, promptId, optionId) {
  const safePromptId = Number(promptId || 0)
  const safeOptionId = Number(optionId || 0)
  if (safePromptId <= 0 || safeOptionId <= 0) {
    return
  }

  const matching = getMatchingAnswerState(question)
  for (const existingPromptId of Object.keys(matching)) {
    if (Number(existingPromptId) !== safePromptId && Number(matching[existingPromptId]) === safeOptionId) {
      delete matching[existingPromptId]
    }
  }

  matching[safePromptId] = safeOptionId
  selectedMatchingOptions.value = {
    ...selectedMatchingOptions.value,
    [question.id]: nextUnassignedMatchingOptionId(question) || safeOptionId,
  }
}

function clearMatchingOption(question, promptId) {
  const safePromptId = Number(promptId || 0)
  if (safePromptId <= 0) {
    return
  }

  const matching = getMatchingAnswerState(question)
  const removedOptionId = Number(matching[safePromptId] || 0)
  delete matching[safePromptId]

  if (removedOptionId > 0) {
    selectedMatchingOptions.value = {
      ...selectedMatchingOptions.value,
      [question.id]: removedOptionId,
    }
  }
}

function nextUnassignedMatchingOptionId(question) {
  const assignedOptionIds = new Set(Object.values(getMatchingAnswerState(question)).map((value) => Number(value || 0)))
  const option = (question.matching?.options || []).find((candidate) => !assignedOptionIds.has(Number(candidate.id)))

  return Number(option?.id || 0)
}

function onMatchingDragStart(optionId) {
  draggedMatchingOptionId.value = Number(optionId || 0)
}

function onMatchingDrop(question, promptId) {
  const optionId = Number(draggedMatchingOptionId.value || 0)
  draggedMatchingOptionId.value = null
  if (optionId <= 0) {
    return
  }

  assignMatchingOption(question, promptId, optionId)
}

function buildAnswerPayload(question) {
  const questionAnswer = answers.value[question.id] || {}

  if (isRadioChoice(question)) {
    return { choice: questionAnswer.choice }
  }

  if (isCheckboxChoice(question)) {
    return { choices: questionAnswer.choices || [] }
  }

  if (isTrueFalseQuestion(question)) {
    return {
      trueFalse: questionAnswer.trueFalse || {},
      degreeCertainty: questionAnswer.degreeCertainty || {},
    }
  }

  if (isFillBlanksQuestion(question)) {
    return { blanks: questionAnswer.blanks || {} }
  }

  if (isMatchingQuestion(question)) {
    return { matching: questionAnswer.matching || {} }
  }

  if (isDraggableQuestion(question)) {
    return { order: draggableAnswerItems(question).map((item) => Number(item.id || 0)).filter((itemId) => itemId > 0) }
  }

  if (isDropdownQuestion(question)) {
    return { dropdown: questionAnswer.dropdown }
  }

  if (isCalculatedQuestion(question)) {
    return {
      calculated: questionAnswer.calculated || "",
      answerId: questionAnswer.calculatedAnswerId || currentCalculatedVariation(question).id || question.calculated?.answerId || null,
    }
  }

  if (isAnnotationQuestion(question)) {
    return {
      paths: annotationPaths(question),
      texts: annotationTexts(question),
    }
  }

  if (isHotspotQuestion(question)) {
    return { points: hotspotPlacedPoints(question) }
  }

  if (isDraftFreeAnswerQuestion(question)) {
    return { text: questionAnswer.text || "" }
  }

  if (isOnlyofficeQuestion(question)) {
    return { onlyoffice: true }
  }

  return {}
}

function isDraftSaveSupported(question) {
  return isRadioChoice(question)
    || isCheckboxChoice(question)
    || isDraftTrueFalseQuestion(question)
    || isFillBlanksQuestion(question)
    || isMatchingQuestion(question)
    || isDraggableQuestion(question)
    || isDropdownQuestion(question)
    || isCalculatedQuestion(question)
    || isHotspotQuestion(question)
    || isAnnotationQuestion(question)
    || isDraftFreeAnswerQuestion(question)
    || isOnlyofficeQuestion(question)
    || isUploadQuestion(question)
    || isOralQuestion(question)
}

function isDraftTrueFalseQuestion(question) {
  return [11, 12, 22].includes(Number(question.type))
}

function isDraftFreeAnswerQuestion(question) {
  return Number(question.type) === 5
}

function applySavedAnswers(savedAnswers = {}) {
  const savedIds = new Set()

  for (const [questionId, rows] of Object.entries(savedAnswers || {})) {
    const question = questions.value.find((item) => Number(item.id) === Number(questionId))
    if (!question || !Array.isArray(rows)) {
      continue
    }

    applySavedAnswer(question, rows)
    if (rows.length > 0) {
      savedIds.add(Number(questionId))
    }
  }

  savedQuestionIds.value = savedIds
}

function applySavedAnswer(question, rows) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  if (isRadioChoice(question)) {
    questionAnswer.choice = Number(rows[0]?.answer || 0) || null
    return
  }

  if (isCheckboxChoice(question)) {
    questionAnswer.choices = rows.map((row) => Number(row.answer || 0)).filter((value) => value > 0)
    return
  }

  if (isTrueFalseQuestion(question)) {
    questionAnswer.trueFalse = {}
    questionAnswer.degreeCertainty = {}
    for (const row of rows) {
      const parts = String(row.answer || "").split(":")
      const answerId = Number(parts[0] || 0)
      const optionValue = Number(parts[1] || 0)
      const degreeValue = Number(parts[2] || 0)
      if (answerId > 0 && optionValue > 0) {
        questionAnswer.trueFalse[answerId] = optionValue
      }
      if (answerId > 0 && degreeValue > 0) {
        questionAnswer.degreeCertainty[answerId] = degreeValue
      }
    }
    return
  }

  if (isFillBlanksQuestion(question)) {
    questionAnswer.blanks = extractSavedBlankValues(rows[0]?.answer || "", question.fillBlanks.separator)
    return
  }

  if (isMatchingQuestion(question)) {
    questionAnswer.matching = {}
    for (const row of rows) {
      const promptId = Number(row.position || 0)
      const optionId = Number(row.answer || 0)
      if (promptId > 0 && optionId > 0) {
        questionAnswer.matching[promptId] = optionId
      }
    }
    return
  }

  if (isDraggableQuestion(question)) {
    const orderedIds = [...rows]
      .sort((left, right) => Number(left.answer || 0) - Number(right.answer || 0))
      .map((row) => Number(row.position || 0))
      .filter((itemId) => itemId > 0)
    const availableItems = Array.isArray(question.draggable?.items) ? question.draggable.items : []
    const availableIds = new Set(availableItems.map((item) => Number(item.id || 0)))
    const restoredIds = orderedIds.filter((itemId) => availableIds.has(itemId))
    for (const item of availableItems) {
      const itemId = Number(item.id || 0)
      if (itemId > 0 && !restoredIds.includes(itemId)) {
        restoredIds.push(itemId)
      }
    }
    questionAnswer.draggableOrder = restoredIds
    return
  }

  if (isDropdownQuestion(question)) {
    questionAnswer.dropdown = Number(rows[0]?.answer || 0) || ""
    return
  }

  if (isCalculatedQuestion(question)) {
    const [answerId, value] = parseSavedCalculatedAnswer(rows[0]?.answer || "")
    questionAnswer.calculatedAnswerId = answerId || question.calculated?.answerId || currentCalculatedVariation(question).id || null
    questionAnswer.calculated = value
    return
  }

  if (isAnnotationQuestion(question)) {
    const annotation = parseSavedAnnotationAnswer(rows[0]?.answer || "")
    questionAnswer.annotationPaths = annotation.paths
    questionAnswer.annotationTexts = annotation.texts
    questionAnswer.annotationMode = "path"
    return
  }

  if (isHotspotQuestion(question)) {
    questionAnswer.hotspotPoints = parseSavedHotspotPoints(rows[0]?.answer || "")
    questionAnswer.selectedHotspotAnswerId = isHotspotDelineationQuestion(question)
      ? null
      : (firstMissingHotspotZoneId(question) || hotspotZones(question)[0]?.id || null)
    return
  }

  if (isOpenQuestion(question)) {
    questionAnswer.text = rows[0]?.answer || ""
    return
  }

  if (isOnlyofficeQuestion(question)) {
    const files = extractSavedAttemptFiles(rows)
    questionAnswer.onlyofficeFiles = files
    questionAnswer.onlyofficeEditorUrl = files.find((file) => file?.onlyofficeEditorUrl)?.onlyofficeEditorUrl || question.onlyoffice?.editorUrl || ""
    return
  }

  if (isUploadQuestion(question)) {
    const files = extractSavedAttemptFiles(rows)
    questionAnswer.uploadedFiles = files.length > 0 ? files : (rows.length > 0 ? [{ name: t("Upload file") }] : [])
    return
  }

  if (isOralQuestion(question)) {
    const files = extractSavedAttemptFiles(rows)
    questionAnswer.uploadedFiles = files.length > 0 ? files : (rows.length > 0 ? [{ name: t("Uploaded audio") }] : [])
  }
}


function extractSavedAttemptFiles(rows = []) {
  const files = []

  for (const row of rows) {
    if (!Array.isArray(row?.files)) {
      continue
    }

    for (const file of row.files) {
      if (file && typeof file === "object") {
        files.push(file)
      }
    }
  }

  return files
}

function extractSavedBlankValues(savedAnswer, separator = 0) {
  const [start, end] = getFillBlankSeparators(separator)
  const pattern = new RegExp(`${escapeRegExp(start)}(.*?)${escapeRegExp(end)}`, "g")
  const matches = [...String(savedAnswer || "").split("::")[0].matchAll(pattern)]
  const blanks = {}

  for (let index = 0; index < matches.length; index += 3) {
    const blankPosition = Math.floor(index / 3) + 1
    blanks[blankPosition] = decodeHtml(matches[index + 1]?.[1] || "")
  }

  return blanks
}

function getFillBlankSeparators(separator = 0) {
  const separators = [
    ["[", "]"],
    ["{", "}"],
    ["(", ")"],
    ["*", "*"],
    ["#", "#"],
    ["%", "%"],
    ["$", "$"],
  ]

  return separators[Number(separator || 0)] || separators[0]
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
}

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function initializeAnswerState() {
  const nextAnswers = {}
  const nextSelectedMatchingOptions = {}

  for (const question of questions.value) {
    nextAnswers[question.id] = {
      choice: null,
      choices: [],
      trueFalse: {},
      degreeCertainty: {},
      blanks: {},
      matching: {},
      draggableOrder: draggableInitialOrder(question),
      dropdown: "",
      calculated: "",
      calculatedAnswerId: currentCalculatedVariation(question).id || question.calculated?.answerId || null,
      text: "",
      uploadFile: null,
      uploadFileName: "",
      uploadedFiles: [],
      onlyofficeEditorUrl: question.onlyoffice?.editorUrl || "",
      onlyofficeFiles: [],
      onlyofficePreparing: false,
      onlyofficeError: "",
      oralFile: null,
      oralFileName: "",
      oralPreviewUrl: "",
      annotationMode: "path",
      annotationPaths: [[]],
      annotationTexts: [],
      annotationTextDraft: "",
      annotationImageSize: null,
      hotspotPoints: [],
      hotspotImageSize: null,
      selectedHotspotAnswerId: isHotspotDelineationQuestion(question) ? null : (hotspotZones(question)[0]?.id || null),
      reviewLater: reviewQuestionIds.value.has(Number(question.id || 0)),
    }

    if (isMatchingDraggableQuestion(question)) {
      nextSelectedMatchingOptions[question.id] = question.matching?.options?.[0]?.id || null
    }
  }

  answers.value = nextAnswers
  selectedMatchingOptions.value = nextSelectedMatchingOptions
}


async function prepareOnlyofficeDocument(question, forceReload = false) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer || canManage.value || !activeAttempt.value?.attemptId) {
    return
  }

  if (!forceReload && (questionAnswer.onlyofficeEditorUrl || questionAnswer.onlyofficePreparing)) {
    return
  }

  questionAnswer.onlyofficePreparing = true
  questionAnswer.onlyofficeError = ""

  try {
    const response = await saveQuestionDraftAnswer(question, "none")
    const editorUrl = response?.feedback?.onlyoffice?.editorUrl || onlyofficeEditorUrl(question)
    if (editorUrl) {
      questionAnswer.onlyofficeEditorUrl = editorUrl
    }

    const files = extractSavedAttemptFiles(response?.savedAnswer || [])
    if (files.length > 0) {
      questionAnswer.onlyofficeFiles = files
    }
  } catch (error) {
    console.error("Error preparing OnlyOffice document", error)
    questionAnswer.onlyofficeError = t("Could not prepare Office document")
  } finally {
    questionAnswer.onlyofficePreparing = false
  }
}

function prepareVisibleOnlyofficeDocuments() {
  if (canManage.value || !activeAttempt.value?.attemptId) {
    return
  }

  for (const question of visibleQuestions.value) {
    if (isOnlyofficeQuestion(question)) {
      void prepareOnlyofficeDocument(question)
    }
  }
}

function openOnlyofficeDocumentInNewTab(question) {
  const editorUrl = onlyofficeEditorUrl(question)
  if (editorUrl) {
    window.open(editorUrl, "_blank", "noopener")
  }
}

function onlyofficeEditorUrl(question) {
  const questionAnswer = answers.value[question.id] || {}

  return questionAnswer.onlyofficeEditorUrl || question.onlyoffice?.editorUrl || ""
}

function onlyofficeAttemptFiles(question) {
  const questionAnswer = answers.value[question.id] || {}

  return Array.isArray(questionAnswer.onlyofficeFiles) ? questionAnswer.onlyofficeFiles : []
}

function onUploadAnswerFileChange(question, event) {
  const file = event?.target?.files?.[0] || null
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.uploadFile = file
  questionAnswer.uploadFileName = file?.name || ""
}

function onOralRecorded(question, audioBlob) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer || !audioBlob) {
    return
  }

  const fileName = `oral-expression-${question.id}.wav`
  questionAnswer.oralFile = new File([audioBlob], fileName, { type: "audio/wav" })
  questionAnswer.oralFileName = fileName
  questionAnswer.uploadFile = null
  questionAnswer.uploadFileName = ""
  questionAnswer.oralPreviewUrl = window.URL.createObjectURL(audioBlob)
}

function onOralFileChange(question, event) {
  const file = event?.target?.files?.[0] || null
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.oralFile = file
  questionAnswer.oralFileName = file?.name || ""
  questionAnswer.uploadFile = null
  questionAnswer.uploadFileName = ""
  questionAnswer.oralPreviewUrl = file ? window.URL.createObjectURL(file) : ""
}

function setAnnotationMode(question, mode) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.annotationMode = mode
  if (mode === "path") {
    ensureCurrentAnnotationPath(question)
  }
}

function onAnnotationImageLoad(question, event) {
  const questionAnswer = answers.value[question.id]
  const image = event?.target
  if (!questionAnswer || !image) {
    return
  }

  questionAnswer.annotationImageSize = {
    width: Number(image.naturalWidth || image.width || 0),
    height: Number(image.naturalHeight || image.height || 0),
  }
}

function onAnnotationImageClick(question, event) {
  const questionAnswer = answers.value[question.id]
  const image = event?.target
  if (!questionAnswer || !image) {
    return
  }

  const point = getImageNaturalPoint(image, event)
  if (!point) {
    return
  }

  questionAnswer.annotationImageSize = point.size
  if (questionAnswer.annotationMode === "text") {
    const text = String(questionAnswer.annotationTextDraft || "").trim()
    if (!text) {
      return
    }

    questionAnswer.annotationTexts.push({ text, x: point.x, y: point.y })
    questionAnswer.annotationTextDraft = ""
    return
  }

  const currentPath = ensureCurrentAnnotationPath(question)
  currentPath.push({ x: point.x, y: point.y })
}

function getImageNaturalPoint(image, event) {
  const rect = image.getBoundingClientRect()
  const naturalWidth = Number(image.naturalWidth || rect.width || 0)
  const naturalHeight = Number(image.naturalHeight || rect.height || 0)
  if (!rect.width || !rect.height || !naturalWidth || !naturalHeight) {
    return null
  }

  return {
    x: Math.max(0, Math.round(((event.clientX - rect.left) / rect.width) * naturalWidth)),
    y: Math.max(0, Math.round(((event.clientY - rect.top) / rect.height) * naturalHeight)),
    size: { width: naturalWidth, height: naturalHeight },
  }
}

function ensureCurrentAnnotationPath(question) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer.annotationPaths || !Array.isArray(questionAnswer.annotationPaths)) {
    questionAnswer.annotationPaths = [[]]
  }

  if (questionAnswer.annotationPaths.length === 0) {
    questionAnswer.annotationPaths.push([])
  }

  return questionAnswer.annotationPaths[questionAnswer.annotationPaths.length - 1]
}

function startNewAnnotationPath(question) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  const currentPath = ensureCurrentAnnotationPath(question)
  if (currentPath.length === 0) {
    return
  }

  questionAnswer.annotationPaths.push([])
}

function undoAnnotation(question) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  if (questionAnswer.annotationMode === "text" && questionAnswer.annotationTexts.length > 0) {
    questionAnswer.annotationTexts.pop()
    return
  }

  const currentPath = ensureCurrentAnnotationPath(question)
  if (currentPath.length > 0) {
    currentPath.pop()
    return
  }

  if (questionAnswer.annotationPaths.length > 1) {
    questionAnswer.annotationPaths.pop()
  }
}

function clearAnnotation(question) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.annotationPaths = [[]]
  questionAnswer.annotationTexts = []
  questionAnswer.annotationTextDraft = ""
}

function annotationPaths(question) {
  const questionAnswer = answers.value[question.id] || {}
  const paths = Array.isArray(questionAnswer.annotationPaths) ? questionAnswer.annotationPaths : []

  return paths
    .map((path) => Array.isArray(path) ? path.filter((point) => Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y))) : [])
    .filter((path) => path.length > 0)
    .map((points) => ({ points }))
}

function annotationTexts(question) {
  const questionAnswer = answers.value[question.id] || {}
  const texts = Array.isArray(questionAnswer.annotationTexts) ? questionAnswer.annotationTexts : []

  return texts.filter((item) => String(item.text || "").trim() && Number.isFinite(Number(item.x)) && Number.isFinite(Number(item.y)))
}

function annotationImageReady(question) {
  const size = answers.value[question.id]?.annotationImageSize || {}

  return Number(size.width || 0) > 0 && Number(size.height || 0) > 0
}

function annotationViewBox(question) {
  const size = answers.value[question.id]?.annotationImageSize || {}

  return `0 0 ${Number(size.width || 1)} ${Number(size.height || 1)}`
}

function annotationPolylinePoints(path) {
  const points = Array.isArray(path?.points) ? path.points : []

  return points.map((point) => `${Number(point.x || 0)},${Number(point.y || 0)}`).join(" ")
}

function annotationPointStyle(question, point) {
  const size = answers.value[question.id]?.annotationImageSize || {}
  const width = Number(size.width || 0)
  const height = Number(size.height || 0)
  if (width > 0 && height > 0) {
    return {
      left: `${(Number(point.x || 0) / width) * 100}%`,
      top: `${(Number(point.y || 0) / height) * 100}%`,
    }
  }

  return {
    left: "0%",
    top: "0%",
  }
}

function parseSavedAnnotationAnswer(value) {
  const result = { paths: [], texts: [] }
  for (const item of String(value || "").split("|")) {
    const parts = item.split(")(")
    const type = parts.shift()
    if (type === "P") {
      const points = parts.map(decodeAnnotationPoint).filter(Boolean)
      if (points.length > 0) {
        result.paths.push(points)
      }
      continue
    }

    if (type === "T" && parts.length >= 2) {
      const text = String(parts.shift() || "").trim()
      const point = decodeAnnotationPoint(parts[0])
      if (text && point) {
        result.texts.push({ text, ...point })
      }
    }
  }

  if (result.paths.length === 0) {
    result.paths = [[]]
  }

  return result
}

function decodeAnnotationPoint(value) {
  const parts = String(value || "").split(";")
  const x = Number(parts[0] || NaN)
  const y = Number(parts[1] || NaN)
  if (!Number.isFinite(x) || !Number.isFinite(y)) {
    return null
  }

  return { x, y }
}

function onHotspotImageLoad(question, event) {
  const questionAnswer = answers.value[question.id]
  const image = event?.target
  if (!questionAnswer || !image) {
    return
  }

  questionAnswer.hotspotImageSize = {
    width: Number(image.naturalWidth || image.width || 0),
    height: Number(image.naturalHeight || image.height || 0),
  }
}

function onHotspotImageClick(question, event) {
  const questionAnswer = answers.value[question.id]
  const image = event?.target
  if (!questionAnswer || !image) {
    return
  }

  const rect = image.getBoundingClientRect()
  const naturalWidth = Number(image.naturalWidth || rect.width || 0)
  const naturalHeight = Number(image.naturalHeight || rect.height || 0)
  if (!rect.width || !rect.height || !naturalWidth || !naturalHeight) {
    return
  }

  const x = Math.round(((event.clientX - rect.left) / rect.width) * naturalWidth)
  const y = Math.round(((event.clientY - rect.top) / rect.height) * naturalHeight)
  questionAnswer.hotspotImageSize = { width: naturalWidth, height: naturalHeight }

  if (isHotspotDelineationQuestion(question)) {
    questionAnswer.hotspotPoints.push({
      x: Math.max(0, x),
      y: Math.max(0, y),
    })
    return
  }

  const selectedAnswerId = Number(questionAnswer?.selectedHotspotAnswerId || 0)
  if (selectedAnswerId <= 0) {
    return
  }

  const nextPoint = {
    answerId: selectedAnswerId,
    x: Math.max(0, x),
    y: Math.max(0, y),
  }
  const existingIndex = questionAnswer.hotspotPoints.findIndex((point) => Number(point.answerId || 0) === selectedAnswerId)
  if (existingIndex >= 0) {
    questionAnswer.hotspotPoints.splice(existingIndex, 1, nextPoint)
  } else {
    questionAnswer.hotspotPoints.push(nextPoint)
  }

  questionAnswer.selectedHotspotAnswerId = firstMissingHotspotZoneId(question) || selectedAnswerId
}

function selectHotspotZone(question, answerId) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.selectedHotspotAnswerId = Number(answerId || 0) || null
}

function removeHotspotPoint(question, answerId, pointIndex = null) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  if (isHotspotDelineationQuestion(question)) {
    const numericIndex = Number(pointIndex)
    if (Number.isInteger(numericIndex) && numericIndex >= 0) {
      questionAnswer.hotspotPoints.splice(numericIndex, 1)
      return
    }

    questionAnswer.hotspotPoints.pop()
    return
  }

  const numericAnswerId = Number(answerId || 0)
  if (numericAnswerId > 0) {
    questionAnswer.hotspotPoints = questionAnswer.hotspotPoints.filter((point) => Number(point.answerId || 0) !== numericAnswerId)
    questionAnswer.selectedHotspotAnswerId = numericAnswerId
    return
  }

  questionAnswer.hotspotPoints.pop()
}

function undoHotspotDelineation(question) {
  removeHotspotPoint(question, 0)
}

function clearHotspotDelineation(question) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.hotspotPoints = []
}

function hotspotZones(question) {
  const zones = Array.isArray(question?.hotspot?.zones) ? question.hotspot.zones : []

  return zones.length ? zones : (Array.isArray(question?.hotspot?.items) ? question.hotspot.items : [])
}

function hotspotPointByAnswer(question, answerId) {
  const questionAnswer = answers.value[question.id] || {}
  const numericAnswerId = Number(answerId || 0)

  return (questionAnswer.hotspotPoints || []).find((point) => Number(point.answerId || 0) === numericAnswerId) || null
}

function hotspotPlacedPoints(question) {
  const questionAnswer = answers.value[question.id] || {}
  const zones = hotspotZones(question)
  const rawPoints = questionAnswer.hotspotPoints || []

  if (isHotspotDelineationQuestion(question)) {
    return rawPoints.map((point, index) => ({
      ...point,
      answerId: 0,
      index,
      label: index + 1,
    }))
  }

  const orderedPoints = []

  for (const [zoneIndex, zone] of zones.entries()) {
    const answerId = Number(zone.id || 0)
    const point = rawPoints.find((item) => Number(item.answerId || 0) === answerId)
    if (!point) {
      continue
    }

    orderedPoints.push({
      ...point,
      answerId,
      label: zone.position || zoneIndex + 1,
    })
  }

  for (const [pointIndex, point] of rawPoints.entries()) {
    const answerId = Number(point.answerId || 0)
    if (answerId > 0 && zones.some((zone) => Number(zone.id || 0) === answerId)) {
      continue
    }

    orderedPoints.push({
      ...point,
      answerId,
      label: point.label || orderedPoints.length + pointIndex + 1,
    })
  }

  return orderedPoints
}

function hotspotPointLabel(question, point) {
  return point.label || hotspotZones(question).findIndex((zone) => Number(zone.id || 0) === Number(point.answerId || 0)) + 1 || ''
}

function firstMissingHotspotZoneId(question) {
  if (isHotspotDelineationQuestion(question)) {
    return null
  }

  const questionAnswer = answers.value[question.id] || {}
  const placedAnswerIds = new Set((questionAnswer.hotspotPoints || []).map((point) => Number(point.answerId || 0)).filter((value) => value > 0))
  const missingZone = hotspotZones(question).find((zone) => !placedAnswerIds.has(Number(zone.id || 0)))

  return missingZone?.id || null
}

function hotspotPointStyle(question, point) {
  const questionAnswer = answers.value[question.id] || {}
  const size = questionAnswer.hotspotImageSize || {}
  const width = Number(size.width || 0)
  const height = Number(size.height || 0)

  if (width > 0 && height > 0) {
    return {
      left: `${(Number(point.x || 0) / width) * 100}%`,
      top: `${(Number(point.y || 0) / height) * 100}%`,
    }
  }

  return {
    left: `${Number(point.x || 0)}px`,
    top: `${Number(point.y || 0)}px`,
  }
}

function hotspotImageViewBox(question) {
  const questionAnswer = answers.value[question.id] || {}
  const size = questionAnswer.hotspotImageSize || {}
  const width = Number(size.width || 0) || 1
  const height = Number(size.height || 0) || 1

  return `0 0 ${width} ${height}`
}

function hotspotDelineationSvgPoints(question) {
  return hotspotPlacedPoints(question)
    .map((point) => `${Number(point.x || 0)},${Number(point.y || 0)}`)
    .join(' ')
}

function parseSavedHotspotPoints(value) {
  return String(value || "")
    .split("|")
    .map((coordinate, index) => {
      const trimmedCoordinate = String(coordinate || "").trim()
      if (!trimmedCoordinate) {
        return null
      }

      const [answerPrefix, pointValue] = trimmedCoordinate.includes(":")
        ? trimmedCoordinate.split(":", 2)
        : ["", trimmedCoordinate]
      const [x, y] = String(pointValue || "").split(";").map((part) => Number(part))
      if (!Number.isFinite(x) || !Number.isFinite(y)) {
        return null
      }

      return {
        answerId: Number(answerPrefix || 0) || 0,
        x,
        y,
        label: index + 1,
      }
    })
    .filter(Boolean)
}

function questionNumberLabel(question, index) {
  if (settings.value.hideQuestionNumber) {
    return t("Question")
  }

  return `${t("Question")} ${question.position || index + 1}`
}

function isRadioChoice(question) {
  return [1, 10, 17, 21].includes(Number(question.type))
}

function isCheckboxChoice(question) {
  return [2, 9, 14].includes(Number(question.type))
}

function isTrueFalseQuestion(question) {
  return [11, 12, 22].includes(Number(question.type))
}

function isDegreeCertaintyQuestion(question) {
  return Number(question.type) === 22
}

function isFillBlanksQuestion(question) {
  return [3, 27].includes(Number(question.type)) && question.fillBlanks && Array.isArray(question.fillBlanks.segments)
}

function isMatchingQuestion(question) {
  return [4, 19, 24, 25].includes(Number(question.type)) && question.matching
}

function isMatchingDraggableQuestion(question) {
  return [19, 25].includes(Number(question.type)) && question.matching
}

function isDraggableQuestion(question) {
  return Number(question.type) === 18 && question.draggable
}


function draggableOrientation(question) {
  const orientation = String(question.draggable?.orientation || "").toLowerCase()

  return ["h", "horizontal"].includes(orientation) ? "horizontal" : "vertical"
}

function isDraggableHorizontal(question) {
  return draggableOrientation(question) === "horizontal"
}

function draggableInitialOrder(question) {
  return (question.draggable?.items || [])
    .map((item) => Number(item.id || 0))
    .filter((itemId) => itemId > 0)
}

function draggableAnswerItems(question) {
  const items = Array.isArray(question.draggable?.items) ? question.draggable.items : []
  const itemMap = new Map(items.map((item) => [Number(item.id || 0), item]))
  const storedOrder = Array.isArray(answers.value?.[question.id]?.draggableOrder)
    ? answers.value[question.id].draggableOrder.map((itemId) => Number(itemId || 0)).filter((itemId) => itemId > 0)
    : []

  const orderedItems = []
  const usedIds = new Set()
  for (const itemId of storedOrder) {
    if (itemMap.has(itemId) && !usedIds.has(itemId)) {
      orderedItems.push(itemMap.get(itemId))
      usedIds.add(itemId)
    }
  }

  for (const item of items) {
    const itemId = Number(item.id || 0)
    if (itemId > 0 && !usedIds.has(itemId)) {
      orderedItems.push(item)
    }
  }

  return orderedItems
}

function setDraggableOrder(question, items) {
  if (!answers.value?.[question.id]) {
    return
  }

  answers.value[question.id].draggableOrder = items
    .map((item) => Number(item.id || item || 0))
    .filter((itemId) => itemId > 0)
}

function moveDraggableItem(question, fromIndex, toIndex) {
  const items = draggableAnswerItems(question)
  if (fromIndex < 0 || toIndex < 0 || fromIndex >= items.length || toIndex >= items.length) {
    return
  }

  const [item] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, item)
  setDraggableOrder(question, items)
}

function onDraggableOrderDragStart(itemId) {
  draggedDraggableItemId.value = Number(itemId || 0)
}

function onDraggableOrderDrop(question, targetItemId) {
  const draggedItemId = Number(draggedDraggableItemId.value || 0)
  draggedDraggableItemId.value = null
  const safeTargetItemId = Number(targetItemId || 0)
  if (draggedItemId <= 0 || safeTargetItemId <= 0 || draggedItemId === safeTargetItemId) {
    return
  }

  const items = draggableAnswerItems(question)
  const fromIndex = items.findIndex((item) => Number(item.id || 0) === draggedItemId)
  const toIndex = items.findIndex((item) => Number(item.id || 0) === safeTargetItemId)
  if (fromIndex < 0 || toIndex < 0) {
    return
  }

  const [item] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, item)
  setDraggableOrder(question, items)
}

function isDropdownQuestion(question) {
  return [28, 29].includes(Number(question.type)) && question.dropdown
}

function currentCalculatedVariation(question) {
  const variations = Array.isArray(question?.calculated?.variations) ? question.calculated.variations : []
  const questionAnswer = answers.value?.[question?.id] || {}
  const selectedAnswerId = Number(questionAnswer.calculatedAnswerId || question?.calculated?.answerId || 0)

  if (selectedAnswerId > 0) {
    const selectedVariation = variations.find((variation) => Number(variation.id) === selectedAnswerId)
    if (selectedVariation) {
      return selectedVariation
    }
  }

  return variations[0] || { id: question?.calculated?.answerId || null, text: question?.calculated?.text || "" }
}

function parseSavedCalculatedAnswer(value) {
  const parts = String(value || "").split(":")
  if (parts.length >= 2) {
    const answerId = Number(parts.shift() || 0)
    return [answerId, parts.join(":")]
  }

  return [0, String(value || "")]
}

function isCalculatedQuestion(question) {
  return Number(question.type) === 16
}

function isOpenQuestion(question) {
  return Number(question.type) === 5
}

function isOnlyofficeQuestion(question) {
  return Number(question?.type) === 30
}

function isUploadQuestion(question) {
  return Number(question.type) === 23
}

function isOralQuestion(question) {
  return Number(question.type) === 13
}

function isStructuralQuestion(question) {
  return [15, 31].includes(Number(question?.type))
}

function isMediaQuestion(question) {
  return Number(question.type) === 15
}

function isAnnotationQuestion(question) {
  return Number(question.type) === 20 && question.annotation
}

function isHotspotQuestion(question) {
  return [6, 8, 26].includes(Number(question.type)) && question.hotspot
}

function isHotspotDelineationQuestion(question) {
  return Number(question.type) === 8 && question.hotspot
}

function isReadingQuestion(question) {
  return Number(question.type) === 21 && question.reading
}

function isPageBreak(question) {
  return Number(question.type) === 31
}

function trueFalseOptions(question) {
  if (Array.isArray(question.trueFalseOptions) && question.trueFalseOptions.length > 0) {
    return question.trueFalseOptions.map((option) => ({
      value: Number(option.id || option.position),
      position: Number(option.position || 0),
      label: displayText(option.title),
    }))
  }

  return [
    { value: 1, position: 1, label: t("True") },
    { value: 2, position: 2, label: t("False") },
    { value: 3, position: 3, label: t("Don't know") },
  ]
}

function trueFalseChoiceOptions(question) {
  const options = trueFalseOptions(question)

  if (isDegreeCertaintyQuestion(question)) {
    return options.filter((option) => [1, 2].includes(Number(option.position || option.value)))
  }

  return options
}

function degreeCertaintyOptions(question) {
  return trueFalseOptions(question).filter((option) => {
    const position = Number(option.position || option.value)

    return position >= 3 && position < 9
  })
}

function syncRuntimeSettingsEffects() {
  syncCopyPasteProtection()
  syncKeepAlivePing()
}

function stopRuntimeSettingsEffects() {
  stopCopyPasteProtection()
  stopKeepAlivePing()
}

function syncCopyPasteProtection() {
  stopCopyPasteProtection()
  if (canManage.value || !activeAttempt.value || true !== settings.value.preventCopyPaste) {
    return
  }

  const preventDefault = (event) => {
    event.preventDefault()
    event.stopPropagation()
  }
  const preventKeyboardShortcut = (event) => {
    if (!event?.ctrlKey && !event?.metaKey) {
      return
    }

    const key = String(event.key || "").toLowerCase()
    if (["a", "c", "p", "s", "u", "v", "x"].includes(key)) {
      preventDefault(event)
    }
  }
  const handlers = [
    [document, "copy", preventDefault],
    [document, "cut", preventDefault],
    [document, "paste", preventDefault],
    [document, "contextmenu", preventDefault],
    [document, "dragstart", preventDefault],
    [document, "keydown", preventKeyboardShortcut],
  ]

  for (const [target, eventName, handler] of handlers) {
    target.addEventListener(eventName, handler, true)
  }

  copyPasteCleanup = () => {
    for (const [target, eventName, handler] of handlers) {
      target.removeEventListener(eventName, handler, true)
    }
  }
}

function stopCopyPasteProtection() {
  if (copyPasteCleanup) {
    copyPasteCleanup()
    copyPasteCleanup = null
  }
}

function syncKeepAlivePing() {
  stopKeepAlivePing()
  const interval = Number(settings.value.keepAlivePingInterval || 0)
  if (interval <= 0 || canManage.value || !activeAttempt.value) {
    return
  }

  const sendKeepAlivePing = () => {
    fetch("/main/inc/ajax/keepalive.ajax.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
    }).catch(() => {})
  }

  keepAliveTimer = window.setInterval(sendKeepAlivePing, Math.max(60, interval) * 1000)
}

function stopKeepAlivePing() {
  if (keepAliveTimer) {
    window.clearInterval(keepAliveTimer)
    keepAliveTimer = null
  }
}

function handleRuntimeContentClick(event) {
  const target = event.target
  if (
    !(target instanceof HTMLImageElement)
    || !imageZoomEnabled.value
    || !target.closest(".exercise-runtime-html")
  ) {
    return
  }

  const source = target.getAttribute("data-zoom-image") || target.currentSrc || target.getAttribute("src") || ""
  if (!source) {
    return
  }

  event.preventDefault()
  zoomImageSrc.value = source
  zoomImageAlt.value = target.getAttribute("alt") || target.getAttribute("title") || t("Image")
  isZoomDialogVisible.value = true
}

function scheduleRuntimeHtmlEnhancement() {
  if (typeof document === "undefined") {
    return
  }

  nextTick(() => {
    markZoomableRuntimeImages()
    applyGlossaryTermsToRuntimeHtml()
  })
}

function markZoomableRuntimeImages() {
  document.querySelectorAll(".exercise-runtime-html img").forEach((image) => {
    if (!(image instanceof HTMLImageElement)) {
      return
    }

    if (!imageZoomEnabled.value) {
      image.removeAttribute("data-exercise-runtime-zoom")
      return
    }

    const source = image.getAttribute("data-zoom-image") || image.currentSrc || image.getAttribute("src") || ""
    if (!source) {
      return
    }

    image.setAttribute("data-exercise-runtime-zoom", "1")
    if (!image.getAttribute("title")) {
      image.setAttribute("title", t("Image"))
    }
  })
}

function applyGlossaryTermsToRuntimeHtml() {
  if (!glossaryEnabled.value) {
    return
  }

  const terms = normalizedGlossaryTerms()
  if (!terms.length) {
    return
  }

  const termPattern = terms.map((term) => escapeRegExp(term.title)).join("|")
  const expression = new RegExp(`(^|[^\\p{L}\\p{N}_])(${termPattern})(?=$|[^\\p{L}\\p{N}_])`, "giu")
  const descriptions = new Map(terms.map((term) => [term.title.toLowerCase(), term.description]))

  document.querySelectorAll(".exercise-runtime-html").forEach((container) => {
    if (!(container instanceof HTMLElement)) {
      return
    }

    wrapGlossaryTermsInContainer(container, expression, descriptions)
  })
}

function normalizedGlossaryTerms() {
  const seen = new Set()

  return glossaryTerms.value
    .map((term) => ({
      title: displayText(term?.title || ""),
      description: displayText(term?.description || ""),
    }))
    .filter((term) => term.title.length > 1)
    .filter((term) => {
      const key = term.title.toLowerCase()
      if (seen.has(key)) {
        return false
      }
      seen.add(key)

      return true
    })
    .sort((left, right) => right.title.length - left.title.length)
}

function wrapGlossaryTermsInContainer(container, expression, descriptions) {
  const walker = document.createTreeWalker(
    container,
    NodeFilter.SHOW_TEXT,
    {
      acceptNode(node) {
        const parent = node.parentElement
        if (
          !parent
          || parent.closest(".glossary-term, a, button, input, select, textarea, script, style, svg")
        ) {
          return NodeFilter.FILTER_REJECT
        }

        expression.lastIndex = 0
        const hasMatch = expression.test(node.nodeValue || "")
        expression.lastIndex = 0

        return hasMatch ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
      },
    },
  )

  const textNodes = []
  while (walker.nextNode()) {
    textNodes.push(walker.currentNode)
  }

  textNodes.forEach((node) => wrapGlossaryTermsInTextNode(node, expression, descriptions))
}

function wrapGlossaryTermsInTextNode(node, expression, descriptions) {
  const text = node.nodeValue || ""
  const fragment = document.createDocumentFragment()
  let lastIndex = 0
  let matched = false

  expression.lastIndex = 0
  let match = expression.exec(text)
  while (match) {
    const leadingText = match[1] || ""
    const matchedText = match[2] || ""
    const leadingEnd = match.index + leadingText.length
    const matchedEnd = leadingEnd + matchedText.length

    fragment.append(document.createTextNode(text.slice(lastIndex, leadingEnd)))

    const term = document.createElement("span")
    term.className = "glossary-term"
    term.textContent = matchedText
    term.title = descriptions.get(matchedText.toLowerCase()) || matchedText
    fragment.append(term)

    lastIndex = matchedEnd
    matched = true
    match = expression.exec(text)
  }

  if (!matched) {
    return
  }

  fragment.append(document.createTextNode(text.slice(lastIndex)))
  node.parentNode?.replaceChild(fragment, node)
}


function submitDisabled() {
  return false
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

onBeforeUnmount(() => {
  stopCountdownTimer()
  stopQuestionCountdownTimer()
  stopRuntimeSettingsEffects()
})

onMounted(loadRuntime)

watch(
  () => [
    route.params.exerciseId,
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.attemptId,
    route.query.preview,
    route.query.isStudentView,
  ],
  () => loadRuntime(),
)

watch(
  () => [currentTimedQuestionId.value, activeAttempt.value?.attemptId, activeAttempt.value?.status],
  () => syncQuestionCountdown(),
)

watch(
  () => [
    currentQuestionIndex.value,
    reviewQueueIndex.value,
    isFeedbackDialogVisible.value,
    imageZoomEnabled.value,
    glossaryEnabled.value,
    glossaryTerms.value.length,
    visibleQuestions.value.map((question) => question.id).join(","),
  ],
  () => {
    scheduleRuntimeHtmlEnhancement()
    prepareVisibleOnlyofficeDocuments()
  },
)
</script>

<style scoped>
.exercise-runtime-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-runtime-html :deep(img[data-exercise-runtime-zoom="1"]) {
  cursor: zoom-in;
}

.exercise-runtime-html :deep(.glossary-term) {
  cursor: help;
  font-weight: 500;
  color: #2563eb;
  border-bottom: 1px dotted currentColor;
}

.exercise-runtime-html :deep(.glossary-term:hover) {
  color: #1d4ed8;
  border-bottom-style: solid;
}

.exercise-runtime-html :deep(p) {
  margin-bottom: 0.5rem;
}

.exercise-runtime-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
