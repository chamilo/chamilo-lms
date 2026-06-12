<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        :label="t('Back to exercises')"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.overview"
        :label="t('Open legacy exercise')"
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
        </div>
      </header>

      <div
        v-if="usesLegacySubmit && activeAttempt"
        class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <p>
            {{ t("This Vue player can save draft answers for simple question types. Final submission, scoring, results and review still use the legacy exercise runtime in this batch.") }}
          </p>
          <BaseButton
            v-if="legacyUrls.overview"
            :label="t('Continue in legacy exercise')"
            :to-url="legacyUrls.overview"
            icon="play-box-outline"
            type="primary"
          />
        </div>
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="space-y-1 text-sm text-gray-700">
            <div class="font-semibold text-gray-90">
              {{ activeAttempt ? t("Vue attempt started") : t("Vue attempt") }}
            </div>
            <div v-if="canManage">
              {{ t("Teacher preview does not create a tracked attempt.") }}
            </div>
            <div v-else-if="activeAttempt">
              {{ t("Attempt") }} #{{ activeAttempt.attemptId }} · {{ progressLabel }}
              <span v-if="activeAttempt.remainingSeconds !== null && activeAttempt.remainingSeconds !== undefined">
                · {{ t("Time left") }}: {{ formatSeconds(activeAttempt.remainingSeconds) }}
              </span>
            </div>
            <div v-else>
              {{ t("Start or resume a Vue attempt. Draft answers for simple question types can be saved before final legacy submission.") }}
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
            <div v-if="finishMessage" class="text-support-4">
              {{ finishMessage }}
            </div>
            <div v-if="finishError" class="text-danger">
              {{ finishError }}
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="!canManage && !activeAttempt"
              :disabled="isStartingAttempt"
              :label="isStartingAttempt ? t('Starting') : t('Start Vue attempt')"
              icon="play-box-outline"
              type="primary"
              @click="startAttempt"
            />
            <BaseButton
              v-if="legacyUrls.overview"
              :label="t('Use legacy runtime')"
              :to-url="legacyUrls.overview"
              icon="play-box-outline"
              type="secondary"
            />
          </div>
        </div>
      </div>

      <form v-if="canManage || activeAttempt" class="space-y-4" @submit.prevent="submitDisabled">
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
              <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800">
                {{ t("Sequence ordering is displayed here as a temporary list. Drag and drop submission will be migrated in the submit processor batch.") }}
              </div>
              <ol class="list-decimal space-y-2 pl-6">
                <li
                  v-for="item in question.draggable.items"
                  :key="item.id"
                  class="exercise-runtime-html"
                  v-html="item.answer"
                />
              </ol>
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
                {{ t("Selected file") }}: {{ answers[question.id].uploadFileName }}
              </div>
              <div
                v-if="answers[question.id]?.uploadedFiles?.length"
                class="space-y-2 rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
              >
                <div class="font-semibold">{{ t("Uploaded file") }}</div>
                <div
                  v-for="file in answers[question.id].uploadedFiles"
                  :key="file.id || file.name"
                >
                  {{ file.name || t("Uploaded file") }}
                </div>
              </div>
            </div>

            <div v-else-if="isOralQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-gray-20 bg-gray-10 p-3">
                <div class="mb-2 text-sm font-semibold text-gray-800">
                  {{ t("Record oral answer") }}
                </div>
                <AudioRecorder
                  :multiple="false"
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
                  {{ file.name || t("Uploaded audio") }}
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
                    <div>{{ t("Paths") }}: {{ annotationPaths(question).length }}</div>
                    <div>{{ t("Texts") }}: {{ annotationTexts(question).length }}</div>
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
                    <button
                      v-for="point in hotspotPlacedPoints(question)"
                      :key="`${question.id}-hotspot-point-${point.answerId || point.label}`"
                      class="absolute flex h-7 w-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-primary text-xs font-bold text-white shadow"
                      :style="hotspotPointStyle(question, point)"
                      type="button"
                      :title="t('Remove point')"
                      @click.stop="removeHotspotPoint(question, point.answerId)"
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
                {{ t("Reading speed") }}: {{ question.reading.speed }} {{ t("words per minute") }}
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
              {{ t("This question type is pending runtime rendering in Vue.") }}
            </div>
          </div>
        </article>

        <div class="flex flex-wrap justify-between gap-2 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <BaseButton
            :disabled="!canMovePrevious || isSavingAnswer"
            :label="previousNavigationLabel"
            icon="back"
            type="secondary"
            @click="goToPreviousQuestion"
          />
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="!canManage && activeAttempt"
              :disabled="isSavingAnswer || !visibleQuestions.some(isDraftSaveSupported)"
              :label="isSavingAnswer ? t('Saving') : t('Save draft')"
              icon="check"
              type="success"
              @click="saveVisibleAnswers"
            />
            <BaseButton
              v-if="canMoveNext"
              :disabled="isSavingAnswer"
              :label="nextNavigationLabel"
              type="primary"
              @click="goToNextQuestion"
            />
            <BaseButton
              v-if="!canManage && activeAttempt && canFinishCurrentPage"
              :disabled="!canSubmit || isSavingAnswer || isFinishingAttempt"
              :label="isFinishingAttempt ? t('Finishing') : t('Finish in Vue')"
              icon="check"
              type="primary"
              @click="finishAttempt"
            />
            <BaseButton
              v-if="legacyUrls.overview"
              :label="t('Continue in legacy exercise')"
              :to-url="legacyUrls.overview"
              icon="play-box-outline"
              type="secondary"
            />
          </div>
        </div>
      </form>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
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
const draggedMatchingOptionId = ref(null)
const selectedMatchingOptions = ref({})

const questionMap = computed(() => new Map(questions.value.map((question) => [Number(question.id), question])))

const runtimePages = computed(() => {
  const pages = settings.value?.runtimePages

  return Array.isArray(pages) ? pages : []
})

const usesPagedNavigation = computed(() => {
  if (runtimePages.value.length > 0 && (settings.value.effectiveOneQuestionPerPage || settings.value.usesStructuralPages)) {
    return true
  }

  return true === settings.value.oneQuestionPerPage
})

const currentRuntimePage = computed(() => {
  if (runtimePages.value.length === 0) {
    return null
  }

  return runtimePages.value[Math.min(Math.max(0, currentQuestionIndex.value), runtimePages.value.length - 1)] || null
})

const visibleQuestions = computed(() => {
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
const navigationTotal = computed(() => usesPagedNavigation.value ? Math.max(1, runtimePages.value.length || visibleQuestionTotal.value) : visibleQuestionTotal.value)
const canMovePrevious = computed(() => usesPagedNavigation.value && currentQuestionIndex.value > 0)
const canMoveNext = computed(() => usesPagedNavigation.value && currentQuestionIndex.value < navigationTotal.value - 1)
const canFinishCurrentPage = computed(() => !usesPagedNavigation.value || !canMoveNext.value)
const answerableQuestions = computed(() => questions.value.filter((question) => !isStructuralQuestion(question)))
const progressLabel = computed(() => {
  if (usesPagedNavigation.value && currentRuntimePage.value && (settings.value.usesStructuralPages || visibleQuestions.value.length > 1)) {
    return `${t("Page")} ${currentQuestionIndex.value + 1} / ${navigationTotal.value}`
  }

  return `${t("Question")} ${currentQuestionIndex.value + 1} / ${navigationTotal.value}`
})
const previousNavigationLabel = computed(() => usesPagedNavigation.value && (settings.value.usesStructuralPages || visibleQuestions.value.length > 1) ? t("Previous page") : t("Previous question"))
const nextNavigationLabel = computed(() => usesPagedNavigation.value && (settings.value.usesStructuralPages || visibleQuestions.value.length > 1) ? t("Next page") : t("Next question"))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    origin: getQueryValue(route.query.origin),
    learnpath_id: getQueryValue(route.query.learnpath_id),
    learnpath_item_id: getQueryValue(route.query.learnpath_item_id),
    learnpath_item_view_id: getQueryValue(route.query.learnpath_item_view_id),
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
    canStartAttempt.value = true === response.canStartAttempt
    activeAttempt.value = response.attempt || null
    canSubmit.value = true === response.canSubmit
    usesLegacySubmit.value = true === response.usesLegacySubmit && Boolean(activeAttempt.value)
    applyAttemptState(activeAttempt.value)
    initializeAnswerState()
    applySavedAnswers(activeAttempt.value?.savedAnswers || {})
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
      applyAttemptState(response)
      reorderQuestionsFromAttempt(response.questionIds || [])
      initializeAnswerState()
      applySavedAnswers(response.savedAnswers || {})
      return
    }

    if (response.usesLegacyRuntime && response.legacyUrls) {
      legacyUrls.value = { ...legacyUrls.value, ...response.legacyUrls }
    }

    canSubmit.value = false
    usesLegacySubmit.value = true === response.usesLegacyRuntime
    attemptError.value = response.message || t("Could not start the Vue attempt")
  } catch (error) {
    console.error("Error starting exercise attempt", error)
    attemptError.value = t("Could not start the Vue attempt")
  } finally {
    isStartingAttempt.value = false
  }
}

function applyAttemptState(attempt) {
  if (!attempt) {
    currentQuestionIndex.value = 0
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
  if (!canMovePrevious.value) {
    return
  }

  if (await saveVisibleAnswers()) {
    currentQuestionIndex.value -= 1
  }
}

async function goToNextQuestion() {
  if (!canMoveNext.value) {
    return
  }

  if (await saveVisibleAnswers()) {
    currentQuestionIndex.value += 1
  }
}

async function saveVisibleAnswers() {
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

  try {
    for (const question of saveTargets) {
      await saveQuestionDraftAnswer(question)
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

async function finishAttempt() {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  if (canManage.value || !exerciseId || !attemptId) {
    return
  }

  if (!(await saveVisibleAnswers())) {
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
    finishMessage.value = response.message ? t(response.message) : t("Attempt finished")

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
  }
}

async function saveQuestionDraftAnswer(question) {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  if (!exerciseId || !attemptId || !question?.id) {
    return
  }

  const response = isUploadQuestion(question) || isOralQuestion(question)
    ? await saveUploadQuestionAnswer(question, exerciseId, attemptId)
    : await exerciseService.saveExerciseRuntimeAnswer(
      {
        exerciseId,
        attemptId,
        questionId: Number(question.id),
        answer: buildAnswerPayload(question),
        secondsSpent: 0,
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
}


async function saveUploadQuestionAnswer(question, exerciseId, attemptId) {
  const questionAnswer = answers.value[question.id] || {}
  if (!questionAnswer.uploadFile && !questionAnswer.oralFile) {
    return null
  }

  const formData = new FormData()
  formData.append("questionId", String(Number(question.id)))
  formData.append("secondsSpent", "0")
  formData.append("file", questionAnswer.uploadFile || questionAnswer.oralFile)

  const response = await exerciseService.uploadExerciseRuntimeAnswer(
    formData,
    getContextParams(),
    exerciseId,
    attemptId,
  )

  if (response?.success) {
    questionAnswer.uploadFile = null
    questionAnswer.uploadFileName = ""
    questionAnswer.oralFile = null
    questionAnswer.oralFileName = ""
    questionAnswer.oralPreviewUrl = ""
    questionAnswer.uploadedFiles = Array.isArray(response.files) ? response.files : []
  }

  return response
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

  return {}
}

function isDraftSaveSupported(question) {
  return isRadioChoice(question)
    || isCheckboxChoice(question)
    || isDraftTrueFalseQuestion(question)
    || isFillBlanksQuestion(question)
    || isMatchingQuestion(question)
    || isDropdownQuestion(question)
    || isCalculatedQuestion(question)
    || isHotspotQuestion(question)
    || isAnnotationQuestion(question)
    || isDraftFreeAnswerQuestion(question)
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
    questionAnswer.selectedHotspotAnswerId = firstMissingHotspotZoneId(question) || hotspotZones(question)[0]?.id || null
    return
  }

  if (isOpenQuestion(question)) {
    questionAnswer.text = rows[0]?.answer || ""
    return
  }

  if (isUploadQuestion(question)) {
    questionAnswer.uploadedFiles = rows.length > 0 ? [{ name: t("Uploaded file") }] : []
    return
  }

  if (isOralQuestion(question)) {
    questionAnswer.uploadedFiles = rows.length > 0 ? [{ name: t("Uploaded audio") }] : []
  }
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
      dropdown: "",
      calculated: "",
      calculatedAnswerId: currentCalculatedVariation(question).id || question.calculated?.answerId || null,
      text: "",
      uploadFile: null,
      uploadFileName: "",
      uploadedFiles: [],
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
      selectedHotspotAnswerId: hotspotZones(question)[0]?.id || null,
    }

    if (isMatchingDraggableQuestion(question)) {
      nextSelectedMatchingOptions[question.id] = question.matching?.options?.[0]?.id || null
    }
  }

  answers.value = nextAnswers
  selectedMatchingOptions.value = nextSelectedMatchingOptions
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
  const selectedAnswerId = Number(questionAnswer?.selectedHotspotAnswerId || 0)
  if (!questionAnswer || !image || selectedAnswerId <= 0) {
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

function removeHotspotPoint(question, answerId) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
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
  return [6, 26].includes(Number(question.type)) && question.hotspot
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

onMounted(loadRuntime)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid],
  () => loadRuntime(),
)
</script>

<style scoped>
.exercise-runtime-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-runtime-html :deep(p) {
  margin-bottom: 0.5rem;
}

.exercise-runtime-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
