<template>
  <section class="space-y-5">
    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back to the questions')"
        :route="questionsRoute"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

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

    <form
      v-if="!isLoading"
      :class="[
        'space-y-5',
        isReadOnlyFromLearningPath ? 'pointer-events-none opacity-75' : '',
      ]"
      @submit.prevent="saveQuestion"
    >
      <div class="space-y-3 border-b border-gray-20 pb-4">
        <h1 class="text-2xl font-semibold text-gray-90">
          {{ editorTitle }}
        </h1>

        <div
          v-if="summaryText"
          class="rounded-lg border border-info/30 bg-support-1 px-4 py-3 text-sm font-medium text-support-4"
        >
          {{ summaryText }}
        </div>

        <div
          v-if="isReadOnlyFromLearningPath"
          class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-medium text-yellow-800"
        >
          {{ t(learningPathReadOnlyMessage) }}
        </div>

        <BaseInputText
          id="exercise-question-title"
          v-model="form.title"
          :label="t('Question') + ' *'"
          name="question"
        />
      </div>

      <BaseAdvancedSettingsButton
        v-if="!isStructuralQuestion"
        v-model="showAdvancedSettings"
      >
        <div class="space-y-4">
          <section class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-70">
              {{ t("Question settings") }}
            </h2>

            <BaseTinyEditor
              editor-id="exercise-question-description"
              v-model="form.description"
              :editor-config="advancedEditorConfig"
              :full-page="false"
              :title="t('Enrich question')"
            />

            <div class="grid gap-4 md:grid-cols-3">
              <BaseSelect
                id="exercise-question-difficulty"
                v-model="form.difficulty"
                :label="t('Difficulty')"
                name="difficulty"
                :options="difficultyOptions"
              />

              <BaseSelect
                id="exercise-question-category"
                v-model="form.categoryId"
                :label="t('Category')"
                name="category_id"
                :options="categorySelectOptions"
              />

              <BaseSelect
                id="exercise-question-media"
                v-model="form.parentMediaId"
                :label="t('Attach to media')"
                name="parent_media_id"
                :options="mediaSelectOptions"
              />
            </div>
          </section>

          <section class="grid gap-4 md:grid-cols-3">
            <BaseInputNumber
              id="exercise-question-duration"
              v-model="form.duration"
              :help-text="t('Leave empty or 0 for no time limit.')"
              :label="t('Time limit in seconds')"
              name="duration"
              :min="0"
            />

            <div
              v-if="canMarkMandatoryQuestion"
              class="flex items-end pb-2"
            >
              <BaseCheckbox
                id="exercise-question-mandatory"
                v-model="form.mandatory"
                :label="t('Mandatory')"
                name="mandatory"
              />
            </div>
          </section>

          <section
            v-if="allowQuestionFeedback"
            class="space-y-3"
          >
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-70">
              {{ t("Feedback") }}
            </h2>
            <BaseTinyEditor
              editor-id="exercise-question-feedback"
              v-model="form.feedback"
              :editor-config="advancedEditorConfig"
              :full-page="false"
              :title="t('Feedback')"
            />
          </section>
        </div>
      </BaseAdvancedSettingsButton>

      <section
        v-if="isStructuralQuestion"
        class="space-y-4 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ structuralQuestionTitle }}</h2>
          <p class="text-sm text-gray-70">
            {{ structuralQuestionHelp }}
          </p>
        </div>

        <div
          v-if="isReadingComprehensionQuestion"
          class="grid gap-4 md:grid-cols-2"
        >
          <BaseSelect
            id="exercise-reading-speed"
            v-model="form.difficulty"
            :label="t('Reading speed')"
            name="reading_speed"
            :options="readingSpeedOptions"
          />

          <BaseSelect
            id="exercise-reading-category"
            v-model="form.categoryId"
            :label="t('Category')"
            name="category_id"
            :options="categorySelectOptions"
          />
        </div>

        <BaseTinyEditor
          editor-id="exercise-structural-question-description"
          v-model="form.description"
          :editor-config="isReadingComprehensionQuestion ? readingComprehensionEditorConfig : structuralEditorConfig"
          :full-page="false"
          :title="structuralEditorTitle"
        />
      </section>

      <section
        v-if="isOpenQuestion || isOralExpressionQuestion || isUploadAnswerQuestion"
        class="grid gap-4 rounded-lg border border-gray-20 bg-white p-4 md:grid-cols-3"
      >
        <BaseInputNumber
          id="exercise-manual-question-score"
          v-model="form.score"
          :label="t('Score')"
          name="score"
          :min="0"
          :step="0.1"
        />
        <p class="self-end pb-2 text-sm text-gray-70 md:col-span-2">
          {{ manualCorrectionHelpText }}
        </p>
      </section>

      <section
        v-if="isHotspotQuestion"
        class="space-y-4 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ hotspotEditorTitle }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Upload image (jpg, png or gif) to apply hotspots.") }}
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <div
            v-if="!isHotspotCombinationQuestion"
            class="rounded-lg border border-info/30 bg-support-1 px-4 py-3 text-sm text-support-4 md:col-span-3"
          >
            {{ t("Total score is calculated as the sum of hotspot scores.") }}
            <span class="font-semibold">{{ formatScore(hotspotTotalScore) }}</span>
          </div>
          <BaseInputNumber
            v-else
            id="exercise-hotspot-global-score"
            v-model="form.globalScore"
            :label="t('Global score')"
            name="global_score"
            :min="0"
            :step="0.1"
          />
        </div>

        <BaseFileUpload
          v-if="!isEditMode"
          accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
          :label="t('Image')"
          size="small"
          @file-selected="selectHotspotImage"
        />

        <div
          v-if="hotspotPreviewUrl"
          class="space-y-3"
        >
          <p
            v-if="form.hotspotImageName"
            class="text-sm font-semibold text-gray-80"
          >
            {{ form.hotspotImageName }}
          </p>

          <div
            ref="hotspotCanvasContainer"
            class="relative inline-block max-w-full overflow-auto rounded-lg border border-gray-20 bg-white p-2"
            @dblclick.prevent="finishHotspotPolygon"
            @mousedown="startHotspotDraw"
            @mousemove="moveHotspotDraw"
            @mouseup="finishHotspotDraw"
            @mouseleave="cancelHotspotDraw"
          >
            <img
              ref="hotspotImageRef"
              :alt="form.hotspotImageName || t('Image')"
              class="block max-h-[520px] max-w-full select-none object-contain"
              draggable="false"
              :src="hotspotPreviewUrl"
              @load="updateHotspotImageSize"
            />
            <svg
              v-if="hotspotImageSize.width && hotspotImageSize.height"
              class="pointer-events-none absolute left-2 top-2"
              :height="hotspotImageSize.height"
              :viewBox="`0 0 ${hotspotImageSize.width} ${hotspotImageSize.height}`"
              :width="hotspotImageSize.width"
            >
              <template
                v-for="(item, index) in form.hotspotItems"
                :key="`hotspot-shape-${item.localId}`"
              >
                <rect
                  v-if="getHotspotShape(item).kind === 'rect'"
                  :fill="getHotspotShapeFill(index)"
                  style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                  :height="getHotspotShape(item).height"
                  :stroke="getHotspotShapeStroke(index)"
                  :stroke-width="index === currentHotspotIndex ? 4 : 3"
                  vector-effect="non-scaling-stroke"
                  :width="getHotspotShape(item).width"
                  :x="getHotspotShape(item).x"
                  :y="getHotspotShape(item).y"
                />
                <ellipse
                  v-else-if="getHotspotShape(item).kind === 'ellipse'"
                  :cx="getHotspotShape(item).cx"
                  :cy="getHotspotShape(item).cy"
                  :fill="getHotspotShapeFill(index)"
                  style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                  :rx="getHotspotShape(item).rx"
                  :ry="getHotspotShape(item).ry"
                  :stroke="getHotspotShapeStroke(index)"
                  :stroke-width="index === currentHotspotIndex ? 4 : 3"
                  vector-effect="non-scaling-stroke"
                />
                <polygon
                  v-else-if="getHotspotShape(item).kind === 'polygon'"
                  :fill="getHotspotShapeFill(index)"
                  style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                  :points="getHotspotShape(item).points"
                  :stroke="getHotspotShapeStroke(index)"
                  stroke-linejoin="round"
                  :stroke-width="index === currentHotspotIndex ? 4 : 3"
                  vector-effect="non-scaling-stroke"
                />
              </template>
              <rect
                v-if="drawingDraft && drawingDraft.kind === 'rect'"
                fill="rgb(var(--color-success-base) / 0.58)"
                style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                :height="drawingDraft.height"
                stroke="rgb(var(--color-success-base))"
                stroke-dasharray="4 4"
                stroke-width="5"
                vector-effect="non-scaling-stroke"
                :width="drawingDraft.width"
                :x="drawingDraft.x"
                :y="drawingDraft.y"
              />
              <ellipse
                v-if="drawingDraft && drawingDraft.kind === 'ellipse'"
                :cx="drawingDraft.cx"
                :cy="drawingDraft.cy"
                fill="rgb(var(--color-success-base) / 0.58)"
                style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                :rx="drawingDraft.rx"
                :ry="drawingDraft.ry"
                stroke="rgb(var(--color-success-base))"
                stroke-dasharray="4 4"
                stroke-width="5"
                vector-effect="non-scaling-stroke"
              />
              <polygon
                v-if="hotspotPolygonPoints.length >= 3"
                fill="rgb(var(--color-success-base) / 0.58)"
                style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                :points="hotspotPolygonPoints.map((point) => `${toDisplayX(point.x)},${toDisplayY(point.y)}`).join(' ')"
                stroke="rgb(var(--color-success-base))"
                stroke-dasharray="4 4"
                stroke-linejoin="round"
                stroke-width="5"
                vector-effect="non-scaling-stroke"
              />
              <polyline
                v-else-if="hotspotPolygonPoints.length"
                fill="none"
                style="filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.95)) drop-shadow(0 1px 4px rgb(0 0 0 / 0.9))"
                :points="hotspotPolygonPoints.map((point) => `${toDisplayX(point.x)},${toDisplayY(point.y)}`).join(' ')"
                stroke="rgb(var(--color-success-base))"
                stroke-dasharray="4 4"
                stroke-width="5"
                vector-effect="non-scaling-stroke"
              />
              <circle
                v-for="(point, index) in hotspotPolygonPoints"
                :key="`hotspot-polygon-point-${index}`"
                :cx="toDisplayX(point.x)"
                :cy="toDisplayY(point.y)"
                fill="white"
                r="5"
                stroke="rgb(var(--color-success-base))"
                stroke-width="3"
                vector-effect="non-scaling-stroke"
              />
            </svg>
          </div>

          <p class="text-sm text-gray-70">
            {{ t("Select a hotspot row, choose its shape, then draw over the image. For polygons, click each point and finish the polygon, or double-click to finish.") }}
          </p>
        </div>

        <section
          v-if="showHotspotDelineationScenario"
          class="space-y-3 rounded-lg border border-info/30 bg-support-1 px-4 py-3"
        >
          <h3 class="text-sm font-semibold uppercase tracking-wide text-support-4">
            {{ t("Adaptive behavior (success/failure)") }}
          </h3>
          <div class="grid gap-4 md:grid-cols-2">
            <BaseSelect
              id="exercise-hotspot-success-destination"
              v-model="form.hotspotScenarioSuccessType"
              :label="t('On success')"
              name="hotspot_success_destination"
              :options="form.hotspotScenarioOptions"
            />
            <BaseInputText
              v-if="form.hotspotScenarioSuccessType === 'url'"
              id="exercise-hotspot-success-url"
              v-model="form.hotspotScenarioSuccessUrl"
              :label="t('Custom URL')"
              name="hotspot_success_url"
            />
            <BaseSelect
              id="exercise-hotspot-failure-destination"
              v-model="form.hotspotScenarioFailureType"
              :label="t('On failure')"
              name="hotspot_failure_destination"
              :options="form.hotspotScenarioOptions"
            />
            <BaseInputText
              v-if="form.hotspotScenarioFailureType === 'url'"
              id="exercise-hotspot-failure-url"
              v-model="form.hotspotScenarioFailureUrl"
              :label="t('Custom URL')"
              name="hotspot_failure_url"
            />
          </div>

          <div class="rounded-lg border border-info/20 bg-white p-3 text-sm text-gray-80">
            <div class="mb-2 font-semibold text-gray-90">
              {{ t("Dependency tree") }}
            </div>
            <div class="flex flex-col gap-2 md:flex-row md:items-center">
              <div class="rounded border border-gray-20 bg-gray-10 px-3 py-2 font-medium">
                {{ t("Current question") }}
              </div>
              <div class="flex flex-1 flex-col gap-2">
                <div
                  v-for="item in hotspotDependencyTreeItems"
                  :key="item.key"
                  class="rounded border border-gray-20 bg-gray-10 px-3 py-2"
                >
                  <span class="font-semibold">{{ item.label }}:</span>
                  <span>{{ item.destination }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <div class="space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-lg font-semibold text-gray-90">{{ t("Hotspot") }}</h3>
            <div class="flex flex-wrap gap-2">
              <BaseButton
                v-if="isPolygonHotspotType(currentHotspotItem?.hotspotType)"
                :disabled="hotspotPolygonPoints.length < 3"
                :label="t('Finish polygon')"
                icon="check"
                size="small"
                type="success"
                @click="finishHotspotPolygon"
              />
              <BaseButton
                :label="addHotspotLabel"
                icon="plus"
                size="small"
                type="success"
                @click="addHotspotItem"
              />
              <BaseButton
                v-if="isHotspotDelineationQuestion"
                :label="t('Area to avoid')"
                icon="plus"
                size="small"
                type="secondary"
                @click="addHotspotOarItem"
              />
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full border-collapse bg-white text-sm">
              <thead class="bg-gray-15 text-gray-90">
                <tr>
                  <th class="w-14 border border-gray-20 px-2 py-2 text-left">{{ t("N°") }}</th>
                  <th class="min-w-[220px] border border-gray-20 px-2 py-2 text-left">{{ t("Answer") }}</th>
                  <th class="w-40 border border-gray-20 px-2 py-2 text-left">{{ t("Shape") }}</th>
                  <th class="min-w-[160px] border border-gray-20 px-2 py-2 text-left">{{ t("Coordinates") }}</th>
                  <th
                    v-if="!isHotspotCombinationQuestion"
                    class="w-28 border border-gray-20 px-2 py-2 text-right"
                  >
                    {{ t("Score") }}
                  </th>
                  <th
                    v-if="isHotspotDelineationQuestion"
                    class="w-36 border border-gray-20 px-2 py-2 text-right"
                  >
                    {{ t("Minimum overlap") }}
                  </th>
                  <th
                    v-if="isHotspotDelineationQuestion"
                    class="w-36 border border-gray-20 px-2 py-2 text-right"
                  >
                    {{ t("Maximum excess") }}
                  </th>
                  <th
                    v-if="isHotspotDelineationQuestion"
                    class="w-36 border border-gray-20 px-2 py-2 text-right"
                  >
                    {{ t("Maximum missing") }}
                  </th>
                  <th class="min-w-[220px] border border-gray-20 px-2 py-2 text-left">{{ t("Feedback") }}</th>
                  <th class="w-28 border border-gray-20 px-2 py-2 text-right">{{ t("Actions") }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in form.hotspotItems"
                  :key="item.localId"
                  :class="index === currentHotspotIndex ? 'bg-primary/5' : ''"
                >
                  <td class="border border-gray-20 px-2 py-2 align-top">{{ index + 1 }}</td>
                  <td class="border border-gray-20 px-2 py-2 align-top">
                    <BaseInputText
                      :id="`exercise-hotspot-answer-${item.localId}`"
                      v-model="item.answer"
                      :label="t('Answer')"
                      :name="`hotspot_answer_${index}`"
                    />
                  </td>
                  <td class="border border-gray-20 px-2 py-2 align-top">
                    <BaseSelect
                      :id="`exercise-hotspot-type-${item.localId}`"
                      v-model="item.hotspotType"
                      :label="t('Shape')"
                      :name="`hotspot_type_${index}`"
                      :options="currentHotspotTypeOptions"
                      @change="selectHotspotItem(index)"
                    />
                  </td>
                  <td class="border border-gray-20 px-2 py-2 align-top">
                    <BaseInputText
                      :id="`exercise-hotspot-coordinates-${item.localId}`"
                      v-model="item.coordinates"
                      :label="t('Coordinates')"
                      :name="`hotspot_coordinates_${index}`"
                    />
                  </td>
                  <td
                    v-if="!isHotspotCombinationQuestion"
                    class="border border-gray-20 px-2 py-2 align-top"
                  >
                    <BaseInputNumber
                      v-if="item.hotspotType !== 'oar'"
                      :id="`exercise-hotspot-score-${item.localId}`"
                      v-model="item.score"
                      :label="t('Score')"
                      :name="`hotspot_score_${index}`"
                      :min="0"
                      :step="0.1"
                    />
                    <div
                      v-else
                      class="rounded border border-gray-20 bg-gray-15 px-3 py-2 text-sm text-gray-70"
                    >
                      {{ t("Area to avoid") }}: 0
                    </div>
                  </td>
                  <td
                    v-if="isHotspotDelineationQuestion"
                    class="border border-gray-20 px-2 py-2 align-top"
                  >
                    <BaseInputNumber
                      v-if="item.hotspotType === 'delineation'"
                      :id="`exercise-hotspot-min-overlap-${item.localId}`"
                      v-model="item.minOverlap"
                      :label="t('Minimum overlap')"
                      :name="`hotspot_min_overlap_${index}`"
                      :min="0"
                      :step="1"
                    />
                    <div
                      v-else
                      class="rounded border border-gray-20 bg-gray-15 px-3 py-2 text-sm text-gray-70"
                    >
                      {{ t("Area to avoid") }}
                    </div>
                  </td>
                  <td
                    v-if="isHotspotDelineationQuestion"
                    class="border border-gray-20 px-2 py-2 align-top"
                  >
                    <BaseInputNumber
                      v-if="item.hotspotType === 'delineation'"
                      :id="`exercise-hotspot-max-excess-${item.localId}`"
                      v-model="item.maxExcess"
                      :label="t('Maximum excess')"
                      :name="`hotspot_max_excess_${index}`"
                      :min="0"
                      :step="1"
                    />
                    <div
                      v-else
                      class="rounded border border-gray-20 bg-gray-15 px-3 py-2 text-sm text-gray-70"
                    >
                      {{ t("Area to avoid") }}
                    </div>
                  </td>
                  <td
                    v-if="isHotspotDelineationQuestion"
                    class="border border-gray-20 px-2 py-2 align-top"
                  >
                    <BaseInputNumber
                      v-if="item.hotspotType === 'delineation'"
                      :id="`exercise-hotspot-max-missing-${item.localId}`"
                      v-model="item.maxMissing"
                      :label="t('Maximum missing')"
                      :name="`hotspot_max_missing_${index}`"
                      :min="0"
                      :step="1"
                    />
                    <div
                      v-else
                      class="rounded border border-gray-20 bg-gray-15 px-3 py-2 text-sm text-gray-70"
                    >
                      {{ t("Area to avoid") }}
                    </div>
                  </td>
                  <td class="border border-gray-20 px-2 py-2 align-top">
                    <BaseTextArea
                      :id="`exercise-hotspot-comment-${item.localId}`"
                      v-model="item.comment"
                      :label="t('Feedback')"
                      :name="`hotspot_comment_${index}`"
                    />
                  </td>
                  <td class="border border-gray-20 px-2 py-2 text-right align-top">
                    <div class="flex justify-end gap-1">
                      <BaseButton
                        :label="t('Select')"
                        icon="cursor-default-click"
                        only-icon
                        size="small"
                        type="primary-text"
                        @click="selectHotspotItem(index)"
                      />
                      <BaseButton
                        :disabled="form.hotspotItems.length <= 1"
                        :label="t('Delete')"
                        icon="delete"
                        only-icon
                        size="small"
                        type="danger-text"
                        @click="removeHotspotItem(index)"
                      />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section
        v-if="isAnnotationQuestion"
        class="space-y-4 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ t("Annotation") }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Upload image (jpg, png or gif) to apply hotspots.") }}
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <BaseInputNumber
            id="exercise-annotation-score"
            v-model="form.score"
            :label="t('Score')"
            name="score"
            :min="0"
            :step="0.1"
          />
        </div>

        <div
          v-if="annotationPreviewUrl"
          class="space-y-2"
        >
          <p class="text-sm font-semibold text-gray-90">{{ t("Image") }}</p>
          <img
            :alt="form.annotationImageName || t('Image')"
            class="max-h-80 max-w-full rounded-lg border border-gray-20 object-contain"
            :src="annotationPreviewUrl"
          />
          <p
            v-if="form.annotationImageName"
            class="text-sm text-gray-70"
          >
            {{ form.annotationImageName }}
          </p>
        </div>

        <BaseFileUpload
          v-if="!isEditMode"
          accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
          :label="t('Image')"
          size="small"
          @file-selected="selectAnnotationImage"
        />
      </section>

      <section
        v-if="isCalculatedAnswerQuestion"
        class="space-y-4 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ t("Calculated answer") }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Please type your text below and use square brackets [...] to define one or more blanks.") }}
          </p>
        </div>

        <BaseTinyEditor
          editor-id="exercise-calculated-text"
          v-model="form.calculatedText"
          :editor-config="calculatedEditorConfig"
          :full-page="false"
          :title="t('Answer')"
          @update:model-value="syncCalculatedRanges"
        />

        <div class="rounded-lg border border-info/30 bg-support-1 px-4 py-3 text-sm text-support-4">
          {{ t("If you want only integer values write both limits without decimals") }}
        </div>

        <div
          v-if="form.calculatedRanges.length"
          class="overflow-x-auto rounded-lg border border-gray-20"
        >
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Variable ranges") }}</th>
                <th class="w-40 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Lowest value") }}</th>
                <th class="w-40 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Highest value") }}</th>
                <th class="w-44 px-3 py-2 font-semibold">{{ t("Range value") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(range, index) in form.calculatedRanges"
                :key="`calculated-range-${range.token}-${index}`"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-3">
                  <code class="rounded bg-gray-15 px-2 py-1 text-gray-90">{{ range.token }}</code>
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3">
                  <BaseInputText
                    :id="`exercise-calculated-low-${index}`"
                    v-model="range.low"
                    :label="t('Lowest value')"
                    :name="`lowestValue_${index}`"
                    @blur="refreshCalculatedRandom(range)"
                  />
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3">
                  <BaseInputText
                    :id="`exercise-calculated-high-${index}`"
                    v-model="range.high"
                    :label="t('Highest value')"
                    :name="`highestValue_${index}`"
                    @blur="refreshCalculatedRandom(range)"
                  />
                </td>
                <td class="border-t border-gray-20 px-3 py-3 text-gray-80">
                  {{ t("Range value") }}: {{ range.random }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <BaseInputText
            id="exercise-calculated-formula"
            v-model="form.calculatedFormula"
            :help-text="t('Formula sample: sqrt( [x] / [y] ) * ( e ^ ( ln(pi) ) )')"
            :label="t('Formula')"
            name="formula"
          />

          <BaseInputNumber
            id="exercise-calculated-score"
            v-model="form.score"
            :label="t('Score')"
            name="weighting"
            :min="0"
            :step="0.1"
          />

          <BaseInputNumber
            id="exercise-calculated-variations"
            v-model="form.calculatedVariations"
            :label="t('Question variations')"
            name="answerVariations"
            :min="1"
            :step="1"
          />
        </div>

        <a
          class="inline-flex w-fit items-center rounded-lg border border-info/30 bg-support-1 px-3 py-2 text-sm font-medium text-support-4 hover:bg-primary/10"
          href="/main/exercise/evalmathnotation.php"
          rel="noopener noreferrer"
          target="_blank"
        >
          {{ t("Formula notation") }}
        </a>

        <BaseTinyEditor
          editor-id="exercise-calculated-comment"
          v-model="form.calculatedComment"
          :editor-config="matchingCommentEditorConfig"
          :full-page="false"
          :title="t('Comment')"
        />
      </section>

      <section
        v-if="isFillBlanksQuestion"
        class="space-y-4 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ t("Fill in blanks") }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Use the selected markers to define blanks. Example: The capital of Peru is [Lima].") }}
          </p>
        </div>

        <BaseTinyEditor
          editor-id="exercise-fill-blanks-text"
          v-model="form.fillBlanksText"
          :editor-config="advancedEditorConfig"
          :full-page="false"
          :title="t('Answer')"
        />

        <div class="grid gap-4 md:grid-cols-3">
          <BaseSelect
            id="exercise-fill-blanks-separator"
            v-model="form.fillBlanksSeparator"
            :label="t('Select a blanks marker')"
            name="fill_blanks_separator"
            :options="fillBlanksSeparatorOptions"
          />

          <div class="flex items-end pb-2">
            <BaseCheckbox
              id="exercise-fill-blanks-switchable"
              v-model="form.fillBlanksSwitchable"
              :label="t('Switchable blanks')"
              name="fill_blanks_switchable"
            />
          </div>

          <div class="flex items-end pb-2">
            <BaseCheckbox
              id="exercise-fill-blanks-case-insensitive"
              v-model="form.fillBlanksCaseInsensitive"
              :label="t('Case insensitive')"
              name="fill_blanks_case_insensitive"
            />
          </div>
        </div>

        <div
          v-if="isFillBlanksCombination"
          class="grid gap-4 md:grid-cols-3"
        >
          <BaseInputNumber
            id="exercise-fill-blanks-global-score"
            v-model="form.globalScore"
            :label="t('Score')"
            name="fill_blanks_global_score"
            :min="0"
            :step="0.1"
          />
        </div>

        <BaseTinyEditor
          editor-id="exercise-fill-blanks-comment"
          v-model="form.fillBlanksComment"
          :editor-config="answerEditorConfig"
          :full-page="false"
          :title="t('Comment')"
        />

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :label="t('Refresh terms')"
            type="secondary"
            @click="syncFillBlankItems"
          />
          <span class="text-sm text-gray-70">
            {{ t("Detected blanks: {0}", [form.fillBlankItems.length]) }}
          </span>
        </div>

        <div
          v-if="form.fillBlankItems.length"
          class="overflow-x-auto rounded-lg border border-gray-20"
        >
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="w-16 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("N°") }}</th>
                <th class="border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Blank") }}</th>
                <th
                  v-if="!isFillBlanksCombination"
                  class="w-40 border-r border-gray-25 px-3 py-2 font-semibold"
                >
                  {{ t("Score") }}
                </th>
                <th class="w-44 px-3 py-2 font-semibold">{{ t("Input width") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(blank, index) in form.fillBlankItems"
                :key="`blank-${index}`"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-3 font-semibold text-gray-90">
                  {{ index + 1 }}
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3">
                  <code class="rounded bg-gray-15 px-2 py-1 text-gray-90">{{ blank.answer }}</code>
                </td>
                <td
                  v-if="!isFillBlanksCombination"
                  class="border-r border-t border-gray-20 px-3 py-3"
                >
                  <BaseInputNumber
                    :id="`exercise-fill-blank-score-${index}`"
                    v-model="blank.score"
                    :label="t('Score')"
                    :name="`fill_blank_score_${index}`"
                    :min="0"
                    :step="0.1"
                  />
                </td>
                <td class="border-t border-gray-20 px-3 py-3">
                  <BaseInputNumber
                    :id="`exercise-fill-blank-size-${index}`"
                    v-model="blank.inputSize"
                    :label="t('Input width')"
                    :name="`fill_blank_input_size_${index}`"
                    :min="40"
                    :max="800"
                    :step="10"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else
          class="rounded-lg border border-yellow-100 bg-yellow-50 px-4 py-3 text-sm text-yellow-800"
        >
          {{ t("Please define at least one blank with the selected marker") }}
        </div>
      </section>

      <section
        v-if="isDropdownQuestion"
        class="space-y-5 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ t("Answer list") }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Enter the dropdown options, one answer by line. Then mark the expected answers below, like the legacy tool.") }}
          </p>
        </div>

        <BaseTextArea
          id="exercise-dropdown-answer-list"
          v-model="form.dropdownListText"
          :label="t('Answer list')"
          name="dropdown_answer_list"
          rows="8"
        />

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :label="t('Refresh answers')"
            type="secondary"
            @click="syncDropdownAnswersFromList"
          />
          <span class="text-sm text-gray-70">
            {{ formatTranslatedText("Options: {0}", [dropdownOptionCount]) }}
          </span>
        </div>

        <div
          v-if="isDropdownCombinationQuestion"
          class="grid gap-4 rounded-lg border border-gray-20 bg-white p-4 md:grid-cols-3"
        >
          <BaseInputNumber
            id="exercise-dropdown-global-score"
            v-model="form.globalScore"
            :label="t('Score')"
            name="dropdown_global_score"
            :min="0"
            :step="0.1"
          />
          <p class="self-end pb-2 text-sm text-gray-70 md:col-span-2">
            {{ t('This dropdown combination question uses one global score, like legacy.') }}
          </p>
        </div>

        <div
          v-if="form.answers.length"
          class="overflow-x-auto rounded-lg border border-gray-20 bg-white shadow-sm"
        >
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="w-16 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("N°") }}</th>
                <th class="w-24 border-r border-gray-25 px-3 py-2 text-center font-semibold">{{ t("Expected") }}</th>
                <th class="border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Answer") }}</th>
                <th
                  v-if="!isDropdownCombinationQuestion"
                  class="w-40 px-3 py-2 font-semibold"
                >
                  {{ t("Score") }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(answer, index) in form.answers"
                :key="answer.localId"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-support-2'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-3 font-semibold text-gray-90">
                  {{ index + 1 }}
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3 text-center">
                  <input
                    v-model="answer.correct"
                    class="h-4 w-4 rounded border-gray-25 text-primary focus:ring-primary"
                    :name="`dropdown_correct_${index}`"
                    type="checkbox"
                  />
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3 text-gray-90">
                  {{ displayText(answer.answer, t('Untitled')) }}
                </td>
                <td
                  v-if="!isDropdownCombinationQuestion"
                  class="border-t border-gray-20 px-3 py-3"
                >
                  <BaseInputNumber
                    :id="`exercise-dropdown-score-${index}`"
                    v-model="answer.score"
                    :disabled="!answer.correct"
                    :label="t('Score')"
                    :name="`dropdown_score_${index}`"
                    :min="0"
                    :step="0.1"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else
          class="rounded-lg border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning"
        >
          {{ t("Please enter at least two dropdown options.") }}
        </div>
      </section>

      <section
        v-if="isMatchingQuestion"
        class="space-y-5 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ t("Match them") }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Create destination options first, then link each item to the correct option.") }}
          </p>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-20">
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="w-16 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("N°") }}</th>
                <th class="min-w-[380px] border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Answer") }}</th>
                <th class="w-72 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Matches To") }}</th>
                <th
                  v-if="!isMatchingCombinationQuestion"
                  class="w-36 border-r border-gray-25 px-3 py-2 font-semibold"
                >
                  {{ t("Score") }}
                </th>
                <th class="min-w-[320px] px-3 py-2 font-semibold">{{ t("Comment") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(pair, index) in form.matchingPairs"
                :key="pair.localId"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-3 align-middle font-semibold text-gray-90">
                  {{ index + 1 }}
                </td>
                <td class="border-r border-t border-gray-20 px-2 py-3 align-top">
                  <BaseTinyEditor
                    :editor-id="`exercise-matching-pair-${pair.localId}`"
                    v-model="pair.answer"
                    :editor-config="matchingEditorConfig"
                    :full-page="false"
                    :title="t('Answer')"
                  />
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3 align-middle">
                  <BaseSelect
                    :id="`exercise-matching-pair-option-${pair.localId}`"
                    v-model="pair.optionLocalId"
                    :label="t('Matches To')"
                    :name="`matching_pair_option_${index}`"
                    :options="matchingOptionSelectOptions"
                  />
                </td>
                <td
                  v-if="!isMatchingCombinationQuestion"
                  class="border-r border-t border-gray-20 px-3 py-3 align-middle"
                >
                  <BaseInputNumber
                    :id="`exercise-matching-score-${pair.localId}`"
                    v-model="pair.score"
                    :label="t('Score')"
                    :name="`matching_pair_score_${index}`"
                    :min="0"
                    :step="0.1"
                  />
                </td>
                <td class="border-t border-gray-20 px-2 py-3 align-top">
                  <BaseTinyEditor
                    :editor-id="`exercise-matching-comment-${pair.localId}`"
                    v-model="pair.comment"
                    :editor-config="matchingCommentEditorConfig"
                    :full-page="false"
                    :title="t('Comment')"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-20">
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="w-16 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("N°") }}</th>
                <th class="min-w-[520px] px-3 py-2 font-semibold">{{ t("Answer") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(option, index) in form.matchingOptions"
                :key="option.localId"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-3 align-middle font-semibold text-gray-90">
                  {{ getMatchingOptionLabel(index + 1) }}
                </td>
                <td class="border-t border-gray-20 px-2 py-3 align-top">
                  <BaseTinyEditor
                    :editor-id="`exercise-matching-option-${option.localId}`"
                    v-model="option.answer"
                    :editor-config="matchingEditorConfig"
                    :full-page="false"
                    :title="t('Answer')"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="isMatchingCombinationQuestion"
          class="grid gap-4 rounded-lg border border-gray-20 bg-white p-4 md:grid-cols-3"
        >
          <BaseInputNumber
            id="exercise-matching-global-score"
            v-model="form.globalScore"
            :label="t('Score')"
            name="matching_global_score"
            :min="0"
            :step="0.1"
          />
          <p class="self-end pb-2 text-sm text-gray-70 md:col-span-2">
            {{ t('This matching combination question uses one global score, like legacy.') }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :disabled="form.matchingOptions.length <= 2 || form.matchingPairs.length <= 2"
            :label="t('Remove element')"
            icon="delete"
            type="danger"
            @click="removeMatchingElement"
          />
          <BaseButton
            :label="t('Add element')"
            icon="plus"
            type="success"
            @click="addMatchingElement"
          />
          <BaseButton
            v-if="!isReadOnlyFromLearningPath"
            :is-loading="isSaving"
            :label="t('Add this question to the test')"
            icon="check"
            :is-submit="true"
            type="primary"
          />
        </div>
      </section>


      <section
        v-if="isDraggableOrderingQuestion"
        class="space-y-5 rounded-lg border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="space-y-1">
          <h2 class="text-xl font-semibold text-gray-90">{{ t("Sequence ordering") }}</h2>
          <p class="text-sm text-gray-70">
            {{ t("Create the steps and choose the expected position for each one, like legacy Draggable.") }}
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <BaseSelect
            id="exercise-draggable-orientation"
            v-model="form.matchingOrientation"
            :label="t('Choose orientation')"
            name="draggable_orientation"
            :options="matchingOrientationOptions"
          />
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-20">
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="w-16 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("N°") }}</th>
                <th class="min-w-[520px] border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Answer") }}</th>
                <th class="w-52 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Matches To") }}</th>
                <th class="w-36 px-3 py-2 font-semibold">{{ t("Score") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in form.draggableItems"
                :key="item.localId"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-3 align-middle font-semibold text-gray-90">
                  {{ index + 1 }}
                </td>
                <td class="border-r border-t border-gray-20 px-2 py-3 align-top">
                  <BaseTinyEditor
                    :editor-id="`exercise-draggable-item-${item.localId}`"
                    v-model="item.answer"
                    :editor-config="matchingEditorConfig"
                    :full-page="false"
                    :title="t('Answer')"
                  />
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-3 align-middle">
                  <BaseSelect
                    :id="`exercise-draggable-target-${item.localId}`"
                    v-model="item.targetPosition"
                    :label="t('Matches To')"
                    :name="`draggable_target_${index}`"
                    :options="draggablePositionSelectOptions"
                  />
                </td>
                <td class="border-t border-gray-20 px-3 py-3 align-middle">
                  <BaseInputNumber
                    :id="`exercise-draggable-score-${item.localId}`"
                    v-model="item.score"
                    :label="t('Score')"
                    :name="`draggable_score_${index}`"
                    :min="0"
                    :step="0.1"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :disabled="form.draggableItems.length <= 2"
            :label="t('Remove element')"
            icon="delete"
            type="danger"
            @click="removeDraggableItem"
          />
          <BaseButton
            :label="t('Add element')"
            icon="plus"
            type="success"
            @click="addDraggableItem"
          />
          <BaseButton
            v-if="!isReadOnlyFromLearningPath"
            :is-loading="isSaving"
            :label="t('Add this question to the test')"
            icon="check"
            :is-submit="true"
            type="primary"
          />
        </div>
      </section>

      <section
        v-if="showUniqueAnswerAdaptiveScenario"
        class="space-y-3 rounded-lg border border-info/30 bg-support-1 px-4 py-3"
      >
        <h3 class="text-sm font-semibold uppercase tracking-wide text-support-4">
          {{ t("Adaptive behavior (success/failure)") }}
        </h3>
        <div class="grid gap-4 md:grid-cols-2">
          <BaseSelect
            id="exercise-adaptive-success-destination"
            v-model="form.hotspotScenarioSuccessType"
            :label="t('On success')"
            name="adaptive_success_destination"
            :options="form.hotspotScenarioOptions"
          />
          <BaseInputText
            v-if="form.hotspotScenarioSuccessType === 'url'"
            id="exercise-adaptive-success-url"
            v-model="form.hotspotScenarioSuccessUrl"
            :label="t('Custom URL')"
            name="adaptive_success_url"
          />
          <BaseSelect
            id="exercise-adaptive-failure-destination"
            v-model="form.hotspotScenarioFailureType"
            :label="t('On failure')"
            name="adaptive_failure_destination"
            :options="form.hotspotScenarioOptions"
          />
          <BaseInputText
            v-if="form.hotspotScenarioFailureType === 'url'"
            id="exercise-adaptive-failure-url"
            v-model="form.hotspotScenarioFailureUrl"
            :label="t('Custom URL')"
            name="adaptive_failure_url"
          />
        </div>

        <div class="rounded-lg border border-info/20 bg-white p-3 text-sm text-gray-80">
          <div class="mb-2 font-semibold text-gray-90">
            {{ t("Dependency tree") }}
          </div>
          <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <div class="rounded border border-gray-20 bg-gray-10 px-3 py-2 font-medium">
              {{ t("Current question") }}
            </div>
            <div class="flex flex-1 flex-col gap-2">
              <div
                v-for="item in hotspotDependencyTreeItems"
                :key="item.key"
                class="rounded border border-gray-20 bg-gray-10 px-3 py-2"
              >
                <span class="font-semibold">{{ item.label }}:</span>
                <span>{{ item.destination }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div
        v-if="!isMatchingQuestion && !isDraggableOrderingQuestion"
        class="flex flex-wrap items-center gap-2"
      >
        <BaseButton
          v-if="!isReadOnlyFromLearningPath"
          :is-loading="isSaving"
          :label="t('Save the question')"
          icon="check"
          :is-submit="true"
          type="success"
        />
        <BaseButton
          v-if="canConvertAnswerType && isUniqueAnswer"
          :label="t('Convert to multiple answer')"
          icon="arrows-left-right"
          type="black"
          @click="convertToMultipleAnswer"
        />
        <BaseButton
          v-if="canConvertAnswerType && !isUniqueAnswer"
          :label="t('Convert to unique answer')"
          icon="arrows-left-right"
          type="black"
          @click="convertToUniqueAnswer"
        />
      </div>

      <section
        v-if="hasAnswerOptions"
        class="space-y-4"
      >
        <h2 class="text-2xl font-semibold text-gray-90">{{ t("Answers") }}</h2>
        <div class="border-b border-gray-20" />

        <div
          v-if="isUniqueAnswerImage"
          class="rounded-lg border border-info/30 bg-support-1 px-4 py-3 text-sm text-support-4"
        >
          <p>
            {{ t("Images will be resized (up or down) to 200x150 pixels. For a better rendering of the question, we recommend you upload only images of this size.") }}
          </p>
          <p
            v-if="imageZoomEnabled"
            class="mt-1"
          >
            {{ t("Add image with zoom") }}
          </p>
        </div>

        <div
          v-if="usesGlobalScore"
          class="grid gap-4 rounded-lg border border-gray-20 bg-white p-4 md:grid-cols-3"
        >
          <BaseInputNumber
            id="exercise-question-global-score"
            v-model="form.globalScore"
            :label="t('Score')"
            name="global_score"
            :min="0"
            :step="0.1"
          />

          <div
            v-if="isGlobalMultipleAnswer"
            class="flex items-end pb-2"
          >
            <BaseCheckbox
              id="exercise-question-no-negative-score"
              v-model="form.noNegativeScore"
              :label="t('No negative score')"
              name="no_negative_score"
            />
          </div>
        </div>

        <div
          v-if="usesTrueFalseScores"
          class="grid gap-4 rounded-lg border border-gray-20 bg-white p-4 md:grid-cols-3"
        >
          <BaseInputNumber
            id="exercise-question-correct-score"
            v-model="form.correctScore"
            :label="t('Correct')"
            name="correct_score"
            :step="0.1"
          />
          <BaseInputNumber
            id="exercise-question-wrong-score"
            v-model="form.wrongScore"
            :label="t('Wrong')"
            name="wrong_score"
            :step="0.1"
          />
          <BaseInputNumber
            v-if="usesUnknownScore"
            id="exercise-question-unknown-score"
            v-model="form.unknownScore"
            :label="t('Don\'t know')"
            name="unknown_score"
            :step="0.1"
          />
        </div>

        <div
          v-if="isDegreeCertaintyQuestion"
          class="rounded-lg border border-info/30 bg-support-1 px-4 py-3 text-sm text-support-4"
        >
          {{ t("Learners will choose True or False and then select their degree of certainty during the attempt. The editor stores the same Correct/Wrong scores and certainty levels as legacy.") }}
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-20 bg-white shadow-sm">
          <table class="min-w-full border-collapse text-sm">
            <thead class="bg-gray-15 text-left text-gray-90">
              <tr>
                <th class="w-16 border-r border-gray-25 px-3 py-2 font-semibold">{{ t("N°") }}</th>
                <th class="w-20 border-r border-gray-25 px-3 py-2 text-center font-semibold">{{ t("True") }}</th>
                <th
                  v-if="isTrueFalseQuestion"
                  class="w-20 border-r border-gray-25 px-3 py-2 text-center font-semibold"
                >
                  {{ t("False") }}
                </th>
                <th class="min-w-[360px] border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Answer") }}</th>
                <th class="min-w-[360px] border-r border-gray-25 px-3 py-2 font-semibold">{{ t("Comment") }}</th>
                <th
                  v-if="!usesGlobalScore && !usesTrueFalseScores"
                  class="w-40 px-3 py-2 font-semibold"
                >
                  {{ t("Score") }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(answer, index) in form.answers"
                :key="answer.localId"
                :class="index % 2 === 0 ? 'bg-white' : 'bg-blue-50'"
              >
                <td class="border-r border-t border-gray-20 px-3 py-4 align-middle font-semibold text-gray-90">
                  {{ index + 1 }}
                </td>
                <td class="border-r border-t border-gray-20 px-3 py-4 text-center align-middle">
                  <input
                    v-if="isTrueFalseQuestion"
                    v-model="answer.correctChoice"
                    class="h-4 w-4"
                    :name="`correct_choice_${index}`"
                    type="radio"
                    :value="1"
                  />
                  <input
                    v-else-if="isSingleCorrectAnswer"
                    :checked="answer.correct"
                    class="h-4 w-4"
                    :disabled="answer.isUnknown"
                    :name="'correct_answer'"
                    type="radio"
                    @change="setUniqueCorrectAnswer(index)"
                  />
                  <input
                    v-else
                    v-model="answer.correct"
                    class="h-4 w-4"
                    :name="`correct_answer_${index}`"
                    type="checkbox"
                  />
                </td>
                <td
                  v-if="isTrueFalseQuestion"
                  class="border-r border-t border-gray-20 px-3 py-4 text-center align-middle"
                >
                  <input
                    v-model="answer.correctChoice"
                    class="h-4 w-4"
                    :name="`correct_choice_${index}`"
                    type="radio"
                    :value="2"
                  />
                </td>
                <td class="border-r border-t border-gray-20 px-2 py-3 align-top">
                  <BaseTinyEditor
                    :editor-id="`exercise-answer-${answer.localId}`"
                    v-model="answer.answer"
                    :editor-config="isUniqueAnswerImage ? imageAnswerEditorConfig : answerEditorConfig"
                    :full-page="false"
                    :title="t('Answer')"
                    :use-file-manager="isUniqueAnswerImage"
                  />
                </td>
                <td class="border-r border-t border-gray-20 px-2 py-3 align-top">
                  <BaseTinyEditor
                    :editor-id="`exercise-answer-comment-${answer.localId}`"
                    v-model="answer.comment"
                    :editor-config="answerEditorConfig"
                    :full-page="false"
                    :title="t('Comment')"
                  />
                </td>
                <td
                  v-if="!usesGlobalScore && !usesTrueFalseScores"
                  class="border-t border-gray-20 px-3 py-4 align-middle"
                >
                  <BaseInputNumber
                    :id="`exercise-answer-score-${index}`"
                    v-model="answer.score"
                    :disabled="answer.isUnknown"
                    :label="t('Score')"
                    :name="`answer_score_${index}`"
                    :step="0.1"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div
        v-if="hasAnswerOptions"
        class="flex flex-wrap items-center gap-2 pt-2"
      >
        <BaseButton
          :disabled="regularAnswerCount <= 2"
          :label="t('Remove answer option')"
          icon="delete"
          type="danger"
          @click="removeLastAnswer"
        />
        <BaseButton
          :label="t('Add answer option')"
          icon="plus"
          type="success"
          @click="addAnswer"
        />
        <BaseButton
          :is-loading="isSaving"
          :label="t('Add this question to the test')"
          icon="check"
          :is-submit="true"
          type="primary"
        />
      </div>

      <p class="text-sm text-gray-70">* {{ t("Required field") }}</p>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const UNIQUE_ANSWER = 1
const MULTIPLE_ANSWER = 2
const FILL_IN_BLANKS = 3
const MATCHING = 4
const FREE_ANSWER = 5
const HOT_SPOT = 6
const HOT_SPOT_DELINEATION = 8
const MULTIPLE_ANSWER_COMBINATION = 9
const UNIQUE_ANSWER_NO_OPTION = 10
const MULTIPLE_ANSWER_TRUE_FALSE = 11
const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12
const ORAL_EXPRESSION = 13
const GLOBAL_MULTIPLE_ANSWER = 14
const MEDIA_QUESTION = 15
const CALCULATED_ANSWER = 16
const UNIQUE_ANSWER_IMAGE = 17
const ANNOTATION = 20
const READING_COMPREHENSION = 21
const DRAGGABLE = 18
const MATCHING_DRAGGABLE = 19
const MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY = 22
const MATCHING_COMBINATION = 24
const UPLOAD_ANSWER = 23
const MATCHING_DRAGGABLE_COMBINATION = 25
const HOT_SPOT_COMBINATION = 26
const FILL_IN_BLANKS_COMBINATION = 27
const MULTIPLE_ANSWER_DROPDOWN_COMBINATION = 28
const MULTIPLE_ANSWER_DROPDOWN = 29
const PAGE_BREAK = 31
const UNKNOWN_ANSWER_POSITION = 666

const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const csrfToken = ref("")
const typeLabel = ref("")
const questionCount = ref(0)
const totalScore = ref(0)
const categoryOptions = ref([])
const mediaOptions = ref([])
const showAdvancedSettings = ref(false)
const allowQuestionFeedback = ref(false)
const imageZoomEnabled = ref(false)
const allowMandatoryQuestion = ref(false)
const canUseHotspotDelineationScenario = ref(false)
const isReadOnlyFromLearningPath = ref(false)
const learningPathReadOnlyMessage = ref(
  "This exercise has been included in a learning path, so it cannot be accessed by students directly from here. If you want to put the same exercise available through the exercises tool, please make a copy of the current exercise using the copy icon.",
)
let answerCounter = 0
let matchingOptionCounter = 0
let matchingPairCounter = 0
let hotspotItemCounter = 0

const hotspotImageRef = ref(null)
const hotspotCanvasContainer = ref(null)
const hotspotImageSize = reactive({ width: 0, height: 0, naturalWidth: 0, naturalHeight: 0 })
const currentHotspotIndex = ref(0)
const drawingDraft = ref(null)
const hotspotPolygonPoints = ref([])

const minimalEditorToolbar =
  "undo redo | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code fullscreen"

const minimalEditorPlugins = "lists link image media table code fullscreen charmap emoticons"

const advancedEditorConfig = {
  height: 240,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const structuralEditorConfig = {
  height: 260,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const readingComprehensionEditorConfig = {
  height: 420,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const answerEditorConfig = {
  height: 220,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const calculatedEditorConfig = {
  height: 350,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const imageAnswerEditorConfig = {
  height: 260,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
  file_picker_types: "image",
  image_dimensions: true,
  image_advtab: true,
}

const matchingEditorConfig = {
  height: 200,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const matchingCommentEditorConfig = {
  height: 170,
  menubar: false,
  plugins: minimalEditorPlugins,
  toolbar: minimalEditorToolbar,
  toolbar_mode: "sliding",
}

const difficultyOptions = [
  { label: "1", value: 1 },
  { label: "2", value: 2 },
  { label: "3", value: 3 },
  { label: "4", value: 4 },
  { label: "5", value: 5 },
]

const readingSpeedOptions = computed(() => [
  { label: t("%s words per minute", [50]), value: 1 },
  { label: t("%s words per minute", [100]), value: 2 },
  { label: t("%s words per minute", [175]), value: 3 },
  { label: t("%s words per minute", [300]), value: 4 },
  { label: t("%s words per minute", [600]), value: 5 },
])

const fillBlanksSeparatorOptions = [
  { label: "[...]", value: 0 },
  { label: "{...}", value: 1 },
  { label: "(...)", value: 2 },
  { label: "*...*", value: 3 },
  { label: "#...#", value: 4 },
  { label: "%...%", value: 5 },
  { label: "$...$", value: 6 },
]

const form = reactive({
  type: UNIQUE_ANSWER,
  title: "",
  description: "",
  feedback: "",
  fillBlanksText: "",
  fillBlankItems: [],
  fillBlanksSeparator: 0,
  fillBlanksSwitchable: false,
  fillBlanksCaseInsensitive: false,
  fillBlanksComment: "",
  calculatedText: "",
  calculatedFormula: "",
  calculatedRanges: [],
  calculatedVariations: 1,
  calculatedComment: "",
  annotationImageUrl: "",
  annotationImageData: "",
  annotationImageName: "",
  annotationImageMimeType: "",
  hotspotImageUrl: "",
  hotspotImageData: "",
  hotspotImageName: "",
  hotspotImageMimeType: "",
  hotspotItems: [],
  hotspotScenarioOptions: [],
  hotspotScenarioSuccessType: "",
  hotspotScenarioSuccessUrl: "",
  hotspotScenarioFailureType: "",
  hotspotScenarioFailureUrl: "",
  score: 1,
  globalScore: 0,
  correctScore: 1,
  wrongScore: -0.5,
  unknownScore: 0,
  noNegativeScore: false,
  mandatory: false,
  duration: null,
  difficulty: 1,
  categoryId: 0,
  parentMediaId: 0,
  answers: [],
  dropdownListText: "",
  matchingOptions: [],
  matchingPairs: [],
  draggableItems: [],
  matchingOrientation: 'h',
})

const exerciseId = computed(() => Number(getQueryValue(route.params.exerciseId) || 0))
const isGlobalQuestionMode = computed(() => ['ExerciseGlobalQuestionCreate', 'ExerciseGlobalQuestionEdit'].includes(String(route.name)))
const questionId = computed(() => Number(getQueryValue(route.params.questionId) || 0))
const questionType = computed(() => Number(getQueryValue(route.params.questionType) || UNIQUE_ANSWER))
const isEditMode = computed(() => questionId.value > 0)
const isUniqueAnswer = computed(() => UNIQUE_ANSWER === Number(form.type))
const isUniqueAnswerNoOption = computed(() => UNIQUE_ANSWER_NO_OPTION === Number(form.type))
const isUniqueAnswerImage = computed(() => UNIQUE_ANSWER_IMAGE === Number(form.type))
const isAnnotationQuestion = computed(() => ANNOTATION === Number(form.type))
const isHotspotQuestion = computed(() => [HOT_SPOT, HOT_SPOT_DELINEATION, HOT_SPOT_COMBINATION].includes(Number(form.type)))
const isHotspotDelineationQuestion = computed(() => HOT_SPOT_DELINEATION === Number(form.type))
const showAdaptiveQuestionScenario = computed(() => canUseHotspotDelineationScenario.value && [UNIQUE_ANSWER, HOT_SPOT_DELINEATION].includes(Number(form.type)))
const showHotspotDelineationScenario = computed(() => isHotspotDelineationQuestion.value && showAdaptiveQuestionScenario.value)
const showUniqueAnswerAdaptiveScenario = computed(() => isUniqueAnswer.value && showAdaptiveQuestionScenario.value)
const isHotspotCombinationQuestion = computed(() => HOT_SPOT_COMBINATION === Number(form.type))
const isCalculatedAnswerQuestion = computed(() => CALCULATED_ANSWER === Number(form.type))
const isOpenQuestion = computed(() => FREE_ANSWER === Number(form.type))
const isOralExpressionQuestion = computed(() => ORAL_EXPRESSION === Number(form.type))
const isUploadAnswerQuestion = computed(() => UPLOAD_ANSWER === Number(form.type))
const isMediaQuestion = computed(() => MEDIA_QUESTION === Number(form.type))
const isReadingComprehensionQuestion = computed(() => READING_COMPREHENSION === Number(form.type))
const isPageBreakQuestion = computed(() => PAGE_BREAK === Number(form.type))
const isStructuralQuestion = computed(() => isMediaQuestion.value || isReadingComprehensionQuestion.value || isPageBreakQuestion.value)
const canMarkMandatoryQuestion = computed(() => allowMandatoryQuestion.value && !isStructuralQuestion.value)
const isFillBlanksQuestion = computed(() => [FILL_IN_BLANKS, FILL_IN_BLANKS_COMBINATION].includes(Number(form.type)))
const isFillBlanksCombination = computed(() => FILL_IN_BLANKS_COMBINATION === Number(form.type))
const isDropdownQuestion = computed(() => [MULTIPLE_ANSWER_DROPDOWN, MULTIPLE_ANSWER_DROPDOWN_COMBINATION].includes(Number(form.type)))
const isDropdownCombinationQuestion = computed(() => MULTIPLE_ANSWER_DROPDOWN_COMBINATION === Number(form.type))
const isMatchingQuestion = computed(() => [MATCHING, MATCHING_COMBINATION, MATCHING_DRAGGABLE, MATCHING_DRAGGABLE_COMBINATION].includes(Number(form.type)))
const isMatchingCombinationQuestion = computed(() => [MATCHING_COMBINATION, MATCHING_DRAGGABLE_COMBINATION].includes(Number(form.type)))
const isDraggableOrderingQuestion = computed(() => DRAGGABLE === Number(form.type))
const hasAnswerOptions = computed(() => !isOpenQuestion.value && !isOralExpressionQuestion.value && !isAnnotationQuestion.value && !isHotspotQuestion.value && !isUploadAnswerQuestion.value && !isCalculatedAnswerQuestion.value && !isStructuralQuestion.value && !isFillBlanksQuestion.value && !isDropdownQuestion.value && !isMatchingQuestion.value && !isDraggableOrderingQuestion.value)
const isGlobalMultipleAnswer = computed(() => GLOBAL_MULTIPLE_ANSWER === Number(form.type))
const isDegreeCertaintyQuestion = computed(() => MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY === Number(form.type))
const isTrueFalseQuestion = computed(() => [MULTIPLE_ANSWER_TRUE_FALSE, MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY].includes(Number(form.type)))
const usesTrueFalseScores = computed(() => [MULTIPLE_ANSWER_TRUE_FALSE, MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY].includes(Number(form.type)))
const usesUnknownScore = computed(() => MULTIPLE_ANSWER_TRUE_FALSE === Number(form.type))
const usesGlobalScore = computed(() => [MULTIPLE_ANSWER_COMBINATION, MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, GLOBAL_MULTIPLE_ANSWER, MATCHING_COMBINATION, MATCHING_DRAGGABLE_COMBINATION, HOT_SPOT_COMBINATION, FILL_IN_BLANKS_COMBINATION, MULTIPLE_ANSWER_DROPDOWN_COMBINATION].includes(Number(form.type)))
const isSingleCorrectAnswer = computed(() => [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION, UNIQUE_ANSWER_IMAGE].includes(Number(form.type)))
const canConvertAnswerType = computed(() => [UNIQUE_ANSWER, MULTIPLE_ANSWER].includes(Number(form.type)))
const annotationPreviewUrl = computed(() => form.annotationImageData || form.annotationImageUrl || "")
const hotspotPreviewUrl = computed(() => form.hotspotImageData || form.hotspotImageUrl || "")
const currentHotspotItem = computed(() => form.hotspotItems[currentHotspotIndex.value] || null)
const hotspotTotalScore = computed(() =>
  form.hotspotItems.reduce((total, item) => total + (item.hotspotType === "oar" ? 0 : Number(item.score || 0)), 0),
)
const hotspotEditorTitle = computed(() => {
  if (isHotspotDelineationQuestion.value) {
    return t("Hotspot delineation")
  }

  return isHotspotCombinationQuestion.value ? t("Hotspot combination") : t("Hotspot")
})
const addHotspotLabel = computed(() => isHotspotDelineationQuestion.value ? t("Add delineation") : t("Add hotspot"))
const hotspotDependencyTreeItems = computed(() => [
  {
    key: "success",
    label: t("On success"),
    destination: getScenarioOptionLabel(form.hotspotScenarioSuccessType, form.hotspotScenarioSuccessUrl),
  },
  {
    key: "failure",
    label: t("On failure"),
    destination: getScenarioOptionLabel(form.hotspotScenarioFailureType, form.hotspotScenarioFailureUrl),
  },
])
const regularAnswerCount = computed(() => form.answers.filter((answer) => !answer.isUnknown).length)
const dropdownOptionCount = computed(() => form.answers.length)
const editorTitle = computed(() => {
  const label = typeLabel.value || getTypeLabel(Number(form.type))

  if (isEditMode.value) {
    return formatTranslatedText("Edit question: {0}", [t(label)])
  }

  return isGlobalQuestionMode.value
    ? formatTranslatedText("Add a question: {0}", [t(label)])
    : formatTranslatedText("Add this question to the test: {0}", [t(label)])
})
const summaryText = computed(() => {
  if (isGlobalQuestionMode.value) {
    return ""
  }

  return formatTranslatedText("{0} questions, for a total score (all questions) of {1}.", [questionCount.value, totalScore.value])
})
const manualCorrectionHelpText = computed(() => {
  if (isUploadAnswerQuestion.value) {
    return t("This upload question has no answer options. Its score is assigned during correction/results review.")
  }

  if (isOralExpressionQuestion.value) {
    return t("This oral expression question has no answer options. Its score is assigned during correction/results review.")
  }

  return t("This open question has no answer options. Its score is assigned during correction/results review.")
})
const structuralQuestionTitle = computed(() => {
  if (isReadingComprehensionQuestion.value) {
    return t("Reading comprehension")
  }

  return isMediaQuestion.value ? t("Media question") : t("Page break")
})
const structuralQuestionHelp = computed(() => {
  if (isReadingComprehensionQuestion.value) {
    return t("Add a reading text with scrolling speed settings.")
  }

  return isMediaQuestion.value
    ? t("Add content, media or reading context without adding score.")
    : t("Insert a page break without adding score.")
})
const structuralEditorTitle = computed(() => {
  if (isReadingComprehensionQuestion.value) {
    return t("Text")
  }

  return isMediaQuestion.value ? t("Content") : t("Description")
})
const categorySelectOptions = computed(() => [
  { label: t("No category selected"), value: 0 },
  ...categoryOptions.value,
])
const mediaSelectOptions = computed(() => [
  { label: t("Not linked to media"), value: 0 },
  ...mediaOptions.value,
])
const matchingOptionSelectOptions = computed(() =>
  form.matchingOptions.map((option, index) => ({
    label: `${getMatchingOptionLabel(index + 1)} - ${displayText(option.answer, t("Option"))}`,
    value: option.localId,
  })),
)
const questionsRoute = computed(() => {
  if (isGlobalQuestionMode.value) {
    return {
      name: isEditMode.value ? "ExerciseQuestionPool" : "ExerciseList",
      params: { node: route.params.node },
      query: getContextParams(),
    }
  }

  return {
    name: "ExerciseQuestions",
    params: { node: route.params.node, exerciseId: exerciseId.value },
    query: getContextParams(),
  }
})

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

function getEditorParams() {
  const params = { ...getContextParams() }

  if (!isEditMode.value && route.query.isContent !== undefined) {
    params.isContent = getQueryValue(route.query.isContent)
  }

  return params
}

function formatTranslatedText(key, replacements = []) {
  return replacements.reduce(
    (text, value, index) => String(text).split(`{${index}}`).join(String(value)),
    t(key),
  )
}

function formatScore(value) {
  const numberValue = Number(value || 0)

  return Number.isInteger(numberValue) ? String(numberValue) : numberValue.toFixed(2).replace(/\.?0+$/, "")
}

function getTypeLabel(type) {
  if (UNIQUE_ANSWER === type) {
    return "Unique answer"
  }

  if (MULTIPLE_ANSWER === type) {
    return "Multiple answer"
  }

  if (FILL_IN_BLANKS === type) {
    return "Fill in blanks"
  }

  if (MATCHING === type) {
    return "Matching"
  }

  if (FREE_ANSWER === type) {
    return "Open question"
  }

  if (HOT_SPOT === type) {
    return "Hotspot"
  }

  if (HOT_SPOT_DELINEATION === type) {
    return "Hotspot delineation"
  }

  if (MULTIPLE_ANSWER_COMBINATION === type) {
    return "Exact Selection"
  }

  if (UNIQUE_ANSWER_NO_OPTION === type) {
    return "Unique answer with unknown"
  }

  if (MULTIPLE_ANSWER_TRUE_FALSE === type) {
    return "Multiple answer true/false"
  }

  if (MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE === type) {
    return "Multiple answer combination true/false"
  }

  if (GLOBAL_MULTIPLE_ANSWER === type) {
    return "Global multiple answer"
  }

  if (MEDIA_QUESTION === type) {
    return "Media question"
  }

  if (READING_COMPREHENSION === type) {
    return "Reading comprehension"
  }

  if (UNIQUE_ANSWER_IMAGE === type) {
    return "Unique answer with images"
  }

  if (ANNOTATION === type) {
    return "Annotation"
  }

  if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY === type) {
    return "Multiple answer true/false with degree of certainty"
  }

  if (UPLOAD_ANSWER === type) {
    return "Upload answer"
  }

  if (DRAGGABLE === type) {
    return "Draggable"
  }

  if (MATCHING_DRAGGABLE === type) {
    return "Matching draggable"
  }

  if (MATCHING_DRAGGABLE_COMBINATION === type) {
    return "Matching draggable combination"
  }

  if (HOT_SPOT_COMBINATION === type) {
    return "Hotspot combination"
  }

  if (FILL_IN_BLANKS_COMBINATION === type) {
    return "Fill in blanks combination"
  }

  if (MULTIPLE_ANSWER_DROPDOWN_COMBINATION === type) {
    return "Multiple answer dropdown combination"
  }

  if (MULTIPLE_ANSWER_DROPDOWN === type) {
    return "Multiple answer dropdown"
  }

  if (PAGE_BREAK === type) {
    return "Page break"
  }

  return "Question"
}

function createEmptyAnswer(index = 0, isUnknown = false) {
  answerCounter += 1

  return {
    localId: `answer-${Date.now()}-${answerCounter}`,
    answer: isUnknown ? t("Don't know") : "",
    correct: isSingleCorrectAnswer.value && !isUnknown ? 0 === index : false,
    correctChoice: isTrueFalseQuestion.value ? 1 : null,
    comment: "",
    score: usesGlobalScore.value || usesTrueFalseScores.value || isUnknown ? 0 : (0 === index ? 1 : 0),
    position: isUnknown ? UNKNOWN_ANSWER_POSITION : index + 1,
    isUnknown,
  }
}

function getMatchingOptionLabel(position) {
  const value = Number(position || 0)

  if (value >= 1 && value <= 26) {
    return String.fromCharCode(64 + value)
  }

  return String(value || "")
}

function createEmptyMatchingOption(index = 0) {
  matchingOptionCounter += 1

  return {
    localId: `option-${Date.now()}-${matchingOptionCounter}`,
    label: getMatchingOptionLabel(index + 1),
    answer: "",
    position: index + 1,
  }
}

function createEmptyMatchingPair(index = 0) {
  matchingPairCounter += 1

  return {
    localId: `pair-${Date.now()}-${matchingPairCounter}`,
    answer: "",
    optionLocalId: form.matchingOptions[index]?.localId || form.matchingOptions[0]?.localId || "",
    comment: "",
    score: 10,
    position: form.matchingOptions.length + index + 1,
  }
}

function createEmptyDraggableItem(index = 0) {
  matchingPairCounter += 1

  return {
    localId: `draggable-${Date.now()}-${matchingPairCounter}`,
    answer: "",
    targetPosition: index + 1,
    score: 10,
    position: index + 1,
  }
}

const draggablePositionSelectOptions = computed(() =>
  form.draggableItems.map((_item, index) => ({
    label: String(index + 1),
    value: index + 1,
  })),
)

const matchingOrientationOptions = [
  { label: "Horizontal", value: "h" },
  { label: "Vertical", value: "v" },
]

const hotspotTypeOptions = [
  { label: "Square", value: "square" },
  { label: "Ellipse", value: "circle" },
  { label: "Polygon", value: "poly" },
]

const hotspotDelineationTypeOptions = [
  { label: "Delineation", value: "delineation" },
  { label: "Area to avoid", value: "oar" },
]

const currentHotspotTypeOptions = computed(() =>
  isHotspotDelineationQuestion.value ? hotspotDelineationTypeOptions : hotspotTypeOptions,
)

function normalizeDraggableItems(items = []) {
  const sourceItems = Array.isArray(items) && items.length ? items : [createEmptyDraggableItem(0), createEmptyDraggableItem(1)]

  return sourceItems.map((item, index) => ({
    localId: item.localId || `draggable-${Date.now()}-${index + 1}`,
    answer: item.answer || "",
    targetPosition: Number(item.targetPosition || index + 1),
    score: Number(item.score ?? 10),
    position: index + 1,
  }))
}

function addDraggableItem() {
  form.draggableItems.push(createEmptyDraggableItem(form.draggableItems.length))
}

function removeDraggableItem() {
  if (form.draggableItems.length <= 2) {
    return
  }

  form.draggableItems.pop()
  form.draggableItems.forEach((item, index) => {
    if (Number(item.targetPosition || 0) > form.draggableItems.length) {
      item.targetPosition = form.draggableItems.length
    }
    item.position = index + 1
  })
}


function normalizeMatchingOptions(items = []) {
  const sourceItems = Array.isArray(items) && items.length ? items : [createEmptyMatchingOption(0), createEmptyMatchingOption(1)]

  return sourceItems.map((item, index) => ({
    localId: item.localId || `option-${Date.now()}-${index + 1}`,
    label: getMatchingOptionLabel(index + 1),
    answer: item.answer || "",
    position: index + 1,
  }))
}

function normalizeMatchingPairs(items = []) {
  const sourceItems = Array.isArray(items) && items.length ? items : [createEmptyMatchingPair(0), createEmptyMatchingPair(1)]
  const firstOptionLocalId = form.matchingOptions[0]?.localId || ""
  const optionLocalIds = form.matchingOptions.map((option) => option.localId)

  return sourceItems.map((item, index) => {
    const optionLocalId = optionLocalIds.includes(item.optionLocalId) ? item.optionLocalId : firstOptionLocalId

    return {
      localId: item.localId || `pair-${Date.now()}-${index + 1}`,
      answer: item.answer || "",
      optionLocalId,
      comment: item.comment || "",
      score: Number(item.score ?? 10),
      position: form.matchingOptions.length + index + 1,
    }
  })
}

function relabelMatchingOptions() {
  form.matchingOptions.forEach((option, index) => {
    option.label = getMatchingOptionLabel(index + 1)
    option.position = index + 1
  })
}

function repositionMatchingPairs() {
  form.matchingPairs.forEach((pair, index) => {
    pair.position = form.matchingOptions.length + index + 1
  })
}

function addMatchingOption() {
  form.matchingOptions.push(createEmptyMatchingOption(form.matchingOptions.length))
  relabelMatchingOptions()
  repositionMatchingPairs()
}

function removeMatchingOption(index) {
  if (form.matchingOptions.length <= 2) {
    return
  }

  const removedOption = form.matchingOptions[index]
  form.matchingOptions.splice(index, 1)
  relabelMatchingOptions()

  const fallbackOptionLocalId = form.matchingOptions[0]?.localId || ""
  form.matchingPairs.forEach((pair) => {
    if (pair.optionLocalId === removedOption?.localId) {
      pair.optionLocalId = fallbackOptionLocalId
    }
  })

  repositionMatchingPairs()
}

function addMatchingPair() {
  form.matchingPairs.push(createEmptyMatchingPair(form.matchingPairs.length))
  repositionMatchingPairs()
}

function removeMatchingPair(index) {
  if (form.matchingPairs.length <= 2) {
    return
  }

  form.matchingPairs.splice(index, 1)
  repositionMatchingPairs()
}

function addMatchingElement() {
  addMatchingOption()
  addMatchingPair()
}

function removeMatchingElement() {
  if (form.matchingOptions.length <= 2 || form.matchingPairs.length <= 2) {
    return
  }

  removeMatchingPair(form.matchingPairs.length - 1)
  removeMatchingOption(form.matchingOptions.length - 1)
}

function normalizeOption(option) {
  return {
    label: option.label || option.title || "",
    value: Number(option.value ?? option.id ?? 0),
  }
}

function fillForm(data) {
  isReadOnlyFromLearningPath.value = true === data.isReadOnlyFromLearningPath
  learningPathReadOnlyMessage.value = data.learningPathReadOnlyMessage || learningPathReadOnlyMessage.value
  form.type = Number(data.type || questionType.value || UNIQUE_ANSWER)
  typeLabel.value = data.typeLabel || getTypeLabel(form.type)
  form.title = data.title || ""
  form.description = data.description || ""
  form.feedback = data.feedback || ""
  form.dropdownListText = data.dropdownListText || ""
  form.fillBlanksText = data.fillBlanksText || ""
  form.fillBlankItems = Array.isArray(data.fillBlankItems) ? normalizeFillBlankItems(data.fillBlankItems) : []
  form.fillBlanksSeparator = Number(data.fillBlanksSeparator || 0)
  form.fillBlanksSwitchable = true === data.fillBlanksSwitchable
  form.fillBlanksCaseInsensitive = true === data.fillBlanksCaseInsensitive
  form.fillBlanksComment = data.fillBlanksComment || ""
  form.calculatedText = data.calculatedText || ""
  form.calculatedFormula = data.calculatedFormula || ""
  form.calculatedRanges = normalizeCalculatedRanges(data.calculatedRanges || [], form.calculatedText)
  form.calculatedVariations = Number(data.calculatedVariations || 1)
  form.calculatedComment = data.calculatedComment || ""
  form.annotationImageUrl = data.annotationImageUrl || ""
  form.annotationImageData = ""
  form.annotationImageName = data.annotationImageName || ""
  form.annotationImageMimeType = ""
  form.hotspotImageUrl = data.hotspotImageUrl || ""
  form.hotspotImageData = ""
  form.hotspotImageName = data.hotspotImageName || ""
  form.hotspotImageMimeType = ""
  form.hotspotItems = normalizeHotspotItems(data.hotspotItems || [])
  form.hotspotScenarioOptions = Array.isArray(data.hotspotScenarioOptions) ? normalizeScenarioOptions(data.hotspotScenarioOptions) : []
  form.hotspotScenarioSuccessType = data.hotspotScenarioSuccessType || ""
  form.hotspotScenarioSuccessUrl = data.hotspotScenarioSuccessUrl || ""
  form.hotspotScenarioFailureType = data.hotspotScenarioFailureType || ""
  form.hotspotScenarioFailureUrl = data.hotspotScenarioFailureUrl || ""
  currentHotspotIndex.value = 0
  hotspotPolygonPoints.value = []
  form.score = Number(data.score || 0)
  form.globalScore = Number(data.globalScore || 0)
  form.correctScore = Number(data.correctScore ?? 1)
  form.wrongScore = Number(data.wrongScore ?? -0.5)
  form.unknownScore = Number(data.unknownScore ?? 0)
  form.noNegativeScore = true === data.noNegativeScore
  form.mandatory = true === data.mandatory
  form.duration = data.duration || null
  form.difficulty = Number(data.difficulty || 1)
  form.categoryId = Number(data.categoryId || 0)
  form.parentMediaId = Number(data.parentMediaId || 0)
  questionCount.value = Number(data.questionCount || 0)
  totalScore.value = Number(data.totalScore || 0)
  categoryOptions.value = Array.isArray(data.categoryOptions) ? data.categoryOptions.map(normalizeOption) : []
  mediaOptions.value = Array.isArray(data.mediaOptions) ? data.mediaOptions.map(normalizeOption) : []
  csrfToken.value = data.csrfToken || ""
  allowQuestionFeedback.value = true === data.allowQuestionFeedback
  imageZoomEnabled.value = true === data.imageZoomEnabled
  allowMandatoryQuestion.value = true === data.allowMandatoryQuestion
  canUseHotspotDelineationScenario.value = true === data.canUseHotspotDelineationScenario

  if (isDropdownQuestion.value) {
    form.matchingOptions = []
    form.matchingPairs = []
    form.draggableItems = []
    form.matchingOrientation = data.matchingOrientation || "h"
    const answers = Array.isArray(data.answers) ? data.answers : []
    form.answers = answers.map((answer, index) => ({
      localId: `dropdown-${answer.id || index}-${Date.now()}`,
      answer: answer.answer || "",
      correct: true === answer.correct,
      correctChoice: null,
      comment: "",
      score: isDropdownCombinationQuestion.value ? 0 : Number(answer.score || 0),
      position: Number(answer.position || index + 1),
      isUnknown: false,
    }))
    syncDropdownListTextFromAnswers()
    return
  }

  if (isHotspotQuestion.value) {
    form.answers = []
    form.matchingOptions = []
    form.matchingPairs = []
    form.draggableItems = []
    return
  }

  if (isMatchingQuestion.value) {
    form.answers = []
    form.matchingOptions = normalizeMatchingOptions(data.matchingOptions)
    form.matchingPairs = normalizeMatchingPairs(data.matchingPairs)
    form.draggableItems = []
    form.matchingOrientation = data.matchingOrientation || 'h'
    return
  }

  if (isDraggableOrderingQuestion.value) {
    form.answers = []
    form.matchingOptions = []
    form.matchingPairs = []
    form.draggableItems = normalizeDraggableItems(data.draggableItems)
    form.matchingOrientation = ['h', 'v'].includes(data.matchingOrientation) ? data.matchingOrientation : 'h'
    return
  }

  if (!hasAnswerOptions.value) {
    form.answers = []
    form.matchingOptions = []
    form.matchingPairs = []
    form.draggableItems = []
    form.matchingOrientation = data.matchingOrientation || 'h'
    if (isFillBlanksQuestion.value && !form.fillBlankItems.length) {
      syncFillBlankItems()
    }
    return
  }

  form.matchingOptions = []
  form.matchingPairs = []
  form.draggableItems = []
  form.matchingOrientation = data.matchingOrientation || 'h'

  const answers = Array.isArray(data.answers) && data.answers.length ? data.answers : [createEmptyAnswer(0), createEmptyAnswer(1)]
  form.answers = answers.map((answer, index) => ({
    localId: `answer-${answer.id || index}-${Date.now()}`,
    answer: answer.answer || "",
    correct: true === answer.correct,
    correctChoice: Number(answer.correctChoice || (true === answer.correct ? 1 : 2)),
    comment: answer.comment || "",
    score: Number(answer.score || 0),
    position: Number(answer.position || index + 1),
    isUnknown: true === answer.isUnknown || UNKNOWN_ANSWER_POSITION === Number(answer.position || 0),
  }))

  ensureUnknownAnswer()

  if (isSingleCorrectAnswer.value && !form.answers.some((answer) => answer.correct && !answer.isUnknown)) {
    const firstRegularAnswer = form.answers.find((answer) => !answer.isUnknown)
    if (firstRegularAnswer) {
      firstRegularAnswer.correct = true
    }
  }
}



function extractCalculatedTokens(text) {
  const matches = String(text || "").match(/\[[^\]]+\]/g) || []
  return [...new Set(matches.map((token) => token.trim()).filter((token) => token !== ""))]
}

function normalizeCalculatedRanges(ranges = [], text = form.calculatedText) {
  const previousByToken = new Map()
  for (const range of Array.isArray(ranges) ? ranges : []) {
    if (range?.token) {
      previousByToken.set(String(range.token), range)
    }
  }

  return extractCalculatedTokens(text).map((token, index) => {
    const previous = previousByToken.get(token) || {}
    const low = String(previous.low ?? "1")
    const high = String(previous.high ?? "20")

    return {
      token,
      low,
      high,
      random: previous.random || buildCalculatedRandomPreview(low, high),
      position: index + 1,
    }
  })
}

function buildCalculatedRandomPreview(low, high) {
  let minimum = Number(low || 0)
  let maximum = Number(high || 0)

  if (Number.isNaN(minimum)) {
    minimum = 1
  }

  if (Number.isNaN(maximum)) {
    maximum = 20
  }

  if (maximum < minimum) {
    const oldMinimum = minimum
    minimum = maximum
    maximum = oldMinimum
  }

  const hasDecimal = String(low).includes(".") || String(high).includes(".")
  const randomValue = Math.random() * (maximum - minimum) + minimum

  return hasDecimal ? randomValue.toFixed(2) : String(Math.floor(randomValue))
}

function refreshCalculatedRandom(range) {
  range.random = buildCalculatedRandomPreview(range.low, range.high)
}

function syncCalculatedRanges() {
  form.calculatedRanges = normalizeCalculatedRanges(form.calculatedRanges, form.calculatedText)
}

function normalizeFillBlankItems(items = []) {
  return items.map((item, index) => ({
    answer: item.answer || "",
    score: Number(item.score ?? 1),
    inputSize: Number(item.inputSize ?? 200),
    position: Number(item.position || index + 1),
  }))
}

function getFillBlankSeparators(separator) {
  const value = Number(separator || 0)

  if (1 === value) {
    return ["{", "}"]
  }

  if (2 === value) {
    return ["(", ")"]
  }

  if (3 === value) {
    return ["*", "*"]
  }

  if (4 === value) {
    return ["#", "#"]
  }

  if (5 === value) {
    return ["%", "%"]
  }

  if (6 === value) {
    return ["$", "$"]
  }

  return ["[", "]"]
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
}

function extractFillBlankAnswers(text, separator) {
  const [start, end] = getFillBlankSeparators(separator)
  const regex = new RegExp(`${escapeRegExp(start)}(.*?)${escapeRegExp(end)}`, "gs")
  const items = []
  let match

  while ((match = regex.exec(String(text || ""))) !== null) {
    items.push(String(match[1] || "").trim())
  }

  return items
}

function syncFillBlankItems() {
  const previousItems = [...form.fillBlankItems]
  const blanks = extractFillBlankAnswers(form.fillBlanksText, form.fillBlanksSeparator)

  form.fillBlankItems = blanks.map((blank, index) => {
    const previous = previousItems[index] || {}

    return {
      answer: blank,
      score: Number(previous.score ?? 1),
      inputSize: Number(previous.inputSize ?? 200),
      position: index + 1,
    }
  })
}

function normalizeDropdownLines(value) {
  return String(value || "")
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line !== "")
}

function syncDropdownListTextFromAnswers() {
  form.dropdownListText = form.answers
    .filter((answer) => !answer.isUnknown)
    .map((answer) => displayText(answer.answer, ""))
    .filter((answer) => answer !== "")
    .join("\n")
}

function syncDropdownAnswersFromList() {
  const previousAnswers = [...form.answers]
  const lines = normalizeDropdownLines(form.dropdownListText)

  form.answers = lines.map((line, index) => {
    const previous = previousAnswers.find((answer) => displayText(answer.answer, "") === line) || previousAnswers[index] || {}

    return {
      localId: previous.localId || `dropdown-${Date.now()}-${index + 1}`,
      answer: line,
      correct: true === previous.correct,
      correctChoice: null,
      comment: "",
      score: isDropdownCombinationQuestion.value ? 0 : Number(previous.score || 0),
      position: index + 1,
      isUnknown: false,
    }
  })
}

function addAnswer() {
  const unknownIndex = form.answers.findIndex((answer) => answer.isUnknown)
  const nextAnswer = createEmptyAnswer(regularAnswerCount.value)

  if (unknownIndex >= 0) {
    form.answers.splice(unknownIndex, 0, nextAnswer)
  } else {
    form.answers.push(nextAnswer)
  }
}

function removeLastAnswer() {
  const lastRegularIndex = form.answers.map((answer, index) => ({ answer, index })).reverse().find((item) => !item.answer.isUnknown)?.index
  if (lastRegularIndex !== undefined) {
    removeAnswer(lastRegularIndex)
  }
}

function removeAnswer(index) {
  if (regularAnswerCount.value <= 2 || form.answers[index]?.isUnknown) {
    return
  }

  const wasCorrect = form.answers[index].correct
  form.answers.splice(index, 1)

  if (isSingleCorrectAnswer.value && wasCorrect && form.answers.length) {
    const firstRegularAnswer = form.answers.find((answer) => !answer.isUnknown)
    if (firstRegularAnswer) {
      firstRegularAnswer.correct = true
    }
  }
}

function setUniqueCorrectAnswer(index) {
  if (form.answers[index]?.isUnknown) {
    return
  }

  form.answers.forEach((answer, answerIndex) => {
    answer.correct = !answer.isUnknown && answerIndex === index
  })
}

function ensureUnknownAnswer() {
  if (!isUniqueAnswerNoOption.value) {
    form.answers = form.answers.filter((answer) => !answer.isUnknown)
    return
  }

  const unknownAnswers = form.answers.filter((answer) => answer.isUnknown)
  if (!unknownAnswers.length) {
    form.answers.push(createEmptyAnswer(form.answers.length, true))
  }

  form.answers.forEach((answer) => {
    if (answer.isUnknown) {
      answer.correct = false
      answer.score = 0
      answer.position = UNKNOWN_ANSWER_POSITION
      if (!stripHtml(answer.answer)) {
        answer.answer = t("Don't know")
      }
    }
  })
}

function convertToMultipleAnswer() {
  form.type = MULTIPLE_ANSWER
  typeLabel.value = getTypeLabel(MULTIPLE_ANSWER)
}

function convertToUniqueAnswer() {
  form.type = UNIQUE_ANSWER
  typeLabel.value = getTypeLabel(UNIQUE_ANSWER)

  const firstCorrectIndex = form.answers.findIndex((answer) => answer.correct && !answer.isUnknown)
  form.answers.forEach((answer, index) => {
    answer.correct = !answer.isUnknown && (firstCorrectIndex >= 0 ? index === firstCorrectIndex : 0 === index)
  })
}

function getAnnotationMimeTypeFromExtension(fileName) {
  const extension = String(fileName || "").split(".").pop()?.toLowerCase() || ""

  if (["jpg", "jpeg"].includes(extension)) {
    return "image/jpeg"
  }

  if ("png" === extension) {
    return "image/png"
  }

  if ("gif" === extension) {
    return "image/gif"
  }

  return ""
}

function isAllowedAnnotationImage(file) {
  const mimeType = file?.type || getAnnotationMimeTypeFromExtension(file?.name)

  return ["image/jpeg", "image/png", "image/gif"].includes(mimeType)
}

function selectAnnotationImage(file) {
  if (!file) {
    return
  }

  if (!isAllowedAnnotationImage(file)) {
    errorMessage.value = t("Only PNG, JPG or GIF images allowed")
    form.annotationImageData = ""
    form.annotationImageName = ""
    form.annotationImageMimeType = ""
    return
  }

  const reader = new FileReader()
  reader.onload = () => {
    form.annotationImageData = String(reader.result || "")
    form.annotationImageName = file.name || "annotation_image"
    form.annotationImageMimeType = file.type || getAnnotationMimeTypeFromExtension(file.name)
    errorMessage.value = ""
  }
  reader.onerror = () => {
    errorMessage.value = t("Could not save question")
  }
  reader.readAsDataURL(file)
}

function normalizeHotspotItems(items = []) {
  const sourceItems = Array.isArray(items) && items.length ? items : [createEmptyHotspotItem(0)]

  return sourceItems.map((item, index) => {
    const hotspotType = normalizeHotspotTypeValue(item.hotspotType)

    return {
      localId: item.localId || `hotspot-${Date.now()}-${index + 1}`,
      answer: item.answer || getDefaultHotspotAnswer(hotspotType),
      comment: item.comment || "",
      score: hotspotType === "oar" || isHotspotCombinationQuestion.value ? 0 : Number(item.score || 10),
      position: index + 1,
      hotspotType,
      coordinates: item.coordinates || "0;0|0|0",
      minOverlap: normalizePercentage(item.minOverlap ?? 1, 1),
      maxExcess: normalizePercentage(item.maxExcess ?? 100, 100),
      maxMissing: normalizePercentage(item.maxMissing ?? 100, 100),
    }
  })
}

function normalizeScenarioOptions(options = []) {
  return options
    .filter((option) => option && option.value !== undefined)
    .map((option) => ({ label: t(option.label || String(option.value)), value: String(option.value) }))
}

function normalizePercentage(value, fallback) {
  const numericValue = Number(value)

  if (!Number.isFinite(numericValue)) {
    return fallback
  }

  return Math.min(100, Math.max(0, Math.round(numericValue)))
}

function getScenarioOptionLabel(value, urlFallback = "") {
  const normalizedValue = String(value || "")

  if (normalizedValue === "url") {
    return urlFallback || t("Custom URL")
  }

  const option = form.hotspotScenarioOptions.find((item) => String(item.value) === normalizedValue)

  return option?.label || t("None")
}

function normalizeHotspotTypeValue(type) {
  const value = String(type || "")
  if (["square", "circle", "poly"].includes(value) && !isHotspotDelineationQuestion.value) {
    return value
  }

  if (["delineation", "oar"].includes(value) && isHotspotDelineationQuestion.value) {
    return value
  }

  return isHotspotDelineationQuestion.value ? "delineation" : "square"
}

function isPolygonHotspotType(type) {
  return ["poly", "delineation", "oar"].includes(String(type || ""))
}

function getDefaultHotspotAnswer(type) {
  if ("delineation" === type) {
    return "delineation"
  }

  if ("oar" === type) {
    return t("Area to avoid")
  }

  return ""
}

function createEmptyHotspotItem(index = 0, forcedType = "") {
  hotspotItemCounter += 1
  const hotspotType = normalizeHotspotTypeValue(forcedType || (isHotspotDelineationQuestion.value ? "delineation" : "square"))

  return {
    localId: `hotspot-${Date.now()}-${hotspotItemCounter}`,
    answer: getDefaultHotspotAnswer(hotspotType),
    comment: "",
    score: isHotspotCombinationQuestion.value || hotspotType === "oar" ? 0 : 10,
    position: index + 1,
    hotspotType,
    coordinates: "0;0|0|0",
    minOverlap: 1,
    maxExcess: 100,
    maxMissing: 100,
  }
}

function addHotspotItem() {
  form.hotspotItems.push(createEmptyHotspotItem(form.hotspotItems.length))
  currentHotspotIndex.value = form.hotspotItems.length - 1
  hotspotPolygonPoints.value = []
}

function addHotspotOarItem() {
  form.hotspotItems.push(createEmptyHotspotItem(form.hotspotItems.length, "oar"))
  currentHotspotIndex.value = form.hotspotItems.length - 1
  hotspotPolygonPoints.value = []
}

function removeHotspotItem(index) {
  if (form.hotspotItems.length <= 1) {
    return
  }

  form.hotspotItems.splice(index, 1)
  currentHotspotIndex.value = Math.max(0, Math.min(currentHotspotIndex.value, form.hotspotItems.length - 1))
  hotspotPolygonPoints.value = []
}

function selectHotspotItem(index) {
  currentHotspotIndex.value = index
  hotspotPolygonPoints.value = []
}

function selectHotspotImage(file) {
  selectQuestionImage(file, "hotspot")
}

function selectQuestionImage(file, target) {
  if (!file) {
    return
  }

  if (!isAllowedAnnotationImage(file)) {
    errorMessage.value = t("Only PNG, JPG or GIF images allowed")
    if (target === "hotspot") {
      form.hotspotImageData = ""
      form.hotspotImageName = ""
      form.hotspotImageMimeType = ""
    }
    return
  }

  const reader = new FileReader()
  reader.onload = () => {
    if (target === "hotspot") {
      form.hotspotImageData = String(reader.result || "")
      form.hotspotImageName = file.name || "hotspot_image"
      form.hotspotImageMimeType = file.type || getAnnotationMimeTypeFromExtension(file.name)
    }
    errorMessage.value = ""
  }
  reader.onerror = () => {
    errorMessage.value = t("Could not save question")
  }
  reader.readAsDataURL(file)
}

function updateHotspotImageSize() {
  const image = hotspotImageRef.value
  if (!image) {
    return
  }

  hotspotImageSize.width = image.clientWidth || 0
  hotspotImageSize.height = image.clientHeight || 0
  hotspotImageSize.naturalWidth = image.naturalWidth || hotspotImageSize.width
  hotspotImageSize.naturalHeight = image.naturalHeight || hotspotImageSize.height
}

function toDisplayX(value) {
  const naturalWidth = hotspotImageSize.naturalWidth || hotspotImageSize.width || 1
  return Number(value || 0) * (hotspotImageSize.width || naturalWidth) / naturalWidth
}

function toDisplayY(value) {
  const naturalHeight = hotspotImageSize.naturalHeight || hotspotImageSize.height || 1
  return Number(value || 0) * (hotspotImageSize.height || naturalHeight) / naturalHeight
}

function toNaturalPoint(event) {
  const image = hotspotImageRef.value
  if (!image) {
    return { x: 0, y: 0 }
  }

  const rect = image.getBoundingClientRect()
  const displayX = Math.max(0, Math.min(event.clientX - rect.left, rect.width))
  const displayY = Math.max(0, Math.min(event.clientY - rect.top, rect.height))
  const naturalWidth = hotspotImageSize.naturalWidth || rect.width || 1
  const naturalHeight = hotspotImageSize.naturalHeight || rect.height || 1

  return {
    x: Math.round(displayX * naturalWidth / Math.max(1, rect.width)),
    y: Math.round(displayY * naturalHeight / Math.max(1, rect.height)),
  }
}

function startHotspotDraw(event) {
  if (!isHotspotQuestion.value || !currentHotspotItem.value || !hotspotPreviewUrl.value) {
    return
  }

  event.preventDefault()
  const point = toNaturalPoint(event)
  if (isPolygonHotspotType(currentHotspotItem.value.hotspotType)) {
    addHotspotPolygonPoint(point)
    return
  }

  drawingDraft.value = {
    startX: point.x,
    startY: point.y,
    currentX: point.x,
    currentY: point.y,
    type: currentHotspotItem.value.hotspotType,
    ...buildDraftShape(point.x, point.y, point.x, point.y, currentHotspotItem.value.hotspotType),
  }
}

function moveHotspotDraw(event) {
  if (!drawingDraft.value) {
    return
  }

  const point = toNaturalPoint(event)
  Object.assign(
    drawingDraft.value,
    buildDraftShape(drawingDraft.value.startX, drawingDraft.value.startY, point.x, point.y, drawingDraft.value.type),
    { currentX: point.x, currentY: point.y },
  )
}

function finishHotspotDraw() {
  if (!drawingDraft.value || !currentHotspotItem.value) {
    return
  }

  const x = Math.round(Math.min(drawingDraft.value.startX, drawingDraft.value.currentX))
  const y = Math.round(Math.min(drawingDraft.value.startY, drawingDraft.value.currentY))
  const width = Math.round(Math.abs(drawingDraft.value.currentX - drawingDraft.value.startX))
  const height = Math.round(Math.abs(drawingDraft.value.currentY - drawingDraft.value.startY))
  if (width >= 4 && height >= 4) {
    currentHotspotItem.value.coordinates = `${x};${y}|${width}|${height}`
  }
  drawingDraft.value = null
}

function addHotspotPolygonPoint(point) {
  const points = hotspotPolygonPoints.value
  const lastPoint = points[points.length - 1] || null

  if (lastPoint && isNearHotspotPoint(point, lastPoint)) {
    return
  }

  if (points.length >= 3 && isNearHotspotPoint(point, points[0])) {
    finishHotspotPolygon()
    return
  }

  points.push(point)
}

function isNearHotspotPoint(point, target) {
  const threshold = Math.max(8, Math.round((hotspotImageSize.naturalWidth || hotspotImageSize.width || 600) / 100))
  const distance = Math.hypot(
    Number(point.x || 0) - Number(target.x || 0),
    Number(point.y || 0) - Number(target.y || 0),
  )

  return distance <= threshold
}

function cancelHotspotDraw() {
  drawingDraft.value = null
}

function finishHotspotPolygon() {
  if (!currentHotspotItem.value || hotspotPolygonPoints.value.length < 3) {
    return
  }

  currentHotspotItem.value.coordinates = hotspotPolygonPoints.value.map((point) => `${point.x};${point.y}`).join("|")
  hotspotPolygonPoints.value = []
}

function buildDraftShape(startX, startY, currentX, currentY, type) {
  const x = Math.min(startX, currentX)
  const y = Math.min(startY, currentY)
  const width = Math.abs(currentX - startX)
  const height = Math.abs(currentY - startY)

  if (type === "circle") {
    return {
      kind: "ellipse",
      cx: toDisplayX(x + width / 2),
      cy: toDisplayY(y + height / 2),
      rx: Math.abs(toDisplayX(width) - toDisplayX(0)) / 2,
      ry: Math.abs(toDisplayY(height) - toDisplayY(0)) / 2,
    }
  }

  return {
    kind: "rect",
    x: toDisplayX(x),
    y: toDisplayY(y),
    width: Math.abs(toDisplayX(width) - toDisplayX(0)),
    height: Math.abs(toDisplayY(height) - toDisplayY(0)),
  }
}

function getHotspotShapeFill(index) {
  if (form.hotspotItems[index]?.hotspotType === "oar") {
    return index === currentHotspotIndex.value
      ? "rgb(var(--color-danger-base) / 0.58)"
      : "rgb(var(--color-danger-base) / 0.46)"
  }

  return index === currentHotspotIndex.value
    ? "rgb(var(--color-primary-base) / 0.58)"
    : "rgb(var(--color-primary-base) / 0.46)"
}

function getHotspotShapeStroke(index) {
  if (form.hotspotItems[index]?.hotspotType === "oar") {
    return "rgb(var(--color-danger-base))"
  }

  return index === currentHotspotIndex.value ? "rgb(var(--color-primary-base))" : "#244d67"
}

function getHotspotShape(item) {
  const type = item.hotspotType || "square"
  const coordinates = String(item.coordinates || "")
  if (!hasValidHotspotCoordinates(coordinates)) {
    return { kind: "none" }
  }

  if (isPolygonHotspotType(type)) {
    const points = coordinates
      .split("|")
      .map((pair) => pair.split(";"))
      .filter((pair) => pair.length === 2)
      .map(([x, y]) => `${toDisplayX(Number(x))},${toDisplayY(Number(y))}`)
      .join(" ")

    return points ? { kind: "polygon", points } : { kind: "none" }
  }

  const [position, width = "0", height = "0"] = coordinates.split("|")
  const [x = "0", y = "0"] = position.split(";")
  const naturalX = Number(x || 0)
  const naturalY = Number(y || 0)
  const naturalWidth = Number(width || 0)
  const naturalHeight = Number(height || 0)

  if (type === "circle") {
    return {
      kind: "ellipse",
      cx: toDisplayX(naturalX + naturalWidth / 2),
      cy: toDisplayY(naturalY + naturalHeight / 2),
      rx: Math.abs(toDisplayX(naturalWidth) - toDisplayX(0)) / 2,
      ry: Math.abs(toDisplayY(naturalHeight) - toDisplayY(0)) / 2,
    }
  }

  return {
    kind: "rect",
    x: toDisplayX(naturalX),
    y: toDisplayY(naturalY),
    width: Math.abs(toDisplayX(naturalWidth) - toDisplayX(0)),
    height: Math.abs(toDisplayY(naturalHeight) - toDisplayY(0)),
  }
}

function hasValidHotspotCoordinates(value) {
  const coordinates = String(value || "").trim()
  return Boolean(coordinates && coordinates !== "0;0|0|0")
}

function stripHtml(value) {
  return String(value || "")
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/g, " ")
    .trim()
}

function htmlHasImage(value) {
  return /<img\b[^>]*>/i.test(String(value || ""))
}

function hasAnswerContent(value) {
  return Boolean(stripHtml(value) || htmlHasImage(value))
}

function decodeHtml(value) {
  if (null === value || undefined === value) {
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

function normalizeParentMediaIdForPayload() {
  const mediaId = Number(form.parentMediaId || 0)

  if (mediaId <= 0) {
    return 0
  }

  return mediaOptions.value.some((option) => Number(option.value) === mediaId) ? mediaId : 0
}

function buildPayload() {
  return {
    exerciseId: exerciseId.value,
    questionId: isEditMode.value ? questionId.value : null,
    type: Number(form.type),
    title: form.title,
    description: form.description,
    feedback: form.feedback,
    dropdownListText: form.dropdownListText,
    fillBlanksText: form.fillBlanksText,
    fillBlankItems: form.fillBlankItems.map((item, index) => ({
      answer: item.answer,
      score: Number(item.score || 0),
      inputSize: Number(item.inputSize || 200),
      position: index + 1,
    })),
    fillBlanksSeparator: Number(form.fillBlanksSeparator || 0),
    fillBlanksSwitchable: true === form.fillBlanksSwitchable,
    fillBlanksCaseInsensitive: true === form.fillBlanksCaseInsensitive,
    fillBlanksComment: form.fillBlanksComment,
    calculatedText: form.calculatedText,
    calculatedFormula: form.calculatedFormula,
    calculatedRanges: form.calculatedRanges.map((range, index) => ({
      token: range.token,
      low: String(range.low ?? "1"),
      high: String(range.high ?? "20"),
      position: index + 1,
    })),
    calculatedVariations: Number(form.calculatedVariations || 1),
    calculatedComment: form.calculatedComment,
    annotationImageData: isAnnotationQuestion.value && !isEditMode.value ? form.annotationImageData : "",
    annotationImageName: isAnnotationQuestion.value && !isEditMode.value ? form.annotationImageName : "",
    annotationImageMimeType: isAnnotationQuestion.value && !isEditMode.value ? form.annotationImageMimeType : "",
    hotspotImageData: isHotspotQuestion.value && !isEditMode.value ? form.hotspotImageData : "",
    hotspotImageName: isHotspotQuestion.value && !isEditMode.value ? form.hotspotImageName : "",
    hotspotImageMimeType: isHotspotQuestion.value && !isEditMode.value ? form.hotspotImageMimeType : "",
    hotspotItems: isHotspotQuestion.value
      ? form.hotspotItems.map((item, index) => ({
          answer: item.answer,
          comment: item.comment,
          score: isHotspotCombinationQuestion.value || item.hotspotType === "oar" ? 0 : Number(item.score || 0),
          position: index + 1,
          hotspotType: normalizeHotspotTypeValue(item.hotspotType),
          coordinates: item.coordinates || "",
          minOverlap: normalizePercentage(item.minOverlap ?? 1, 1),
          maxExcess: normalizePercentage(item.maxExcess ?? 100, 100),
          maxMissing: normalizePercentage(item.maxMissing ?? 100, 100),
        }))
      : [],
    hotspotScenarioSuccessType: showAdaptiveQuestionScenario.value ? form.hotspotScenarioSuccessType : "",
    hotspotScenarioSuccessUrl: showAdaptiveQuestionScenario.value ? form.hotspotScenarioSuccessUrl : "",
    hotspotScenarioFailureType: showAdaptiveQuestionScenario.value ? form.hotspotScenarioFailureType : "",
    hotspotScenarioFailureUrl: showAdaptiveQuestionScenario.value ? form.hotspotScenarioFailureUrl : "",
    score: Number(form.score || 0),
    globalScore: Number(form.globalScore || 0),
    correctScore: Number(form.correctScore || 0),
    wrongScore: Number(form.wrongScore || 0),
    unknownScore: isDegreeCertaintyQuestion.value ? 0 : Number(form.unknownScore || 0),
    noNegativeScore: true === form.noNegativeScore,
    mandatory: canMarkMandatoryQuestion.value ? form.mandatory : false,
    duration: isStructuralQuestion.value ? null : (form.duration ? Number(form.duration) : null),
    difficulty: Number(form.difficulty || 1),
    categoryId: isStructuralQuestion.value ? 0 : Number(form.categoryId || 0),
    parentMediaId: isStructuralQuestion.value ? 0 : normalizeParentMediaIdForPayload(),
    matchingOptions: isMatchingQuestion.value
      ? form.matchingOptions.map((option, index) => ({
          localId: option.localId,
          answer: option.answer,
          position: index + 1,
        }))
      : [],
    matchingPairs: isMatchingQuestion.value
      ? form.matchingPairs.map((pair, index) => ({
          answer: pair.answer,
          optionLocalId: pair.optionLocalId,
          comment: pair.comment,
          score: isMatchingCombinationQuestion.value ? 0 : Number(pair.score || 0),
          position: form.matchingOptions.length + index + 1,
        }))
      : [],
    draggableItems: isDraggableOrderingQuestion.value
      ? form.draggableItems.map((item, index) => ({
          answer: item.answer,
          targetPosition: Number(item.targetPosition || index + 1),
          score: Number(item.score || 0),
          position: index + 1,
        }))
      : [],
    matchingOrientation: form.matchingOrientation || 'h',
    answers: (hasAnswerOptions.value || isDropdownQuestion.value)
      ? form.answers.map((answer, index) => ({
          answer: answer.answer,
          correct: true === answer.correct,
          correctChoice: Number(answer.correctChoice || 0),
          comment: answer.comment,
          score: Number(answer.score || 0),
          position: answer.isUnknown ? UNKNOWN_ANSWER_POSITION : index + 1,
          isUnknown: true === answer.isUnknown,
        }))
      : [],
    submittedCsrfToken: csrfToken.value,
  }
}

function validateForm() {
  if (!stripHtml(form.title)) {
    errorMessage.value = t("The title is required.")
    return false
  }

  if (isStructuralQuestion.value) {
    return true
  }

  if (isOpenQuestion.value || isOralExpressionQuestion.value || isUploadAnswerQuestion.value) {
    if (Number(form.score || 0) <= 0) {
      errorMessage.value = t("Required field")
      return false
    }

    return true
  }

  if (isHotspotQuestion.value) {
    if (!isEditMode.value && !form.hotspotImageData) {
      errorMessage.value = t("Please select an image")
      return false
    }

    const filledItems = form.hotspotItems.filter((item) => stripHtml(item.answer) && hasValidHotspotCoordinates(item.coordinates))
    if (!filledItems.length) {
      errorMessage.value = t("Please draw at least one hotspot.")
      return false
    }

    if (isHotspotCombinationQuestion.value) {
      if (Number(form.globalScore || 0) <= 0) {
        errorMessage.value = t("Required field")
        return false
      }

      return true
    }

    if (isHotspotDelineationQuestion.value) {
      const hasDelineation = filledItems.some((item) => item.hotspotType === "delineation")
      if (!hasDelineation) {
        errorMessage.value = t("Please draw at least one delineation.")
        return false
      }

      const invalidDelineationScore = filledItems.some(
        (item) => item.hotspotType !== "oar" && Number(item.score || 0) <= 0,
      )
      if (invalidDelineationScore) {
        errorMessage.value = t("You must give a positive score for each hotspots")
        return false
      }

      return true
    }

    const invalidScore = filledItems.some((item) => Number(item.score || 0) <= 0)
    if (invalidScore) {
      errorMessage.value = t("You must give a positive score for each hotspots")
      return false
    }

    return true
  }

  if (isAnnotationQuestion.value) {
    if (Number(form.score || 0) <= 0) {
      errorMessage.value = t("Required field")
      return false
    }

    if (!isEditMode.value && !form.annotationImageData) {
      errorMessage.value = t("Please select an image")
      return false
    }

    return true
  }

  if (isCalculatedAnswerQuestion.value) {
    syncCalculatedRanges()
    if (!stripHtml(form.calculatedText)) {
      errorMessage.value = t("Please type the text")
      return false
    }

    if (!extractCalculatedTokens(form.calculatedText).length) {
      errorMessage.value = t("Please define at least one blank with the selected marker")
      return false
    }

    if (!String(form.calculatedFormula || "").trim()) {
      errorMessage.value = t("Please, write the formula")
      return false
    }

    if (Number(form.score || 0) <= 0 || Number(form.calculatedVariations || 0) < 1) {
      errorMessage.value = t("Required field")
      return false
    }

    return true
  }

  if (isFillBlanksQuestion.value) {
    syncFillBlankItems()
    if (!stripHtml(form.fillBlanksText)) {
      errorMessage.value = t("The fill in blanks text is required.")
      return false
    }

    if (!form.fillBlankItems.length) {
      errorMessage.value = t("Please define at least one blank with the selected marker")
      return false
    }

    if (isFillBlanksCombination.value) {
      if (Number(form.globalScore || 0) <= 0) {
        errorMessage.value = t("Required field")
        return false
      }

      return true
    }

    const score = form.fillBlankItems.reduce((total, item) => total + Number(item.score || 0), 0)
    if (score <= 0) {
      errorMessage.value = t("Required field")
      return false
    }

    return true
  }

  if (isDropdownQuestion.value) {
    syncDropdownAnswersFromList()
    const filledAnswers = form.answers.filter((answer) => stripHtml(answer.answer))
    if (filledAnswers.length < 2) {
      errorMessage.value = t("Please enter at least two dropdown options.")
      return false
    }

    const correctAnswers = filledAnswers.filter((answer) => true === answer.correct)
    if (correctAnswers.length < 1) {
      errorMessage.value = t("At least one expected answer is required.")
      return false
    }

    if (isDropdownCombinationQuestion.value) {
      if (Number(form.globalScore || 0) <= 0) {
        errorMessage.value = t("Required field")
        return false
      }

      return true
    }

    const score = correctAnswers.reduce((total, answer) => total + Number(answer.score || 0), 0)
    if (score <= 0) {
      errorMessage.value = t("Required field")
      return false
    }

    return true
  }

  if (isDraggableOrderingQuestion.value) {
    const filledItems = form.draggableItems.filter((item) => stripHtml(item.answer))

    if (filledItems.length < 2) {
      errorMessage.value = t("At least two draggable items are required.")
      return false
    }

    const validTargetPositions = form.draggableItems.map((_item, index) => index + 1)
    const invalidTarget = filledItems.some((item) => !validTargetPositions.includes(Number(item.targetPosition || 0)))
    if (invalidTarget) {
      errorMessage.value = t("Each draggable item must be linked to a valid target position.")
      return false
    }

    const score = filledItems.reduce((total, item) => total + Math.max(0, Number(item.score || 0)), 0)
    if (score <= 0) {
      errorMessage.value = t("Required field")
      return false
    }

    return true
  }

  if (isHotspotQuestion.value) {
    form.answers = []
    form.matchingOptions = []
    form.matchingPairs = []
    form.draggableItems = []
    return
  }

  if (isMatchingQuestion.value) {
    const filledOptions = form.matchingOptions.filter((option) => stripHtml(option.answer))
    const filledPairs = form.matchingPairs.filter((pair) => stripHtml(pair.answer))

    if (filledOptions.length < 2) {
      errorMessage.value = t("At least two matching options are required.")
      return false
    }

    if (filledPairs.length < 2) {
      errorMessage.value = t("At least two matching pairs are required.")
      return false
    }

    const validOptionLocalIds = filledOptions.map((option) => option.localId)
    const hasInvalidPair = filledPairs.some((pair) => !validOptionLocalIds.includes(pair.optionLocalId))
    if (hasInvalidPair) {
      errorMessage.value = t("Each matching pair must be linked to a valid option.")
      return false
    }

    const score = isMatchingCombinationQuestion.value
      ? Number(form.globalScore || 0)
      : filledPairs.reduce((total, pair) => total + Number(pair.score || 0), 0)
    if (score <= 0) {
      errorMessage.value = t("Required field")
      return false
    }

    return true
  }

  ensureUnknownAnswer()

  const filledAnswers = form.answers.filter((answer) => hasAnswerContent(answer.answer))
  const filledRegularAnswers = filledAnswers.filter((answer) => !answer.isUnknown)
  if (filledRegularAnswers.length < 2) {
    errorMessage.value = t("At least two answers are required.")
    return false
  }

  if (isTrueFalseQuestion.value) {
    const missingTrueFalseChoice = filledRegularAnswers.some((answer) => ![1, 2].includes(Number(answer.correctChoice || 0)))
    if (missingTrueFalseChoice) {
      errorMessage.value = t("Each true/false answer must have True or False selected.")
      return false
    }
  } else {
    const correctAnswers = filledRegularAnswers.filter((answer) => answer.correct)
    if (isSingleCorrectAnswer.value && correctAnswers.length !== 1) {
      errorMessage.value = t("A unique answer question must have exactly one correct answer.")
      return false
    }

    if (!isSingleCorrectAnswer.value && correctAnswers.length < 1) {
      errorMessage.value = t("At least one correct answer is required.")
      return false
    }
  }

  if (usesGlobalScore.value && Number(form.globalScore || 0) <= 0) {
    errorMessage.value = t("Required field")
    return false
  }

  if (usesTrueFalseScores.value && Number(form.correctScore || 0) <= 0) {
    errorMessage.value = t("Required field")
    return false
  }

  return true
}

async function loadQuestionEditor() {
  if (!isGlobalQuestionMode.value && !exerciseId.value) {
    errorMessage.value = t("A valid exercise id is required.")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = isGlobalQuestionMode.value
      ? await exerciseService.getExerciseGlobalQuestionEditor(
          { ...getEditorParams(), questionId: isEditMode.value ? questionId.value : null },
          isEditMode.value ? null : questionType.value,
        )
      : await exerciseService.getExerciseQuestionEditor(
          getEditorParams(),
          exerciseId.value,
          isEditMode.value ? questionId.value : null,
          isEditMode.value ? null : questionType.value,
        )
    fillForm(response)
  } catch (error) {
    console.error("Error loading exercise question editor", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not load question")
  } finally {
    isLoading.value = false
  }
}

async function saveQuestion() {
  errorMessage.value = ""

  if (isReadOnlyFromLearningPath.value) {
    errorMessage.value = t("This exercise is read-only because it is included in a learning path.")
    return
  }

  if (!validateForm()) {
    return
  }

  isSaving.value = true

  try {
    if (isGlobalQuestionMode.value) {
      await exerciseService.saveExerciseGlobalQuestion(
        buildPayload(),
        getContextParams(),
        isEditMode.value ? questionId.value : null,
      )
    } else {
      await exerciseService.saveExerciseQuestion(
        buildPayload(),
        getContextParams(),
        exerciseId.value,
        isEditMode.value ? questionId.value : null,
      )
    }
    await router.push(questionsRoute.value)
  } catch (error) {
    console.error("Error saving exercise question", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not save question")
  } finally {
    isSaving.value = false
  }
}

watch(() => form.calculatedText, () => {
  if (isCalculatedAnswerQuestion.value) {
    syncCalculatedRanges()
  }
})

onMounted(loadQuestionEditor)
</script>
