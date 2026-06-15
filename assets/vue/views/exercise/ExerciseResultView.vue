<template>
  <section
    class="exercise-result-page space-y-5"
    :class="{ 'exercise-result-print-mode': shouldAutoPrint() }"
  >
    <div
      v-if="!isLearnpathContext"
      class="exercise-result-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm"
    >
      <BaseButton
        :label="t('Back to exercises')"
        :route="{ name: 'ExerciseList', params: getBaseRouteParams(), query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        :label="t('Open exercise player')"
        :route="{ name: 'ExercisePlayer', params: getPlayerRouteParams(), query: getContextParams() }"
        icon="play-box-outline"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        :label="t('Export PDF')"
        icon="file-pdf"
        only-icon
        size="small"
        type="primary-text"
        @click="downloadResultPdf"
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

    <div
      v-if="correctionError"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ correctionError }}
    </div>

    <div
      v-if="correctionMessage"
      class="rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success"
    >
      {{ correctionMessage }}
    </div>

    <template v-if="!isLoading && !errorMessage">
      <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
        <div class="border-l-4 border-l-primary p-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
              <h1 class="text-2xl font-semibold text-gray-90">
                {{ displayText(title, t("Exercise result")) }}
              </h1>
              <div
                v-if="description"
                class="exercise-result-html text-sm text-gray-700"
                v-html="description"
              />
            </div>

            <div
              v-if="visibility.showTotalScore && attempt.passed !== null && attempt.passed !== undefined"
              class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold"
              :class="attempt.passed ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
            >
              <span
                class="mdi"
                :class="attempt.passed ? 'mdi-check-circle' : 'mdi-alert-circle'"
              />
              {{ attempt.passed ? t("Passed") : t("Failed") }}
            </div>
          </div>

          <div
            v-if="attempt.textWhenFinished"
            class="exercise-result-html mt-4 rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
            v-html="attempt.textWhenFinished"
          />

          <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Attempt") }}
                  </div>
                  <div class="mt-1 text-xl font-semibold text-gray-90">#{{ attempt.attemptId }}</div>
                  <div class="text-xs text-gray-600">{{ t(attempt.status || "completed") }}</div>
                </div>
                <span class="mdi mdi-clipboard-check-outline text-2xl text-primary" />
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Score") }}
                  </div>
                  <div
                    v-if="visibility.showTotalScore"
                    class="mt-1 text-xl font-semibold text-gray-90"
                  >
                    {{ formatNumber(attempt.score) }} / {{ formatNumber(attempt.maxScore) }}
                  </div>
                  <div
                    v-else
                    class="mt-1 text-sm text-gray-600"
                  >
                    {{ t("The score is hidden for this exercise.") }}
                  </div>
                </div>
                <span class="mdi mdi-counter text-2xl text-primary" />
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Percentage") }}
                  </div>
                  <div
                    v-if="visibility.showTotalScore"
                    class="mt-1 text-xl font-semibold text-gray-90"
                  >
                    {{ formatNumber(attempt.percentage) }}%
                  </div>
                  <div
                    v-else
                    class="mt-1 text-sm text-gray-600"
                  >
                    —
                  </div>
                </div>
                <span class="mdi mdi-percent-outline text-2xl text-primary" />
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Duration") }}
                  </div>
                  <div class="mt-1 text-xl font-semibold text-gray-90">
                    {{ formatSeconds(attempt.duration) }}
                  </div>
                  <div
                    v-if="attempt.completedAt"
                    class="text-xs text-gray-600"
                  >
                    {{ formatDate(attempt.completedAt) }}
                  </div>
                </div>
                <span class="mdi mdi-clock-outline text-2xl text-primary" />
              </div>
            </div>
          </div>

          <div
            v-if="!visibility.showCorrections"
            class="mt-4 rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
          >
            {{ t("Corrections are hidden according to the exercise result settings.") }}
          </div>
        </div>
      </header>

      <div
        v-if="visibility.showRadar"
        class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      >
        {{ t("This exercise uses the radar/spiderweb result mode. The chart is not available on this page.") }}
      </div>

      <div
        v-if="visibility.showRanking"
        class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
      >
        <div class="border-b border-gray-20 bg-gray-10 p-4">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Ranking") }}</h2>
        </div>
        <div class="overflow-x-auto p-4">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-20 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <th class="px-3 py-2 text-right">{{ t("Position") }}</th>
                <th class="px-3 py-2">{{ t("Username") }}</th>
                <th class="px-3 py-2 text-right">{{ t("Score") }}</th>
                <th class="px-3 py-2 text-center">{{ t("Date") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in ranking"
                :key="`${item.position}-${item.userId}`"
                class="border-b border-gray-20"
                :class="item.currentUser ? 'bg-warning/10' : ''"
              >
                <td class="px-3 py-2 text-right font-semibold">{{ item.position }}</td>
                <td class="px-3 py-2">{{ item.user }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(item.score) }} / {{ formatNumber(item.maxScore) }}</td>
                <td class="px-3 py-2 text-center">{{ formatDate(item.date) }}</td>
              </tr>
              <tr v-if="!ranking.length">
                <td
                  class="px-3 py-3 text-center text-gray-500"
                  colspan="4"
                >
                  {{ t("No ranking data available") }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="!questions.length && visibility.showQuestionDetails === false"
        class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      >
        {{ t("Question details are hidden according to the exercise result settings.") }}
      </div>

      <div
        v-else
        class="space-y-4"
      >
        <article
          v-for="(question, index) in questions"
          :key="question.id"
          class="overflow-hidden rounded-xl border bg-white shadow-sm"
          :class="questionCardClass(question)"
        >
          <div
            v-if="shouldShowParentMedia(question, index)"
            class="border-b border-dashed border-gray-40 bg-gray-10 p-4"
          >
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
              {{ t("Media question") }}
            </div>
            <h2
              v-if="question.parent?.title"
              class="exercise-result-html text-lg font-semibold text-gray-90"
              v-html="question.parent.title"
            />
            <div
              v-if="question.parent?.description || question.parent?.content?.description"
              class="exercise-result-html mt-2 text-sm text-gray-700"
              v-html="question.parent.description || question.parent.content?.description"
            />
          </div>

          <div class="border-b border-gray-20 bg-gray-10 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div class="flex gap-3">
                <span
                  class="mdi mt-1 text-xl"
                  :class="questionStatusIconClass(question)"
                />
                <div class="space-y-1">
                  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <span>{{ questionLabel(question.position) }}</span>
                    <span>·</span>
                    <span>{{ t(question.typeLabel) }}</span>
                  </div>
                  <h2
                    class="exercise-result-html text-lg font-semibold text-gray-90"
                    v-html="question.title"
                  />
                  <div
                    v-if="question.description"
                    class="exercise-result-html text-sm text-gray-700"
                    v-html="question.description"
                  />
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-2 md:justify-end">
                <span
                  v-if="visibility.showQuestionScore && question.score !== null && question.maxScore !== null"
                  class="rounded-full border px-3 py-1 text-xs font-semibold"
                  :class="questionScoreClass(question)"
                >
                  {{ t("Score") }}: {{ formatNumber(question.score) }} / {{ formatNumber(question.maxScore) }}
                </span>
                <span
                  v-if="visibility.showQuestionScore && questionResultBadgeLabel(question)"
                  class="rounded-full px-3 py-1 text-xs font-semibold"
                  :class="questionResultBadgeClass(question)"
                >
                  {{ questionResultBadgeLabel(question) }}
                </span>
                <span
                  v-if="question.pendingCorrection"
                  class="rounded-full bg-warning/10 px-3 py-1 text-xs font-semibold text-warning"
                >
                  {{ t("Pending correction") }}
                </span>
              </div>
            </div>
          </div>

          <div class="space-y-3 p-4">
            <template v-if="question.answer.kind === 'choice' || question.answer.kind === 'dropdown'">
              <div
                v-for="choice in question.answer.choices || question.answer.options"
                :key="choice.id"
                class="rounded-lg border p-3 text-sm"
                :class="choiceClass(choice)"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div class="flex flex-1 gap-2">
                    <span
                      class="mdi mt-0.5 text-base"
                      :class="choiceIconClass(choice)"
                    />
                    <div class="exercise-result-html flex-1" v-html="choice.answer" />
                  </div>
                  <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    <span
                      v-if="visibility.showStudentAnswers !== false && choice.selected"
                      class="rounded-full bg-info/10 px-2 py-1 text-info"
                    >
                      {{ t("Your answer") }}
                    </span>
                    <span
                      v-if="choice.correct"
                      class="rounded-full bg-success/10 px-2 py-1 text-success"
                    >
                      {{ t("Correct answer") }}
                    </span>
                  </div>
                </div>
                <div
                  v-if="choice.comment"
                  class="exercise-result-html mt-2 rounded bg-white/70 p-2 text-xs text-gray-700"
                  v-html="choice.comment"
                />
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'true_false'">
              <div
                v-for="choice in question.answer.choices"
                :key="choice.id"
                class="rounded-lg border p-3 text-sm"
                :class="trueFalseClass(choice)"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div class="exercise-result-html font-medium text-gray-90" v-html="choice.answer" />
                  <span
                    class="mdi text-base"
                    :class="trueFalseIconClass(choice)"
                  />
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                  <span
                    v-if="visibility.showStudentAnswers !== false"
                    class="rounded-full bg-info/10 px-2 py-1 text-info"
                  >
                    {{ t("Your answer") }}: {{ choice.selectedOptionLabel || t("No answer") }}
                  </span>
                  <span
                    v-if="choice.selectedDegreeLabel"
                    class="rounded-full bg-support-1 px-2 py-1 text-support-4"
                  >
                    {{ t("Degree of certainty that my answer will be considered correct") }}: {{ choice.selectedDegreeLabel }}
                  </span>
                  <span
                    v-if="choice.correctOptionLabel"
                    class="rounded-full bg-success/10 px-2 py-1 text-success"
                  >
                    {{ t("Correct answer") }}: {{ choice.correctOptionLabel }}
                  </span>
                </div>
                <div
                  v-if="choice.comment"
                  class="exercise-result-html mt-2 rounded bg-white/70 p-2 text-xs text-gray-700"
                  v-html="choice.comment"
                />
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'fill_blanks'">
              <div class="space-y-2 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm">
                <div
                  v-for="blank in question.answer.blanks"
                  :key="blank.position"
                  class="flex flex-col gap-2 rounded border bg-white p-3 md:flex-row md:items-center md:justify-between"
                  :class="blankClass(blank)"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="mdi text-base"
                      :class="blankIconClass(blank)"
                    />
                    <div>
                      <span class="font-semibold">{{ t("Blank {0}", [blank.position]) }}</span>
                      <template v-if="visibility.showStudentAnswers !== false">
                        : {{ blank.studentAnswer || t("No answer") }}
                      </template>
                    </div>
                  </div>
                  <div
                    v-if="blank.correctAnswer"
                    class="text-sm font-semibold text-success"
                  >
                    {{ t("Correct answer") }}: {{ blank.correctAnswer }}
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'matching'">
              <div
                v-for="prompt in question.answer.prompts"
                :key="prompt.id"
                class="rounded-lg border p-3 text-sm"
                :class="matchingClass(prompt)"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div class="exercise-result-html font-medium text-gray-90" v-html="prompt.answer" />
                  <span
                    class="mdi text-base"
                    :class="matchingIconClass(prompt)"
                  />
                </div>
                <div class="mt-2 grid gap-2 text-sm md:grid-cols-2">
                  <div
                    v-if="visibility.showStudentAnswers !== false"
                    class="rounded bg-info/10 p-2 text-info"
                  >
                    <span class="font-semibold">{{ t("Your answer") }}:</span>
                    {{ displayText(prompt.selectedOptionAnswer, t("No answer")) }}
                  </div>
                  <div
                    v-if="prompt.correctOptionAnswer"
                    class="rounded bg-success/10 p-2 text-success"
                  >
                    <span class="font-semibold">{{ t("Correct answer") }}:</span>
                    {{ displayText(prompt.correctOptionAnswer) }}
                  </div>
                </div>
                <div
                  v-if="prompt.comment"
                  class="exercise-result-html mt-2 rounded bg-white/70 p-2 text-xs text-gray-700"
                  v-html="prompt.comment"
                />
              </div>
            </template>


            <template v-else-if="question.answer.kind === 'draggable'">
              <div class="grid gap-3 md:grid-cols-2">
                <div
                  v-if="visibility.showStudentAnswers !== false"
                  class="rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <h3 class="mb-3 text-sm font-semibold text-gray-80">{{ t("Your order") }}</h3>
                  <ol
                    :class="question.answer.orientation === 'h'
                      ? 'flex gap-2 overflow-x-auto text-sm'
                      : 'list-decimal space-y-2 pl-5 text-sm'"
                  >
                    <li
                      v-for="(item, index) in question.answer.studentItems"
                      :key="`student-${item.id}`"
                      :class="question.answer.orientation === 'h'
                        ? 'flex min-w-[12rem] items-center gap-2 rounded border border-gray-20 bg-white p-2'
                        : 'exercise-result-html'"
                    >
                      <span
                        v-if="question.answer.orientation === 'h'"
                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                      >
                        {{ index + 1 }}
                      </span>
                      <span class="exercise-result-html" v-html="item.answer" />
                    </li>
                  </ol>
                  <p v-if="!question.answer.studentItems?.length" class="text-sm text-gray-500">
                    {{ t("No answer") }}
                  </p>
                </div>

                <div
                  v-if="question.answer.expectedItems?.length"
                  class="rounded-lg border border-success/30 bg-success/10 p-3"
                >
                  <h3 class="mb-3 text-sm font-semibold text-success">{{ t("Correct order") }}</h3>
                  <ol
                    :class="question.answer.orientation === 'h'
                      ? 'flex gap-2 overflow-x-auto text-sm'
                      : 'list-decimal space-y-2 pl-5 text-sm'"
                  >
                    <li
                      v-for="(item, index) in question.answer.expectedItems"
                      :key="`expected-${item.id}`"
                      :class="question.answer.orientation === 'h'
                        ? 'flex min-w-[12rem] items-center gap-2 rounded border border-success/30 bg-white p-2'
                        : 'exercise-result-html'"
                    >
                      <span
                        v-if="question.answer.orientation === 'h'"
                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-success/10 text-xs font-semibold text-success"
                      >
                        {{ index + 1 }}
                      </span>
                      <span class="exercise-result-html" v-html="item.answer" />
                    </li>
                  </ol>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'calculated'">
              <div class="space-y-3 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm">
                <div
                  v-if="question.answer.text"
                  class="exercise-result-html rounded bg-white p-3 text-gray-800"
                  v-html="question.answer.text"
                />
                <div class="grid gap-2 md:grid-cols-2">
                  <div
                    v-if="visibility.showStudentAnswers !== false"
                    class="rounded bg-info/10 p-2 text-info"
                  >
                    <span class="font-semibold">{{ t("Your answer") }}:</span>
                    {{ question.answer.studentAnswer || t("No answer") }}
                  </div>
                  <div
                    v-if="question.answer.expectedAnswer"
                    class="rounded bg-success/10 p-2 text-success"
                  >
                    <span class="font-semibold">{{ t("Correct answer") }}:</span>
                    {{ question.answer.expectedAnswer }}
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'hotspot'">
              <div class="space-y-3">
                <div
                  v-if="visibility.showStudentAnswers !== false"
                  class="rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner clicks") }}
                  </div>
                  <div
                    v-if="question.answer.imageUrl"
                    class="inline-block max-w-full rounded-lg border border-gray-20 bg-white p-2"
                  >
                    <div class="relative inline-block max-w-full">
                      <img
                        class="max-h-[32rem] max-w-full object-contain"
                        :alt="question.answer.imageName || t('Question image')"
                        :src="question.answer.imageUrl"
                        @load="onResultHotspotImageLoad(question, $event)"
                      />
                      <span
                        v-for="point in question.answer.studentPoints"
                        :key="`${question.id}-result-hotspot-${point.label}`"
                        class="absolute flex h-7 w-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-primary text-xs font-bold text-white shadow"
                        :style="resultHotspotPointStyle(question, point)"
                      >
                        {{ point.label }}
                      </span>
                    </div>
                  </div>
                  <div
                    v-else
                    class="text-sm text-gray-600"
                  >
                    {{ t("No hotspot image available") }}
                  </div>
                  <div
                    v-if="!question.answer.studentPoints?.length"
                    class="mt-2 text-sm text-gray-600"
                  >
                    {{ t("No answer") }}
                  </div>
                </div>

                <div
                  v-if="question.answer.zones?.length"
                  class="rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
                >
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Expected zones") }}
                  </div>
                  <ul class="list-disc space-y-1 pl-5">
                    <li
                      v-for="zone in question.answer.zones"
                      :key="zone.id"
                    >
                      <span class="font-semibold">{{ displayText(zone.answer, t("Zone")) }}</span>
                      <span> · {{ zone.hotspotType }}</span>
                      <span v-if="zone.score !== null && zone.score !== undefined"> · {{ t("Score") }}: {{ formatNumber(zone.score) }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'annotation'">
              <div class="space-y-3">
                <div
                  v-if="visibility.showStudentAnswers !== false"
                  class="rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner annotation") }}
                  </div>
                  <div
                    v-if="question.answer.imageUrl"
                    class="inline-block max-w-full rounded-lg border border-gray-20 bg-white p-2"
                  >
                    <div class="relative inline-block max-w-full">
                      <img
                        class="max-h-[32rem] max-w-full object-contain"
                        :alt="question.answer.imageName || t('Question image')"
                        :src="question.answer.imageUrl"
                        @load="onResultHotspotImageLoad(question, $event)"
                      />
                      <svg
                        v-if="resultAnnotationImageReady(question)"
                        class="pointer-events-none absolute inset-0 h-full w-full"
                        :viewBox="resultAnnotationViewBox(question)"
                        preserveAspectRatio="none"
                      >
                        <polyline
                          v-for="(path, pathIndex) in question.answer.paths || []"
                          :key="`${question.id}-result-annotation-path-${pathIndex}`"
                          fill="none"
                          :points="resultAnnotationPolylinePoints(path)"
                          stroke="currentColor"
                          stroke-width="3"
                          class="text-primary"
                        />
                      </svg>
                      <span
                        v-for="(textAnnotation, textIndex) in question.answer.texts || []"
                        :key="`${question.id}-result-annotation-text-${textIndex}`"
                        class="absolute -translate-x-1/2 -translate-y-1/2 rounded bg-white/90 px-2 py-1 text-xs font-semibold text-primary shadow"
                        :style="resultHotspotPointStyle(question, textAnnotation)"
                      >
                        {{ textAnnotation.text }}
                      </span>
                    </div>
                  </div>
                  <div
                    v-else
                    class="text-sm text-gray-600"
                  >
                    {{ t("No annotation image available") }}
                  </div>
                  <div
                    v-if="!(question.answer.paths?.length || question.answer.texts?.length)"
                    class="mt-2 text-sm text-gray-600"
                  >
                    {{ t("No answer") }}
                  </div>
                </div>

                <div
                  v-if="question.pendingCorrection"
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("This answer is pending teacher correction.") }}
                </div>

                <div
                  v-if="question.answer.teacherComment"
                  class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
                >
                  <div class="mb-1 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Teacher comment") }}
                  </div>
                  {{ question.answer.teacherComment }}
                </div>

                <div
                  v-if="question.canCorrect && correctionForms[question.id]"
                  class="exercise-result-correction-form rounded-lg border border-gray-20 bg-white p-4"
                >
                  <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <BaseIcon icon="edit" size="small" />
                    {{ t("Teacher correction") }}
                  </div>
                  <div
                    v-if="canUseAiCorrection(question) && aiCorrectionForms[question.id]"
                    class="mb-3 rounded-lg border border-info/30 bg-support-1 p-3"
                  >
                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-support-4">
                      <BaseIcon icon="robot-outline" size="small" />
                      {{ t("AI suggestion") }}
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                      <label class="flex min-w-[220px] flex-col gap-1 text-sm font-semibold text-gray-700">
                        {{ t("AI provider") }}
                        <select
                          v-model="aiCorrectionForms[question.id].provider"
                          class="rounded border border-gray-30 bg-white px-3 py-2 text-sm font-normal text-gray-90"
                          :disabled="aiCorrectionForms[question.id].isLoadingProviders || aiCorrectionForms[question.id].isGenerating"
                          name="ai_provider"
                          @change="saveAiCorrectionProvider(question)"
                        >
                          <option value="">{{ t("Default") }}</option>
                          <option
                            v-for="provider in aiCorrectionForms[question.id].providers"
                            :key="provider.key"
                            :value="provider.key"
                          >
                            {{ provider.label }}
                          </option>
                        </select>
                      </label>

                      <BaseButton
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :is-loading="aiCorrectionForms[question.id].isGenerating"
                        :label="aiCorrectionForms[question.id].feedback ? t('Regenerate') : t('Generate')"
                        icon="lightning-bolt"
                        type="secondary"
                        @click="generateAiCorrection(question)"
                      />

                      <BaseButton
                        v-if="aiCorrectionForms[question.id].feedback"
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :label="t('Apply suggestion')"
                        icon="arrow-down"
                        type="primary"
                        @click="applyAiCorrectionSuggestion(question)"
                      />
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].providerHint"
                      class="mt-2 text-xs text-gray-600"
                    >
                      {{ aiCorrectionForms[question.id].providerHint }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].error"
                      class="mt-3 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
                    >
                      {{ aiCorrectionForms[question.id].error }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].feedback"
                      class="mt-3 space-y-2"
                    >
                      <div
                        v-if="aiCorrectionForms[question.id].score !== null"
                        class="inline-flex items-center gap-2 rounded-full border border-info/30 bg-white px-3 py-1 text-xs font-semibold text-info"
                      >
                        {{ t("Suggested score") }}: {{ aiCorrectionForms[question.id].score }} / {{ formatNumber(question.maxScore || 0) }}
                      </div>
                      <BaseTextArea
                        v-model="aiCorrectionForms[question.id].feedback"
                        :id="aiCorrectionFeedbackInputId(question)"
                        :label="t('Suggested feedback')"
                        name="ai_feedback"
                        rows="4"
                      />
                    </div>
                  </div>
                  <div class="grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-start">
                    <BaseInputNumber
                      v-model="correctionForms[question.id].marks"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionScoreInputId(question)"
                      :label="t('Correction score')"
                      :max="Number(question.maxScore || 0)"
                      :min="0"
                      :step="0.5"
                      name="correction_score"
                    />
                    <BaseTextArea
                      v-model="correctionForms[question.id].teacherComment"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionCommentInputId(question)"
                      :label="t('Teacher comment')"
                      name="teacher_comment"
                      rows="3"
                    />
                    <BaseButton
                      :disabled="correctionSavingQuestionId === question.id"
                      :is-loading="correctionSavingQuestionId === question.id"
                      :label="t('Save correction')"
                      icon="content-save"
                      type="success"
                      @click="saveManualCorrection(question)"
                    />
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'oral_expression'">
              <div class="space-y-3">
                <div
                  v-if="visibility.showStudentAnswers !== false"
                  class="rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner audio") }}
                  </div>
                  <div
                    v-if="question.answer.files?.length"
                    class="space-y-3"
                  >
                    <div
                      v-for="file in question.answer.files"
                      :key="file.id || file.name"
                      class="space-y-2"
                    >
                      <audio
                        class="max-w-full"
                        controls
                        :src="file.inlineUrl || file.url"
                      />
                      <a
                        class="inline-flex items-center gap-2 rounded border border-primary/30 bg-white px-3 py-2 text-sm font-semibold text-primary hover:bg-support-1"
                        :href="file.url"
                        rel="noopener noreferrer"
                        target="_blank"
                      >
                        <BaseIcon icon="download" size="small" />
                        {{ file.name || t("Download audio") }}
                      </a>
                    </div>
                  </div>
                  <div
                    v-else
                    class="text-sm text-gray-600"
                  >
                    {{ t("No uploaded audio") }}
                  </div>
                </div>

                <div
                  v-if="question.pendingCorrection"
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("This answer is pending teacher correction.") }}
                </div>

                <div
                  v-if="question.answer.teacherComment"
                  class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
                >
                  <div class="mb-1 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Teacher comment") }}
                  </div>
                  {{ question.answer.teacherComment }}
                </div>

                <div
                  v-if="question.canCorrect && correctionForms[question.id]"
                  class="exercise-result-correction-form rounded-lg border border-gray-20 bg-white p-4"
                >
                  <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <BaseIcon icon="edit" size="small" />
                    {{ t("Teacher correction") }}
                  </div>
                  <div
                    v-if="canUseAiCorrection(question) && aiCorrectionForms[question.id]"
                    class="mb-3 rounded-lg border border-info/30 bg-support-1 p-3"
                  >
                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-support-4">
                      <BaseIcon icon="robot-outline" size="small" />
                      {{ t("AI suggestion") }}
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                      <label class="flex min-w-[220px] flex-col gap-1 text-sm font-semibold text-gray-700">
                        {{ t("AI provider") }}
                        <select
                          v-model="aiCorrectionForms[question.id].provider"
                          class="rounded border border-gray-30 bg-white px-3 py-2 text-sm font-normal text-gray-90"
                          :disabled="aiCorrectionForms[question.id].isLoadingProviders || aiCorrectionForms[question.id].isGenerating"
                          name="ai_provider"
                          @change="saveAiCorrectionProvider(question)"
                        >
                          <option value="">{{ t("Default") }}</option>
                          <option
                            v-for="provider in aiCorrectionForms[question.id].providers"
                            :key="provider.key"
                            :value="provider.key"
                          >
                            {{ provider.label }}
                          </option>
                        </select>
                      </label>

                      <BaseButton
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :is-loading="aiCorrectionForms[question.id].isGenerating"
                        :label="aiCorrectionForms[question.id].feedback ? t('Regenerate') : t('Generate')"
                        icon="lightning-bolt"
                        type="secondary"
                        @click="generateAiCorrection(question)"
                      />

                      <BaseButton
                        v-if="aiCorrectionForms[question.id].feedback"
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :label="t('Apply suggestion')"
                        icon="arrow-down"
                        type="primary"
                        @click="applyAiCorrectionSuggestion(question)"
                      />
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].providerHint"
                      class="mt-2 text-xs text-gray-600"
                    >
                      {{ aiCorrectionForms[question.id].providerHint }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].error"
                      class="mt-3 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
                    >
                      {{ aiCorrectionForms[question.id].error }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].feedback"
                      class="mt-3 space-y-2"
                    >
                      <div
                        v-if="aiCorrectionForms[question.id].score !== null"
                        class="inline-flex items-center gap-2 rounded-full border border-info/30 bg-white px-3 py-1 text-xs font-semibold text-info"
                      >
                        {{ t("Suggested score") }}: {{ aiCorrectionForms[question.id].score }} / {{ formatNumber(question.maxScore || 0) }}
                      </div>
                      <BaseTextArea
                        v-model="aiCorrectionForms[question.id].feedback"
                        :id="aiCorrectionFeedbackInputId(question)"
                        :label="t('Suggested feedback')"
                        name="ai_feedback"
                        rows="4"
                      />
                    </div>
                  </div>
                  <div class="grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-start">
                    <BaseInputNumber
                      v-model="correctionForms[question.id].marks"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionScoreInputId(question)"
                      :label="t('Correction score')"
                      :max="Number(question.maxScore || 0)"
                      :min="0"
                      :step="0.5"
                      name="correction_score"
                    />
                    <BaseTextArea
                      v-model="correctionForms[question.id].teacherComment"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionCommentInputId(question)"
                      :label="t('Teacher comment')"
                      name="teacher_comment"
                      rows="3"
                    />
                    <BaseButton
                      :disabled="correctionSavingQuestionId === question.id"
                      :is-loading="correctionSavingQuestionId === question.id"
                      :label="t('Save correction')"
                      icon="content-save"
                      type="success"
                      @click="saveManualCorrection(question)"
                    />
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'upload_answer'">
              <div class="space-y-3">
                <div
                  v-if="visibility.showStudentAnswers !== false"
                  class="rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner file") }}
                  </div>
                  <div
                    v-if="question.answer.files?.length"
                    class="space-y-2"
                  >
                    <a
                      v-for="file in question.answer.files"
                      :key="file.id || file.name"
                      class="inline-flex items-center gap-2 rounded border border-primary/30 bg-white px-3 py-2 text-sm font-semibold text-primary hover:bg-support-1"
                      :href="file.url"
                      rel="noopener noreferrer"
                      target="_blank"
                    >
                      <BaseIcon icon="download" size="small" />
                      {{ file.name || t("Download file") }}
                    </a>
                  </div>
                  <div
                    v-else
                    class="text-sm text-gray-600"
                  >
                    {{ t("No uploaded file") }}
                  </div>
                </div>

                <div
                  v-if="question.pendingCorrection"
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("This answer is pending teacher correction.") }}
                </div>

                <div
                  v-if="question.answer.teacherComment"
                  class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
                >
                  <div class="mb-1 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Teacher comment") }}
                  </div>
                  {{ question.answer.teacherComment }}
                </div>

                <div
                  v-if="question.canCorrect && correctionForms[question.id]"
                  class="exercise-result-correction-form rounded-lg border border-gray-20 bg-white p-4"
                >
                  <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <BaseIcon icon="edit" size="small" />
                    {{ t("Teacher correction") }}
                  </div>
                  <div
                    v-if="canUseAiCorrection(question) && aiCorrectionForms[question.id]"
                    class="mb-3 rounded-lg border border-info/30 bg-support-1 p-3"
                  >
                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-support-4">
                      <BaseIcon icon="robot-outline" size="small" />
                      {{ t("AI suggestion") }}
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                      <label class="flex min-w-[220px] flex-col gap-1 text-sm font-semibold text-gray-700">
                        {{ t("AI provider") }}
                        <select
                          v-model="aiCorrectionForms[question.id].provider"
                          class="rounded border border-gray-30 bg-white px-3 py-2 text-sm font-normal text-gray-90"
                          :disabled="aiCorrectionForms[question.id].isLoadingProviders || aiCorrectionForms[question.id].isGenerating"
                          name="ai_provider"
                          @change="saveAiCorrectionProvider(question)"
                        >
                          <option value="">{{ t("Default") }}</option>
                          <option
                            v-for="provider in aiCorrectionForms[question.id].providers"
                            :key="provider.key"
                            :value="provider.key"
                          >
                            {{ provider.label }}
                          </option>
                        </select>
                      </label>

                      <BaseButton
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :is-loading="aiCorrectionForms[question.id].isGenerating"
                        :label="aiCorrectionForms[question.id].feedback ? t('Regenerate') : t('Generate')"
                        icon="lightning-bolt"
                        type="secondary"
                        @click="generateAiCorrection(question)"
                      />

                      <BaseButton
                        v-if="aiCorrectionForms[question.id].feedback"
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :label="t('Apply suggestion')"
                        icon="arrow-down"
                        type="primary"
                        @click="applyAiCorrectionSuggestion(question)"
                      />
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].providerHint"
                      class="mt-2 text-xs text-gray-600"
                    >
                      {{ aiCorrectionForms[question.id].providerHint }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].error"
                      class="mt-3 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
                    >
                      {{ aiCorrectionForms[question.id].error }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].feedback"
                      class="mt-3 space-y-2"
                    >
                      <div
                        v-if="aiCorrectionForms[question.id].score !== null"
                        class="inline-flex items-center gap-2 rounded-full border border-info/30 bg-white px-3 py-1 text-xs font-semibold text-info"
                      >
                        {{ t("Suggested score") }}: {{ aiCorrectionForms[question.id].score }} / {{ formatNumber(question.maxScore || 0) }}
                      </div>
                      <BaseTextArea
                        v-model="aiCorrectionForms[question.id].feedback"
                        :id="aiCorrectionFeedbackInputId(question)"
                        :label="t('Suggested feedback')"
                        name="ai_feedback"
                        rows="4"
                      />
                    </div>
                  </div>
                  <div class="grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-start">
                    <BaseInputNumber
                      v-model="correctionForms[question.id].marks"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionScoreInputId(question)"
                      :label="t('Correction score')"
                      :max="Number(question.maxScore || 0)"
                      :min="0"
                      :step="0.5"
                      name="correction_score"
                    />
                    <BaseTextArea
                      v-model="correctionForms[question.id].teacherComment"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionCommentInputId(question)"
                      :label="t('Teacher comment')"
                      name="teacher_comment"
                      rows="3"
                    />
                    <BaseButton
                      :disabled="correctionSavingQuestionId === question.id"
                      :is-loading="correctionSavingQuestionId === question.id"
                      :label="t('Save correction')"
                      icon="content-save"
                      type="success"
                      @click="saveManualCorrection(question)"
                    />
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'content'">
              <div
                v-if="question.answer.description"
                class="exercise-result-html rounded-lg border border-gray-20 bg-gray-10 p-4 text-gray-800"
                v-html="question.answer.description"
              />
              <div
                v-else
                class="rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
              >
                {{ t("Content item") }}
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'free_answer'">
              <div class="space-y-3">
                <div
                  v-if="visibility.showStudentAnswers !== false"
                  class="rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner answer") }}
                  </div>
                  <div class="whitespace-pre-wrap text-sm text-gray-800">
                    {{ question.answer.studentAnswer || t("No answer") }}
                  </div>
                </div>

                <div
                  v-if="question.pendingCorrection"
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("This answer is pending teacher correction.") }}
                </div>

                <div
                  v-if="question.answer.teacherComment"
                  class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
                >
                  <div class="mb-1 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Teacher comment") }}
                  </div>
                  {{ question.answer.teacherComment }}
                </div>

                <div
                  v-if="question.canCorrect && correctionForms[question.id]"
                  class="exercise-result-correction-form rounded-lg border border-gray-20 bg-white p-4"
                >
                  <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <BaseIcon icon="edit" size="small" />
                    {{ t("Teacher correction") }}
                  </div>
                  <div
                    v-if="canUseAiCorrection(question) && aiCorrectionForms[question.id]"
                    class="mb-3 rounded-lg border border-info/30 bg-support-1 p-3"
                  >
                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-support-4">
                      <BaseIcon icon="robot-outline" size="small" />
                      {{ t("AI suggestion") }}
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                      <label class="flex min-w-[220px] flex-col gap-1 text-sm font-semibold text-gray-700">
                        {{ t("AI provider") }}
                        <select
                          v-model="aiCorrectionForms[question.id].provider"
                          class="rounded border border-gray-30 bg-white px-3 py-2 text-sm font-normal text-gray-90"
                          :disabled="aiCorrectionForms[question.id].isLoadingProviders || aiCorrectionForms[question.id].isGenerating"
                          name="ai_provider"
                          @change="saveAiCorrectionProvider(question)"
                        >
                          <option value="">{{ t("Default") }}</option>
                          <option
                            v-for="provider in aiCorrectionForms[question.id].providers"
                            :key="provider.key"
                            :value="provider.key"
                          >
                            {{ provider.label }}
                          </option>
                        </select>
                      </label>

                      <BaseButton
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :is-loading="aiCorrectionForms[question.id].isGenerating"
                        :label="aiCorrectionForms[question.id].feedback ? t('Regenerate') : t('Generate')"
                        icon="lightning-bolt"
                        type="secondary"
                        @click="generateAiCorrection(question)"
                      />

                      <BaseButton
                        v-if="aiCorrectionForms[question.id].feedback"
                        :disabled="aiCorrectionForms[question.id].isGenerating"
                        :label="t('Apply suggestion')"
                        icon="arrow-down"
                        type="primary"
                        @click="applyAiCorrectionSuggestion(question)"
                      />
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].providerHint"
                      class="mt-2 text-xs text-gray-600"
                    >
                      {{ aiCorrectionForms[question.id].providerHint }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].error"
                      class="mt-3 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
                    >
                      {{ aiCorrectionForms[question.id].error }}
                    </div>

                    <div
                      v-if="aiCorrectionForms[question.id].feedback"
                      class="mt-3 space-y-2"
                    >
                      <div
                        v-if="aiCorrectionForms[question.id].score !== null"
                        class="inline-flex items-center gap-2 rounded-full border border-info/30 bg-white px-3 py-1 text-xs font-semibold text-info"
                      >
                        {{ t("Suggested score") }}: {{ aiCorrectionForms[question.id].score }} / {{ formatNumber(question.maxScore || 0) }}
                      </div>
                      <BaseTextArea
                        v-model="aiCorrectionForms[question.id].feedback"
                        :id="aiCorrectionFeedbackInputId(question)"
                        :label="t('Suggested feedback')"
                        name="ai_feedback"
                        rows="4"
                      />
                    </div>
                  </div>
                  <div class="grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-start">
                    <BaseInputNumber
                      v-model="correctionForms[question.id].marks"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionScoreInputId(question)"
                      :label="t('Correction score')"
                      :max="Number(question.maxScore || 0)"
                      :min="0"
                      :step="0.5"
                      name="correction_score"
                    />
                    <BaseTextArea
                      v-model="correctionForms[question.id].teacherComment"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionCommentInputId(question)"
                      :label="t('Teacher comment')"
                      name="teacher_comment"
                      rows="3"
                    />
                    <BaseButton
                      :disabled="correctionSavingQuestionId === question.id"
                      :is-loading="correctionSavingQuestionId === question.id"
                      :label="t('Save correction')"
                      icon="content-save"
                      type="success"
                      @click="saveManualCorrection(question)"
                    />
                  </div>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning">
                {{ t("This question type cannot be reviewed on this page.") }}
              </div>
            </template>

            <div
              v-if="question.feedback"
              class="exercise-result-html rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
              v-html="question.feedback"
            />
          </div>
        </article>
      </div>

      <section
        v-if="showStandaloneFinalActions"
        class="exercise-result-final-actions rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-base font-semibold text-gray-90">{{ t("Exercise completed") }}</h2>
            <p
              v-if="finalActions.maxAttempt > 0"
              class="mt-1 text-sm text-gray-600"
            >
              {{ t("Attempts") }}: {{ finalActions.attemptCount || 0 }} / {{ finalActions.maxAttempt }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="finalActions.canTryAgain"
              :disabled="isStartingAttempt"
              :is-loading="isStartingAttempt"
              :label="t('Try again')"
              icon="replay"
              type="success"
              @click="startAnotherAttempt"
            />
            <BaseButton
              :label="t('Return to Course Homepage')"
              :route="{
                name: 'CourseHome',
                params: { id: getCourseId() },
                query: getCourseHomeQuery(),
              }"
              icon="home"
              type="primary"
            />
          </div>
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const title = ref("")
const description = ref("")
const attempt = ref({})
const visibility = ref({})
const questions = ref([])
const ranking = ref([])
const finalActions = ref({})
const aiCorrection = ref({})
const isStartingAttempt = ref(false)
const correctionForms = ref({})
const aiCorrectionForms = ref({})
const correctionSavingQuestionId = ref(null)
const correctionError = ref("")
const correctionMessage = ref("")
const resultHotspotImageSizes = ref({})
const autoPrintDone = ref(false)
const AI_CORRECTION_PROVIDER_STORAGE_KEY = "chamilo.ai.open_answer.provider"

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    origin: getQueryValue(route.query.origin),
    lp_init: getQueryValue(route.query.lp_init),
    learnpath_id: getQueryValue(route.query.learnpath_id),
    learnpath_item_id: getQueryValue(route.query.learnpath_item_id),
    learnpath_item_view_id: getQueryValue(route.query.learnpath_item_view_id),
    isStudentView: getQueryValue(route.query.isStudentView),
    review: getQueryValue(route.query.review),
    mode: getQueryValue(route.query.mode),
  }
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

function getBaseRouteParams() {
  return {
    node: route.params.node,
  }
}

function getPlayerRouteParams() {
  return {
    ...getBaseRouteParams(),
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

function getAttemptId() {
  return Number(route.params.attemptId || 0)
}

function getCourseId() {
  return Number(getQueryValue(route.query.cid) || 0)
}

function getCourseHomeQuery() {
  const query = {}
  const sid = getQueryValue(route.query.sid)

  if (
    sid !== undefined
    && sid !== null
    && String(sid) !== ""
    && Number(sid) > 0
  ) {
    query.sid = sid
  }

  return query
}

const showStandaloneFinalActions = computed(() => {
  return finalActions.value?.showFinalActions === true
    && visibility.value?.isReviewMode !== true
    && !isLearnpathContext.value
    && getCourseId() > 0
})

function buildPlayerQuery(startResponse = null) {
  const query = { ...getContextParams() }
  const attemptId = Number(startResponse?.attemptId || 0)

  if (attemptId > 0) {
    query.attemptId = attemptId
  }

  return query
}

function openLegacyRuntime(startResponse = null) {
  const overviewUrl = startResponse?.legacyUrls?.overview || startResponse?.legacyUrls?.exercise || ""
  if (overviewUrl && typeof window !== "undefined") {
    window.location.href = overviewUrl

    return true
  }

  return false
}

async function startAnotherAttempt() {
  const exerciseId = getExerciseId()
  if (exerciseId <= 0 || !finalActions.value?.canTryAgain) {
    return
  }

  isStartingAttempt.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.startExerciseAttempt({ exerciseId }, getContextParams(), exerciseId)

    if (response?.success) {
      await router.push({
        name: "ExercisePlayer",
        params: getPlayerRouteParams(),
        query: buildPlayerQuery(response),
      })

      return
    }

    if (true === response?.usesLegacyRuntime && openLegacyRuntime(response)) {
      return
    }

    errorMessage.value = response?.message ? t(response.message) : t("Could not start the attempt")
  } catch (error) {
    console.error("Error starting another exercise attempt from result", error)
    errorMessage.value = t("Could not start the attempt")
  } finally {
    isStartingAttempt.value = false
  }
}

async function loadResult() {
  const exerciseId = getExerciseId()
  const attemptId = getAttemptId()
  if (!exerciseId || !attemptId) {
    errorMessage.value = t("Invalid exercise attempt")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntimeResult(getContextParams(), exerciseId, attemptId)
    title.value = response.title || ""
    description.value = response.description || ""
    attempt.value = response.attempt || {}
    visibility.value = response.visibility || {}
    questions.value = Array.isArray(response.questions) ? response.questions : []
    ranking.value = Array.isArray(response.ranking) ? response.ranking : []
    finalActions.value = response.finalActions || {}
    aiCorrection.value = response.aiCorrection || {}
    initializeCorrectionForms()
    initializeAiCorrectionForms()
    scheduleAutoPrintIfRequested()
  } catch (error) {
    console.error("Error loading exercise result", error)
    errorMessage.value = t("Could not load exercise result")
  } finally {
    isLoading.value = false
  }
}

function onResultHotspotImageLoad(question, event) {
  const image = event?.target
  if (!image || !question?.id) {
    return
  }

  resultHotspotImageSizes.value = {
    ...resultHotspotImageSizes.value,
    [question.id]: {
      width: Number(image.naturalWidth || image.width || 0),
      height: Number(image.naturalHeight || image.height || 0),
    },
  }
}

function resultHotspotPointStyle(question, point) {
  const size = resultHotspotImageSizes.value[question.id] || {}
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

function resultAnnotationImageReady(question) {
  const size = resultHotspotImageSizes.value[question.id] || {}

  return Number(size.width || 0) > 0 && Number(size.height || 0) > 0
}

function resultAnnotationViewBox(question) {
  const size = resultHotspotImageSizes.value[question.id] || {}

  return `0 0 ${Number(size.width || 1)} ${Number(size.height || 1)}`
}

function resultAnnotationPolylinePoints(path) {
  const points = Array.isArray(path?.points) ? path.points : []

  return points.map((point) => `${Number(point.x || 0)},${Number(point.y || 0)}`).join(" ")
}

function shouldShowParentMedia(question, index) {
  const parentId = Number(question?.parent?.id || question?.parentId || 0)
  if (parentId <= 0) {
    return false
  }

  const previousQuestion = questions.value[index - 1] || null

  return Number(previousQuestion?.parent?.id || previousQuestion?.parentId || 0) !== parentId
}

function questionLabel(position) {
  const safePosition = Number(position || 0)

  return safePosition > 0 ? `${t("Question")} ${safePosition}` : t("Question")
}

function isManualCorrectionQuestion(question) {
  return ["free_answer", "oral_expression", "upload_answer"].includes(question?.answer?.kind)
}

function hasPartialManualScore(question) {
  if (!isManualCorrectionQuestion(question) || question?.pendingCorrection) {
    return false
  }

  const score = Number(question?.score ?? 0)
  const maxScore = Number(question?.maxScore ?? 0)

  return maxScore > 0 && score > 0 && score < maxScore
}

function questionCardClass(question) {
  if (question.pendingCorrection) {
    return "border-warning/40"
  }

  if (question.isCorrect === true) {
    return "border-success/40"
  }

  if (hasPartialManualScore(question)) {
    return "border-warning/40"
  }

  if (question.isCorrect === false) {
    return "border-danger/40"
  }

  return "border-gray-20"
}

function questionStatusIconClass(question) {
  if (question.pendingCorrection) {
    return "mdi-help-circle-outline text-warning"
  }

  if (question.isCorrect === true) {
    return "mdi-check-circle text-success"
  }

  if (hasPartialManualScore(question)) {
    return "mdi-progress-check text-warning"
  }

  if (question.isCorrect === false) {
    return "mdi-alert-circle text-danger"
  }

  return "mdi-help-circle-outline text-primary"
}

function questionScoreClass(question) {
  if (question.pendingCorrection) {
    return "border-warning/30 bg-warning/10 text-warning"
  }

  if (question.isCorrect === true) {
    return "border-success/30 bg-success/10 text-success"
  }

  if (hasPartialManualScore(question)) {
    return "border-warning/30 bg-warning/10 text-warning"
  }

  if (question.isCorrect === false) {
    return "border-danger/30 bg-danger/10 text-danger"
  }

  return "border-gray-20 bg-white text-gray-700"
}

function questionResultBadgeLabel(question) {
  if (question.pendingCorrection) {
    return ""
  }

  if (question.isCorrect === true) {
    return t("Correct")
  }

  if (hasPartialManualScore(question)) {
    return t("Partially correct")
  }

  if (question.isCorrect === false) {
    return t("Incorrect")
  }

  return ""
}

function questionResultBadgeClass(question) {
  if (question.isCorrect === true) {
    return "bg-success/10 text-success"
  }

  if (hasPartialManualScore(question)) {
    return "bg-warning/10 text-warning"
  }

  if (question.isCorrect === false) {
    return "bg-danger/10 text-danger"
  }

  return "bg-gray-20 text-gray-700"
}

function choiceClass(choice) {
  if (choice.correct) {
    return "border-success/40 bg-success/10"
  }

  if (choice.selected) {
    return "border-info/40 bg-support-1"
  }

  return "border-gray-20 bg-white"
}

function choiceIconClass(choice) {
  if (choice.correct) {
    return "mdi-check-circle text-success"
  }

  if (choice.selected) {
    return "mdi-radiobox-marked text-info"
  }

  return "mdi-circle-outline text-gray-50"
}

function trueFalseClass(choice) {
  if (isTrueFalseChoiceCorrect(choice)) {
    return "border-success/40 bg-success/10"
  }

  if (visibility.value.showStudentAnswers === false && choice.correctOptionLabel) {
    return "border-success/40 bg-success/10"
  }

  if (choice.selectedOptionLabel) {
    return "border-danger/40 bg-danger/10"
  }

  return "border-gray-20 bg-white"
}

function trueFalseIconClass(choice) {
  if (isTrueFalseChoiceCorrect(choice)) {
    return "mdi-check-circle text-success"
  }

  if (visibility.value.showStudentAnswers === false && choice.correctOptionLabel) {
    return "mdi-check-circle text-success"
  }

  if (choice.selectedOptionLabel) {
    return "mdi-alert-circle text-danger"
  }

  return "mdi-help-circle-outline text-gray-50"
}

function isTrueFalseChoiceCorrect(choice) {
  return Boolean(choice.correctOptionLabel && choice.selectedOptionLabel === choice.correctOptionLabel)
}

function blankClass(blank) {
  if (visibility.value.showStudentAnswers === false && blank.correctAnswer) {
    return "border-success/30"
  }

  if (isBlankCorrect(blank)) {
    return "border-success/30"
  }

  return "border-danger/30"
}

function blankIconClass(blank) {
  if (visibility.value.showStudentAnswers === false && blank.correctAnswer) {
    return "mdi-check-circle text-success"
  }

  return isBlankCorrect(blank) ? "mdi-check-circle text-success" : "mdi-alert-circle text-danger"
}

function isBlankCorrect(blank) {
  return String(blank.studentScore || "") === "1"
}

function matchingClass(prompt) {
  if (isMatchingCorrect(prompt)) {
    return "border-success/40 bg-success/10"
  }

  if (visibility.value.showStudentAnswers === false && prompt.correctOptionAnswer) {
    return "border-success/40 bg-success/10"
  }

  if (prompt.selectedOptionAnswer) {
    return "border-danger/40 bg-danger/10"
  }

  return "border-gray-20 bg-white"
}

function matchingIconClass(prompt) {
  if (isMatchingCorrect(prompt)) {
    return "mdi-check-circle text-success"
  }

  if (visibility.value.showStudentAnswers === false && prompt.correctOptionAnswer) {
    return "mdi-check-circle text-success"
  }

  if (prompt.selectedOptionAnswer) {
    return "mdi-alert-circle text-danger"
  }

  return "mdi-help-circle-outline text-gray-50"
}

function isMatchingCorrect(prompt) {
  return Boolean(prompt.correctOptionAnswer && prompt.selectedOptionAnswer === prompt.correctOptionAnswer)
}


function initializeCorrectionForms() {
  const forms = {}
  for (const question of questions.value) {
    if (!question.canCorrect) {
      continue
    }

    forms[question.id] = {
      marks: Number(question.score ?? question.answer?.marks ?? 0),
      teacherComment: question.answer?.teacherComment || "",
    }
  }

  correctionForms.value = forms
}

function initializeAiCorrectionForms() {
  const forms = {}
  const savedProvider = getSavedAiCorrectionProvider()

  for (const question of questions.value) {
    if (!question.canCorrect) {
      continue
    }

    forms[question.id] = {
      provider: savedProvider,
      providers: [],
      providersLoaded: false,
      isLoadingProviders: false,
      isGenerating: false,
      score: null,
      feedback: "",
      error: "",
      providerHint: "",
    }
  }

  aiCorrectionForms.value = forms
}

function canUseAiCorrection(question) {
  return Boolean(aiCorrection.value?.enabled && question?.canCorrect && aiCorrectionForms.value[question.id])
}

function aiCorrectionFeedbackInputId(question) {
  return `ai_feedback_${Number(question.id || 0)}`
}

function getSavedAiCorrectionProvider() {
  if (typeof window === "undefined") {
    return ""
  }

  try {
    return window.localStorage.getItem(AI_CORRECTION_PROVIDER_STORAGE_KEY) || ""
  } catch (error) {
    return ""
  }
}

function saveAiCorrectionProvider(question) {
  const state = aiCorrectionForms.value[question.id]
  if (!state || typeof window === "undefined") {
    return
  }

  try {
    window.localStorage.setItem(AI_CORRECTION_PROVIDER_STORAGE_KEY, state.provider || "")
  } catch (error) {
    // Ignore local storage failures.
  }
}

async function loadAiCorrectionProviders(question) {
  const state = aiCorrectionForms.value[question.id]
  if (!state || state.providersLoaded || state.isLoadingProviders) {
    return
  }

  state.isLoadingProviders = true
  state.error = ""

  try {
    const response = await fetch("/ai/text_providers", {
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
      method: "GET",
    })
    const data = await response.json().catch(() => ({}))

    if (!response.ok) {
      throw new Error(data?.error || data?.message || "Could not load AI providers")
    }

    state.providers = Array.isArray(data.providers)
      ? data.providers.filter((provider) => provider?.key).map((provider) => ({
          key: String(provider.key),
          label: String(provider.label || provider.key),
        }))
      : []
    state.providersLoaded = true
  } catch (error) {
    console.error("Error loading AI correction providers", error)
    state.providers = []
    state.providersLoaded = true
    state.providerHint = t("Could not load AI providers")
  } finally {
    state.isLoadingProviders = false
  }
}

async function generateAiCorrection(question) {
  const state = aiCorrectionForms.value[question.id]
  const questionId = Number(question?.id || 0)
  const attemptId = getAttemptId()
  const courseId = getCourseId()

  if (!state || !canUseAiCorrection(question) || questionId <= 0 || attemptId <= 0 || courseId <= 0) {
    return
  }

  await loadAiCorrectionProviders(question)

  state.isGenerating = true
  state.error = ""
  state.providerHint = ""

  const payload = new URLSearchParams({
    exeId: String(attemptId),
    questionId: String(questionId),
    courseId: String(courseId),
  })

  if (state.provider) {
    payload.set("ai_provider", state.provider)
  }

  try {
    const response = await fetch("/ai/open_answer_grade", {
      body: payload,
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/x-www-form-urlencoded",
      },
      method: "POST",
    })
    const text = await response.text()
    let data = {}

    try {
      data = JSON.parse(text)
    } catch (error) {
      data = {}
    }

    if (!response.ok) {
      throw new Error(data?.error || data?.text || data?.message || text || "Could not generate AI suggestion")
    }

    state.score = normalizeAiCorrectionScore(data?.score, question)
    state.feedback = String(data?.feedback || "")
    state.providerHint = data?.provider_used ? `${t("Provider used")}: ${data.provider_used}` : ""
  } catch (error) {
    console.error("Error generating AI correction suggestion", error)
    state.error = error?.message || t("Could not generate AI suggestion")
  } finally {
    state.isGenerating = false
  }
}

function normalizeAiCorrectionScore(value, question) {
  const score = Number(value)
  if (!Number.isFinite(score)) {
    return null
  }

  const maxScore = Number(question?.maxScore || 0)
  if (maxScore <= 0) {
    return Math.max(0, score)
  }

  return Math.min(Math.max(0, score), maxScore)
}

function applyAiCorrectionSuggestion(question) {
  const correctionForm = correctionForms.value[question.id]
  const aiForm = aiCorrectionForms.value[question.id]
  if (!correctionForm || !aiForm) {
    return
  }

  if (aiForm.score !== null) {
    correctionForm.marks = aiForm.score
  }

  if (aiForm.feedback) {
    correctionForm.teacherComment = aiForm.feedback
  }
}

function correctionScoreInputId(question) {
  return `correction_score_${Number(question.id || 0)}`
}

function correctionCommentInputId(question) {
  return `teacher_comment_${Number(question.id || 0)}`
}

async function saveManualCorrection(question) {
  const form = correctionForms.value[question.id]
  const exerciseId = getExerciseId()
  const attemptId = getAttemptId()
  const questionId = Number(question.id || 0)
  if (!form || !exerciseId || !attemptId || !questionId) {
    return
  }

  correctionSavingQuestionId.value = questionId
  correctionError.value = ""
  correctionMessage.value = ""

  try {
    const response = await exerciseService.saveExerciseRuntimeCorrection(
      {
        exerciseId,
        attemptId,
        questionId,
        marks: Number(form.marks || 0),
        teacherComment: form.teacherComment || "",
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

    if (!response?.success) {
      throw new Error(response?.message || "Could not save correction")
    }

    correctionMessage.value = t("Correction saved")
    await loadResult()
  } catch (error) {
    console.error("Error saving exercise correction", error)
    correctionError.value = t("Could not save correction")
  } finally {
    correctionSavingQuestionId.value = null
  }
}

function shouldAutoPrint() {
  return ['1', 'true', 'pdf', 'print'].includes(String(getQueryValue(route.query.print) || '').toLowerCase())
}

function scheduleAutoPrintIfRequested() {
  if (!shouldAutoPrint() || autoPrintDone.value) {
    return
  }

  autoPrintDone.value = true
  nextTick(() => {
    window.setTimeout(() => printResult(), 350)
  })
}

function downloadResultPdf() {
  const exerciseId = getExerciseId()
  const attemptId = getAttemptId()
  if (!exerciseId || !attemptId || typeof window === "undefined") {
    return
  }

  window.open(exerciseService.buildExerciseRuntimeAttemptPdfUrl(getContextParams(), exerciseId, attemptId), "_blank", "noopener")
}

function printResult() {
  if (typeof window === 'undefined') {
    return
  }

  window.print()
}

function formatNumber(value) {
  if (value === null || value === undefined || value === "") {
    return "—"
  }

  const number = Number(value)
  if (Number.isNaN(number)) {
    return "—"
  }

  return number.toFixed(2).replace(/\.00$/, "")
}

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const hours = Math.floor(safeSeconds / 3600)
  const minutes = Math.floor((safeSeconds % 3600) / 60)
  const remainingSeconds = safeSeconds % 60

  if (hours > 0) {
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
  }

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ""
  }

  return date.toLocaleString()
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

onMounted(loadResult)

watch(
  () => [route.params.exerciseId, route.params.attemptId, route.query.cid, route.query.sid, route.query.gid, route.query.print],
  () => {
    autoPrintDone.value = false
    loadResult()
  },
)
</script>

<style scoped>
:deep(.exercise-result-toolbar .p-button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-result-toolbar .p-button-icon) {
  font-size: 1.25rem;
}

@media print {
  .exercise-result-toolbar,
  .exercise-result-correction-form,
  .exercise-result-final-actions {
    display: none !important;
  }

  article,
  header {
    break-inside: avoid;
    page-break-inside: avoid;
  }
}

.exercise-result-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-result-html :deep(.tiny-content) {
  display: block;
}

.exercise-result-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-result-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>

<style>
@media print {
  body:has(.exercise-result-print-mode) * {
    visibility: hidden !important;
  }

  body:has(.exercise-result-print-mode) .exercise-result-print-mode,
  body:has(.exercise-result-print-mode) .exercise-result-print-mode * {
    visibility: visible !important;
  }

  body:has(.exercise-result-print-mode) .exercise-result-print-mode {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
  }

  body:has(.exercise-result-print-mode) .exercise-result-toolbar,
  body:has(.exercise-result-print-mode) .exercise-result-correction-form,
  body:has(.exercise-result-print-mode) .exercise-result-final-actions {
    display: none !important;
  }
}
</style>
