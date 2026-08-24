<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Assessments')" />

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="overview">
      <div
        v-if="!overview.hasGradebook"
        class="rounded-xl border border-gray-20 bg-white px-6 py-10 text-center shadow-sm"
      >
        <BaseIcon
          class="mb-3 text-gray-500"
          icon="gradebook"
          size="big"
        />
        <p class="text-sm italic text-gray-500">
          {{ t("No data available") }}
        </p>
      </div>

      <template v-else>
        <BaseToolbar v-if="canManage">
          <template #start>
            <div class="flex flex-wrap items-center gap-2">
              <BaseButton
                v-if="canAddCategory"
                :label="t('Add a category')"
                icon="folder-plus"
                only-icon
                size="normal"
                type="success"
                @click="startCreateCategory"
              />
              <BaseButton
                :disabled="currentCategoryLockedForTeacher || overview.currentCategory?.hasGradeModel"
                :label="t('Add classroom activity')"
                icon="gradebook"
                only-icon
                size="normal"
                type="success"
                @click="startCreateEvaluation"
              />
              <BaseButton
                :disabled="currentCategoryLockedForTeacher || overview.currentCategory?.hasGradeModel"
                :label="t('Add online activity')"
                icon="link-add"
                only-icon
                size="normal"
                type="success"
                @click="startCreateLink"
              />
              <BaseButton
                :label="t('List view')"
                :route="buildReportRoute('GradebookFlatView')"
                icon="view-table"
                only-icon
                size="normal"
                type="primary-text"
              />
              <BaseButton
                :label="t('Students list report')"
                :route="buildReportRoute('GradebookStudentsReport')"
                icon="account"
                only-icon
                size="normal"
                type="primary-text"
              />
              <BaseButton
                v-if="overview.controlledFallbacks?.gradingElectronic"
                :label="t('Export')"
                :to-url="overview.controlledFallbacks.gradingElectronic"
                icon="export"
                only-icon
                size="normal"
                type="primary-text"
              />
            </div>
          </template>

          <template #end>
            <BaseButton
              :disabled="currentCategoryLockedForTeacher"
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="normal"
              type="secondary-text"
              @click="startEditCategory(overview.currentCategory)"
            />
            <BaseButton
              v-if="overview.currentCategory?.generateCertificates"
              :label="t('Certificate')"
              :route="buildReportRoute('GradebookCertificates')"
              icon="gradebook"
              only-icon
              size="normal"
              type="primary-text"
            />
            <BaseButton
              v-if="canShowWeightReport"
              :label="t('Weight in Report')"
              :route="buildReportRoute('GradebookWeights')"
              icon="percent-box-outline"
              only-icon
              size="normal"
              type="secondary-text"
            />
            <BaseButton
              v-if="canShowScoringSettings"
              :label="t('Skills ranking')"
              :route="buildReportRoute('GradebookScoring')"
              icon="graph"
              only-icon
              size="normal"
              type="secondary-text"
            />
            <BaseButton
              v-if="overview.settings?.lockingEnabled && !overview.currentCategory?.locked"
              :label="t('Locked')"
              icon="lock"
              only-icon
              size="normal"
              type="danger-text"
              @click="toggleCategoryLock(overview.currentCategory)"
            />
            <BaseButton
              v-else-if="overview.settings?.lockingEnabled && overview.currentCategory?.locked && overview.canUnlock"
              :label="t('Unlock')"
              icon="unlock"
              only-icon
              size="normal"
              type="secondary-text"
              @click="toggleCategoryLock(overview.currentCategory)"
            />
          </template>
        </BaseToolbar>

        <BaseToolbar v-else-if="canViewAll">
          <template #start>
            <BaseButton
              :label="t('List view')"
              :route="buildReportRoute('GradebookFlatView')"
              icon="view-table"
              only-icon
              size="normal"
              type="primary-text"
            />
            <BaseButton
              :label="t('Students list report')"
              :route="buildReportRoute('GradebookStudentsReport')"
              icon="account"
              only-icon
              size="normal"
              type="primary-text"
            />
          </template>

          <template #end>
            <BaseButton
              v-if="overview.currentCategory?.generateCertificates"
              :label="t('Certificate')"
              :route="buildReportRoute('GradebookCertificates')"
              icon="gradebook"
              only-icon
              size="normal"
              type="primary-text"
            />
          </template>
        </BaseToolbar>

        <div
          v-else
          class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm"
        >
          <BaseButton
            v-if="!overview.settings?.hidePdfReportButton"
            :label="t('Report')"
            :to-url="gradebookService.buildExportUrl('learner', 'pdf', getContextParams())"
            icon="file-pdf"
            only-icon
            size="small"
            type="primary-text"
          />
          <BaseButton
            v-if="overview.currentCategory?.generateCertificates"
            :label="t('Certificate')"
            :route="buildReportRoute('GradebookCertificates')"
            icon="gradebook"
            only-icon
            size="small"
            type="primary-text"
          />
          <BaseButton
            :label="t('Details')"
            :route="buildLearnerReportRoute()"
            icon="eye-on"
            only-icon
            size="small"
            type="primary-text"
          />
        </div>

        <div
          v-if="canViewAll"
          class="rounded border border-info/30 bg-support-1 px-4 py-4 text-support-4"
          role="status"
        >
          <div class="flex flex-wrap items-center gap-1 text-sm">
            <strong>{{ t("Total weight") }} : {{ formatNumber(overview.currentCategory?.weight) }}</strong>
            <span aria-hidden="true">-</span>
            <span>
              {{ t("Minimum certification score") }} :
              {{ formatNumber(overview.currentCategory?.certificateMinScore ?? 0) }}
            </span>
            <BaseButton
              v-if="canManage"
              :disabled="currentCategoryLockedForTeacher"
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="primary-text"
              @click="startEditCategory(overview.currentCategory)"
            />
          </div>
          <p
            v-if="currentCategoryDescription"
            class="mt-2 whitespace-pre-line text-sm"
          >
            <strong>{{ t("Description") }}:</strong>
            {{ currentCategoryDescription }}
          </p>
        </div>

        <div
          v-else
          class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
        >
          <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <BaseIcon
                  icon="gradebook"
                  size="normal"
                />
                <h1 class="break-words text-xl font-semibold text-gray-90">
                  {{ currentCategoryTitle }}
                </h1>
              </div>
              <p
                v-if="currentCategoryDescription"
                class="mt-2 whitespace-pre-line text-sm text-gray-600"
              >
                {{ currentCategoryDescription }}
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
              <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
                {{ t("Weight") }}: {{ formatNumber(overview.currentCategory?.weight) }}
              </span>
              <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
                {{ calculationModeLabel(overview.currentCategory?.calculationMode) }}
              </span>
              <span
                v-if="overview.scoreSummary?.score !== null && overview.scoreSummary?.score !== undefined"
                class="rounded-full bg-blue-100 px-2 py-1 text-blue-700"
              >
                {{ t("Score") }}: {{ formatNumber(overview.scoreSummary.score) }}
                <template v-if="overview.scoreSummary.maxScore !== null">
                  / {{ formatNumber(overview.scoreSummary.maxScore) }}
                </template>
                <template v-if="overview.scoreSummary.percentage !== null">
                  ({{ formatNumber(overview.scoreSummary.percentage) }}%)
                </template>
              </span>
            </div>
          </div>
        </div>

        <div
          v-if="categoryTrail.length > 1"
          class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-20 bg-white px-4 py-3 text-sm shadow-sm"
        >
          <BaseButton
            :label="t('Back')"
            :route="buildCategoryRoute(parentCategoryId)"
            icon="back"
            only-icon
            size="small"
            type="primary-text"
          />

          <template
            v-for="(category, index) in categoryTrail"
            :key="category.id"
          >
            <span
              v-if="index > 0"
              class="text-gray-400"
              aria-hidden="true"
            >
              /
            </span>
            <RouterLink
              v-if="index < categoryTrail.length - 1"
              :to="buildCategoryRoute(category.id)"
              class="font-medium text-primary hover:underline"
            >
              {{ categoryDisplayLabel(category) }}
            </RouterLink>
            <span
              v-else
              class="font-semibold text-gray-800"
            >
              {{ categoryDisplayLabel(category) }}
            </span>
          </template>
        </div>

        <div
          v-if="tableHiddenForLearner"
          class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
        >
          {{ t("No data available") }}
        </div>

        <BaseTable
          v-else
          v-model:selected-items="selectedItems"
          class="gradebook-overview-table"
          :is-loading="isLoading"
          :text-for-empty="t('No data available')"
          :total-items="items.length"
          :values="items"
          data-key="rowKey"
        >
          <Column
            v-if="canManage"
            selection-mode="multiple"
            header-style="width: 2.5rem"
          />

          <Column
            :header="t('Type')"
            header-style="width: 3rem"
          >
            <template #body="{ data }">
              <BaseIcon
                :icon="itemIcon(data)"
                size="small"
                :tooltip="itemTypeLabel(data)"
              />
            </template>
          </Column>

          <Column :header="t('Name')">
            <template #body="{ data }">
              <div class="flex min-w-0 flex-wrap items-center gap-2">
                <RouterLink
                  v-if="data.kind === 'category'"
                  :to="buildCategoryRoute(data.id)"
                  :class="[
                    'break-words font-semibold hover:underline',
                    data.visible ? 'text-primary' : 'text-gray-500',
                  ]"
                >
                  {{ data.title }}
                </RouterLink>
                <RouterLink
                  v-else-if="data.kind === 'evaluation' && canViewAll"
                  :to="buildEvaluationResultsRoute(data)"
                  :class="[
                    'break-words font-semibold hover:underline',
                    data.visible ? 'text-primary' : 'text-gray-500',
                  ]"
                >
                  {{ data.title }}
                </RouterLink>
                <RouterLink
                  v-else-if="data.kind === 'link' && data.url"
                  :to="data.url"
                  :class="[
                    'break-words font-semibold hover:underline',
                    data.visible ? 'text-primary' : 'text-gray-500',
                  ]"
                >
                  {{ data.title }}
                </RouterLink>
                <span
                  v-else
                  :class="[
                    'break-words font-semibold',
                    data.visible ? 'text-gray-90' : 'text-gray-500',
                  ]"
                >
                  {{ data.title }}
                </span>
                <BaseTag
                  v-if="itemBadgeLabel(data)"
                  :label="itemBadgeLabel(data)"
                  type="info"
                />
              </div>
            </template>
          </Column>

          <Column :header="t('Description')">
            <template #body="{ data }">
              <span
                v-if="data.description"
                :class="[
                  'whitespace-pre-line text-sm',
                  data.visible ? 'text-gray-700' : 'text-gray-500',
                ]"
              >
                {{ data.description }}
              </span>
              <span v-else>-</span>
            </template>
          </Column>

          <Column
            :header="t('Weight')"
            field="weight"
            header-style="width: 8rem"
          >
            <template #body="{ data }">
              {{ formatNumber(data.weight) }}
            </template>
          </Column>

          <Column
            v-if="!canViewAll"
            :header="t('Score')"
          >
            <template #body="{ data }">
              <span v-if="data.score !== null && data.score !== undefined">
                {{ formatNumber(data.score) }}<template v-if="data.maxScore !== null"> / {{ formatNumber(data.maxScore) }}</template>
                <template v-if="data.percentage !== null && data.percentage !== undefined">
                  ({{ formatNumber(data.percentage) }}%)
                </template>
              </span>
              <span v-else>-</span>
            </template>
          </Column>

          <Column
            v-if="showDetailedStats"
            :header="t('Ranking')"
          >
            <template #body="{ data }">
              <span v-if="data.stats?.ranking">
                {{ data.stats.ranking.position }} / {{ data.stats.ranking.total }}
              </span>
              <span v-else>-</span>
            </template>
          </Column>

          <Column
            v-if="showDetailedStats"
            :header="t('Best score')"
          >
            <template #body="{ data }">
              {{ formatDetailedStat(data.stats?.best) }}
            </template>
          </Column>

          <Column
            v-if="showDetailedStats"
            :header="t('Average')"
          >
            <template #body="{ data }">
              {{ formatDetailedStat(data.stats?.average) }}
            </template>
          </Column>

          <Column
            v-if="canManage"
            :header="t('Edit')"
            class="w-52"
          >
            <template #body="{ data }">
              <div
                v-if="data.kind === 'category'"
                class="flex justify-end gap-1"
              >
                <BaseButton
                  :disabled="categoryLockedForTeacher(data)"
                  :label="t('Edit')"
                  icon="edit"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="startEditCategory(data)"
                />
                <BaseButton
                  :disabled="categoryLockedForTeacher(data)"
                  :label="data.visible ? t('Hide') : t('Show')"
                  :icon="data.visible ? 'eye-off' : 'eye-on'"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="toggleCategoryVisibility(data)"
                />
                <BaseButton
                  :disabled="categoryLockedForTeacher(data)"
                  :label="t('Move')"
                  icon="folder-move"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="startMoveCategory(data)"
                />
                <BaseButton
                  v-if="overview.settings?.lockingEnabled && !data.locked"
                  :label="t('Locked')"
                  icon="lock"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="toggleCategoryLock(data)"
                />
                <BaseButton
                  v-else-if="overview.settings?.lockingEnabled && data.locked && overview.canUnlock"
                  :label="t('Unlock')"
                  icon="unlock"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="toggleCategoryLock(data)"
                />
                <BaseButton
                  :disabled="categoryLockedForTeacher(data)"
                  :label="t('Delete')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDeleteCategory(data)"
                />
              </div>

              <div
                v-else-if="data.kind === 'evaluation'"
                class="flex justify-end gap-1"
              >
                <BaseButton
                  :label="t('Grade learners')"
                  :route="buildEvaluationResultsRoute(data)"
                  icon="account-check"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  :disabled="evaluationLockedForTeacher(data)"
                  :label="t('Edit')"
                  icon="edit"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="startEditEvaluation(data)"
                />
                <BaseButton
                  :disabled="evaluationLockedForTeacher(data)"
                  :label="data.visible ? t('Hide') : t('Show')"
                  :icon="data.visible ? 'eye-off' : 'eye-on'"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="toggleEvaluationVisibility(data)"
                />
                <BaseButton
                  :disabled="evaluationLockedForTeacher(data)"
                  :label="t('Move')"
                  icon="folder-move"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="startMoveEvaluation(data)"
                />
                <BaseButton
                  v-if="overview.settings?.lockingEnabled && !data.locked"
                  :label="t('Locked')"
                  icon="lock"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="toggleEvaluationLock(data)"
                />
                <BaseButton
                  v-else-if="overview.settings?.lockingEnabled && data.locked && overview.canUnlock"
                  :label="t('Unlock')"
                  icon="unlock"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="toggleEvaluationLock(data)"
                />
                <BaseButton
                  :label="t('Assessment history')"
                  :route="buildHistoryRoute('evaluation', data.id)"
                  icon="information"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  :disabled="evaluationLockedForTeacher(data)"
                  :label="t('Delete')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDeleteEvaluation(data)"
                />
              </div>

              <div
                v-else-if="data.kind === 'link'"
                class="flex justify-end gap-1"
              >
                <BaseButton
                  :disabled="linkLockedForTeacher(data) || !data.valid"
                  :label="t('Edit')"
                  icon="edit"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="startEditLink(data)"
                />
                <BaseButton
                  :disabled="linkLockedForTeacher(data)"
                  :label="data.visible ? t('Hide') : t('Show')"
                  :icon="data.visible ? 'eye-off' : 'eye-on'"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="toggleLinkVisibility(data)"
                />
                <BaseButton
                  :disabled="linkLockedForTeacher(data)"
                  :label="t('Move')"
                  icon="folder-move"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="startMoveLink(data)"
                />
                <BaseButton
                  :label="t('Assessment history')"
                  :route="buildHistoryRoute('link', data.id)"
                  icon="information"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  :disabled="linkLockedForTeacher(data)"
                  :label="t('Delete')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDeleteLink(data)"
                />
              </div>
            </template>
          </Column>
        </BaseTable>

        <div
          v-if="canManage && items.length > 0"
          class="flex flex-wrap items-center gap-2 rounded-b border border-t-0 border-gray-20 bg-white px-2 py-2"
        >
          <BaseButton
            :label="t('Select all')"
            icon="select-all"
            size="small"
            type="plain"
            @click="selectAllItems"
          />
          <BaseButton
            :disabled="selectedItems.length === 0"
            :label="t('Deselect all')"
            icon="unselect-all"
            size="small"
            type="plain"
            @click="deselectAllItems"
          />
          <BaseSelect
            id="gradebook-bulk-action"
            v-model="bulkAction"
            class="!mb-0 min-w-48"
            :label="t('Action')"
            name="gradebookBulkAction"
            :options="bulkActionOptions"
            option-label="label"
            option-value="value"
          />
          <BaseButton
            :disabled="!canApplyBulkAction"
            :is-loading="isRunningBulkAction"
            :label="t('Apply')"
            icon="check"
            size="small"
            type="primary"
            @click="confirmBulkAction"
          />
        </div>
      </template>
    </template>

    <BaseDialog
      v-model:is-visible="isCategoryDialogVisible"
      :title="editingCategoryId ? t('Edit category') : t('Add a category')"
      header-icon="folder-plus"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="saveCategory"
      >
        <BaseInputText
          v-if="!isEditingRootCategory"
          id="gradebook-category-title"
          v-model="categoryForm.title"
          :error-text="t('Category name')"
          :form-submitted="categoryFormSubmitted"
          :is-invalid="categoryFormSubmitted && !categoryForm.title.trim()"
          :label="t('Category name')"
          maxlength="50"
          name="gradebook_category_title"
          required
        />

        <BaseTextArea
          id="gradebook-category-description"
          v-model="categoryForm.description"
          :label="t('Description')"
          name="gradebook_category_description"
          rows="5"
        />

        <div class="grid gap-4 md:grid-cols-2">
          <BaseInputNumber
            id="gradebook-category-weight"
            v-model="categoryForm.weight"
            :label="t('Total weight')"
            :min="0"
            name="gradebook_category_weight"
          />
          <BaseSelect
            id="gradebook-category-calculation-mode"
            v-model="categoryForm.calculationMode"
            :label="t('Calculation mode')"
            name="gradebook_category_calculation_mode"
            :options="calculationModeOptions"
            option-label="label"
            option-value="value"
          />
        </div>

        <BaseSelect
          v-if="showGradeModelField"
          id="gradebook-category-grade-model"
          v-model="categoryForm.gradeModelId"
          :disabled="!advancedSettings?.canChangeGradeModel"
          :is-loading="isLoadingAdvancedSettings"
          :label="t('Grading model')"
          name="gradebook_category_grade_model"
          :options="gradeModelOptions"
          option-label="label"
          option-value="value"
        />

        <div
          v-if="advancedSettings?.canManageSkills"
          class="field"
        >
          <FloatLabel variant="on">
            <MultiSelect
              v-model="categoryForm.skillIds"
              display="chip"
              filter
              fluid
              input-id="gradebook-category-skills"
              :loading="isLoadingAdvancedSettings"
              name="gradebook_category_skills"
              :options="advancedSkillOptions"
              option-label="label"
              option-value="value"
              @filter="searchSkills"
            />
            <label for="gradebook-category-skills">{{ t("Skills") }}</label>
          </FloatLabel>
          <p class="mt-1 text-xs text-gray-500">
            {{ t("Skills obtained when achieving this assessment") }}
          </p>
        </div>

        <BaseCheckbox
          v-if="!isEditingRootCategory"
          id="gradebook-category-visible"
          v-model="categoryForm.visible"
          :label="t('Visible')"
          name="gradebook_category_visible"
        />

        <BaseInputNumber
          v-if="showCertificateMinimumField"
          id="gradebook-category-certificate-min-score"
          v-model="categoryForm.certificateMinScore"
          :label="certificateMinimumLabel"
          :min="0"
          name="gradebook_category_certificate_min_score"
        />

        <BaseCheckbox
          v-if="isEditingRootCategory && canChangeGradeModelSettings"
          id="gradebook-category-generate-certificates"
          v-model="categoryForm.generateCertificates"
          :label="t('Generate certificates')"
          name="gradebook_category_generate_certificates"
        />

        <BaseCheckbox
          id="gradebook-category-requirement"
          v-model="categoryForm.isRequirement"
          :label="t('Is requirement')"
          name="gradebook_category_requirement"
        />

        <BaseCheckbox
          v-if="isEditingRootCategory && overview.settings?.allowSubcategorySkills"
          id="gradebook-category-allow-subcategory-skills"
          v-model="categoryForm.allowSkillsBySubcategory"
          :label="t('Allow skills by subcategories')"
          name="gradebook_category_allow_subcategory_skills"
        />
      </form>

      <template #footer>
        <BaseButton
          :disabled="isSavingCategory"
          :is-loading="isSavingCategory"
          :label="editingCategoryId ? t('Save') : t('Create category')"
          icon="save"
          type="success"
          @click="saveCategory"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isMoveDialogVisible"
      :title="t('Move')"
      header-icon="folder-move"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="moveCategory"
      >
        <BaseSelect
          id="gradebook-category-move-target"
          v-model="moveTargetCategoryId"
          :label="t('Select a category')"
          name="gradebook_category_move_target"
          :options="moveTargetOptions"
          option-label="label"
          option-value="value"
        />
      </form>
      <template #footer>
        <BaseButton
          :disabled="!moveTargetCategoryId || isSavingCategory"
          :is-loading="isSavingCategory"
          :label="t('Move')"
          icon="folder-move"
          type="secondary"
          @click="moveCategory"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isEvaluationDialogVisible"
      :title="editingEvaluationId ? t('Edit') : t('Add classroom activity')"
      header-icon="gradebook"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="saveEvaluation"
      >
        <BaseInputText
          id="gradebook-evaluation-title"
          v-model="evaluationForm.title"
          :form-submitted="evaluationFormSubmitted"
          :is-invalid="evaluationFormSubmitted && !evaluationForm.title.trim()"
          :label="t('Assessment')"
          maxlength="50"
          name="gradebook_evaluation_title"
          required
        />

        <BaseSelect
          v-if="showActivityCategorySelector"
          id="gradebook-evaluation-category"
          v-model="evaluationForm.categoryId"
          :label="t('Select a category')"
          name="gradebook_evaluation_category"
          :options="evaluationCategoryOptions"
          option-label="label"
          option-value="value"
        />

        <div class="grid gap-4 md:grid-cols-3">
          <BaseInputNumber
            id="gradebook-evaluation-weight"
            v-model="evaluationForm.weight"
            :label="t('Weight')"
            :min="0"
            name="gradebook_evaluation_weight"
          />
          <BaseInputNumber
            id="gradebook-evaluation-max-score"
            v-model="evaluationForm.maxScore"
            :disabled="evaluationMaximumScoreLocked"
            :label="t('Maximum score')"
            :min="0"
            name="gradebook_evaluation_max_score"
          />
          <BaseInputNumber
            id="gradebook-evaluation-min-score"
            v-model="evaluationForm.minScore"
            :label="t('Minimum score')"
            :min="0"
            name="gradebook_evaluation_min_score"
          />
        </div>

        <BaseTextArea
          id="gradebook-evaluation-description"
          v-model="evaluationForm.description"
          :label="t('Description')"
          name="gradebook_evaluation_description"
          rows="5"
        />

        <BaseCheckbox
          v-if="!editingEvaluationId"
          id="gradebook-evaluation-grade-learners"
          v-model="evaluationForm.gradeLearners"
          :label="t('Grade learners')"
          name="gradebook_evaluation_grade_learners"
        />
      </form>

      <template #footer>
        <BaseButton
          :disabled="isSavingEvaluation"
          :is-loading="isSavingEvaluation"
          :label="editingEvaluationId ? t('Save') : t('Add classroom activity')"
          icon="save"
          type="success"
          @click="saveEvaluation"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isMoveEvaluationDialogVisible"
      :title="t('Move')"
      header-icon="folder-move"
    >
      <BaseSelect
        id="gradebook-evaluation-move-target"
        v-model="moveEvaluationTargetCategoryId"
        :label="t('Select a category')"
        name="gradebook_evaluation_move_target"
        :options="evaluationCategoryOptions"
        option-label="label"
        option-value="value"
      />
      <template #footer>
        <BaseButton
          :disabled="!moveEvaluationTargetCategoryId || isSavingEvaluation"
          :is-loading="isSavingEvaluation"
          :label="t('Move')"
          icon="folder-move"
          type="secondary"
          @click="moveEvaluation"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isLinkDialogVisible"
      :title="editingLinkId ? t('Edit') : t('Add online activity')"
      header-icon="link-add"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="saveLink"
      >
        <BaseSelect
          id="gradebook-link-type"
          v-model="linkForm.type"
          :disabled="Boolean(editingLinkId) || isLoadingLinkOptions"
          :label="t('Type')"
          name="gradebook_link_type"
          :options="linkTypeOptions"
          option-label="label"
          option-value="value"
        />

        <BaseSelect
          id="gradebook-link-resource"
          v-model="linkForm.refId"
          :disabled="Boolean(editingLinkId) || !linkForm.type || isLoadingLinkOptions"
          :label="t('Select an option')"
          name="gradebook_link_resource"
          :options="linkResourceOptions"
          option-label="label"
          option-value="value"
        />

        <BaseSelect
          v-if="showActivityCategorySelector"
          id="gradebook-link-category"
          v-model="linkForm.categoryId"
          :label="t('Select a category')"
          name="gradebook_link_category"
          :options="evaluationCategoryOptions"
          option-label="label"
          option-value="value"
        />

        <div
          v-if="linkUsesParticipationPoints"
          class="grid gap-4 md:grid-cols-2"
        >
          <BaseInputNumber
            id="gradebook-link-points-one"
            v-model="linkForm.pointsOne"
            :label="t('Points for one message')"
            :min="0"
            name="gradebook_link_points_one"
          />
          <BaseInputNumber
            id="gradebook-link-points-many"
            v-model="linkForm.pointsMany"
            :label="t('Points for two or more messages')"
            :min="0"
            name="gradebook_link_points_many"
          />
        </div>

        <div
          v-else
          class="grid gap-4 md:grid-cols-2"
        >
          <BaseInputNumber
            id="gradebook-link-weight"
            v-model="linkForm.weight"
            :label="t('Weight')"
            :min="0"
            name="gradebook_link_weight"
          />
          <BaseInputNumber
            id="gradebook-link-min-score"
            v-model="linkForm.minScore"
            :label="t('Minimum score')"
            :min="0"
            name="gradebook_link_min_score"
          />
        </div>

        <BaseInputNumber
          v-if="linkUsesParticipationPoints"
          id="gradebook-link-min-score-participation"
          v-model="linkForm.minScore"
          :label="t('Minimum score')"
          :min="0"
          name="gradebook_link_min_score"
        />
      </form>

      <template #footer>
        <BaseButton
          :disabled="!canSaveLink || isSavingLink"
          :is-loading="isSavingLink"
          :label="editingLinkId ? t('Save') : t('Add online activity')"
          icon="save"
          type="success"
          @click="saveLink"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isMoveLinkDialogVisible"
      :title="t('Move')"
      header-icon="folder-move"
    >
      <BaseSelect
        id="gradebook-link-move-target"
        v-model="moveLinkTargetCategoryId"
        :label="t('Select a category')"
        name="gradebook_link_move_target"
        :options="evaluationCategoryOptions"
        option-label="label"
        option-value="value"
      />
      <template #footer>
        <BaseButton
          :disabled="!moveLinkTargetCategoryId || isSavingLink"
          :is-loading="isSavingLink"
          :label="t('Move')"
          icon="folder-move"
          type="secondary"
          @click="moveLink"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import FloatLabel from "primevue/floatlabel"
import MultiSelect from "primevue/multiselect"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTag from "../../components/basecomponents/BaseTag.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import gradebookService from "../../services/gradebookService"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const overview = ref(null)
const isLoading = ref(false)
const isInitializing = ref(false)
const isSavingCategory = ref(false)
const isLoadingAdvancedSettings = ref(false)
const advancedSettings = ref(null)
const advancedSkillOptions = ref([])
const errorMessage = ref("")
const infoMessage = ref("")
const selectedItems = ref([])
const bulkAction = ref("")
const isRunningBulkAction = ref(false)
const isCategoryDialogVisible = ref(false)
const isMoveDialogVisible = ref(false)
const editingCategoryId = ref(null)
const movingCategory = ref(null)
const moveTargetCategoryId = ref(null)
const categoryFormSubmitted = ref(false)
const isEvaluationDialogVisible = ref(false)
const isMoveEvaluationDialogVisible = ref(false)
const isSavingEvaluation = ref(false)
const editingEvaluationId = ref(null)
const movingEvaluation = ref(null)
const moveEvaluationTargetCategoryId = ref(null)
const evaluationFormSubmitted = ref(false)
const isLinkDialogVisible = ref(false)
const isMoveLinkDialogVisible = ref(false)
const isSavingLink = ref(false)
const isLoadingLinkOptions = ref(false)
const editingLinkId = ref(null)
const movingLink = ref(null)
const moveLinkTargetCategoryId = ref(null)
const linkOptions = ref(null)
const syncedAchievementCategories = new Set()

const categoryForm = reactive({
  title: "",
  description: "",
  weight: 100,
  calculationMode: "weighted_average",
  visible: true,
  certificateMinScore: 0,
  generateCertificates: false,
  isRequirement: false,
  allowSkillsBySubcategory: true,
  gradeModelId: -1,
  skillIds: [],
})

const evaluationForm = reactive({
  title: "",
  description: "",
  categoryId: null,
  weight: 0,
  maxScore: 100,
  minScore: null,
  hasResults: false,
  gradeLearners: false,
})

const linkForm = reactive({
  type: null,
  refId: null,
  categoryId: null,
  weight: 0,
  minScore: 0,
  pointsOne: 0,
  pointsMany: null,
})

const calculationModeOptions = computed(() => [
  { label: t("Weighted average"), value: "weighted_average" },
  { label: t("Points sum"), value: "points_sum" },
])

const items = computed(() => {
  const values = Array.isArray(overview.value?.items) ? overview.value.items : []

  return values.map((item) => ({
    ...item,
    rowKey: `${item.kind}-${item.id}`,
  }))
})
const bulkActionOptions = computed(() => [
  { label: t("Select an action"), value: "" },
  { label: t("Set visible"), value: "setvisible" },
  { label: t("Set invisible"), value: "setinvisible" },
  { label: t("Delete"), value: "deleted" },
])
const canApplyBulkAction = computed(
  () => selectedItems.value.length > 0 && "" !== bulkAction.value && !isRunningBulkAction.value,
)
const categoryTrail = computed(() => (Array.isArray(overview.value?.categoryTrail) ? overview.value.categoryTrail : []))
const categoryOptions = computed(() =>
  Array.isArray(overview.value?.categoryOptions) ? overview.value.categoryOptions : [],
)
const canManage = computed(() => true === overview.value?.canManage)
const canViewAll = computed(() => true === overview.value?.canViewAll)
const showDetailedStats = computed(
  () =>
    !canViewAll.value &&
    true === overview.value?.settings?.detailedAdminView &&
    (overview.value?.settings?.scoreModelMax === null || overview.value?.settings?.scoreModelMax === undefined),
)
const currentCategoryTitle = computed(() => {
  const currentCategory = overview.value?.currentCategory
  if (!currentCategory?.parentId) {
    return t("Assessments")
  }

  return currentCategory.title || t("Assessments")
})
const currentCategoryDescription = computed(() => overview.value?.currentCategory?.description || "")
const parentCategoryId = computed(() => {
  const parentId = Number(overview.value?.currentCategory?.parentId || 0)

  return parentId > 0 ? parentId : Number(overview.value?.rootCategoryId || 0)
})
const tableHiddenForLearner = computed(() => !canViewAll.value && true === overview.value?.settings?.hideTable)
const isEditingRootCategory = computed(
  () => Number(editingCategoryId.value || 0) === Number(overview.value?.rootCategoryId || 0),
)
const currentCategoryLockedForTeacher = computed(() => categoryLockedForTeacher(overview.value?.currentCategory))
const showSubcategorySkillMinimumField = computed(
  () =>
    !isEditingRootCategory.value &&
    true === advancedSettings.value?.allowSubcategorySkillsSetting &&
    true === advancedSettings.value?.parentAllowsSkillsBySubcategory,
)
const showCertificateMinimumField = computed(
  () => isEditingRootCategory.value || showSubcategorySkillMinimumField.value,
)
const certificateMinimumLabel = computed(() =>
  isEditingRootCategory.value ? t("Minimum certification score") : t("Minimum score for skills"),
)
const canChangeGradeModelSettings = computed(
  () => true === overview.value?.canUnlock || true === overview.value?.settings?.teachersCanChangeGradeModelSettings,
)
const showGradeModelField = computed(
  () =>
    isEditingRootCategory.value &&
    true === advancedSettings.value?.gradeModelEnabled &&
    canChangeGradeModelSettings.value,
)
const gradeModelOptions = computed(() => [
  { label: t("None"), value: -1 },
  ...(Array.isArray(advancedSettings.value?.gradeModels) ? advancedSettings.value.gradeModels : []).map((model) => ({
    label: model.title,
    value: Number(model.id),
  })),
])
const canAddCategory = computed(() => true !== overview.value?.currentCategory?.hasGradeModel)
const canShowWeightReport = computed(() => !items.value.some((item) => item.kind === "category"))
const canShowScoringSettings = computed(
  () => true === overview.value?.settings?.teachersCanChangeScoreSettings && true === overview.value?.settings?.scoreDisplayCustom,
)
const evaluationCategoryOptions = computed(() =>
  categoryOptions.value
    .filter((category) => !category.hasGradeModel)
    .map((category) => ({
      label: categoryDisplayLabel(category, true),
      value: Number(category.id),
    })),
)
const showActivityCategorySelector = computed(() => evaluationCategoryOptions.value.length > 1)
const evaluationMaximumScoreLocked = computed(() => {
  const scoreModelMax = overview.value?.settings?.scoreModelMax
  const hasScoreModel =
    scoreModelMax !== null && scoreModelMax !== undefined && scoreModelMax !== "" && Number.isFinite(Number(scoreModelMax))

  return hasScoreModel || true === evaluationForm.hasResults
})

const linkTypes = computed(() => (Array.isArray(linkOptions.value?.types) ? linkOptions.value.types : []))
const linkTypeOptions = computed(() =>
  linkTypes.value
    .filter((type) => true === type.available)
    .map((type) => ({
      label: t(type.label || "Type"),
      value: Number(type.type),
    })),
)
const selectedLinkType = computed(() =>
  linkTypes.value.find((type) => Number(type.type) === Number(linkForm.type || 0)) || null,
)
const linkResourceOptions = computed(() =>
  (Array.isArray(selectedLinkType.value?.items) ? selectedLinkType.value.items : []).map((item) => ({
    label: item.title,
    value: Number(item.id),
  })),
)
const linkUsesParticipationPoints = computed(() => true === selectedLinkType.value?.usesParticipationPoints)
const canSaveLink = computed(() => {
  if (!linkForm.type || !linkForm.refId || !linkForm.categoryId) {
    return false
  }
  if (linkForm.minScore === null || linkForm.minScore === undefined || Number(linkForm.minScore) < 0) {
    return false
  }
  if (linkUsesParticipationPoints.value) {
    return linkForm.pointsOne !== null && linkForm.pointsOne !== undefined && Number(linkForm.pointsOne) >= 0
  }

  return linkForm.weight !== null && linkForm.weight !== undefined && Number(linkForm.weight) >= 0
})
const moveTargetOptions = computed(() => {
  if (!movingCategory.value) {
    return []
  }

  const invalidIds = getCategoryDescendantIds(Number(movingCategory.value.id))
  invalidIds.add(Number(movingCategory.value.id))

  return categoryOptions.value
    .filter((category) => !invalidIds.has(Number(category.id)))
    .map((category) => ({
      label: categoryDisplayLabel(category, true),
      value: Number(category.id),
    }))
})

function categoryDisplayLabel(category, includeDepth = false) {
  const categoryId = Number(category?.id || 0)
  const rootCategoryId = Number(overview.value?.rootCategoryId || 0)
  const label = categoryId > 0 && categoryId === rootCategoryId ? t("Assessments") : category?.title || t("Assessments")

  if (!includeDepth) {
    return label
  }

  return `${"—".repeat(Math.max(0, Number(category?.depth || 0)))} ${label}`.trim()
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams(includeCategory = true) {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
  }


  if (includeCategory) {
    const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
    if (categoryId > 0) {
      params.categoryId = categoryId
    }
  }

  return params
}

function buildCategoryRoute(categoryId) {
  const query = { ...route.query }
  const normalizedCategoryId = Number(categoryId || 0)
  const rootCategoryId = Number(overview.value?.rootCategoryId || 0)

  if (normalizedCategoryId > 0 && normalizedCategoryId !== rootCategoryId) {
    query.categoryId = normalizedCategoryId
  } else {
    delete query.categoryId
  }

  return {
    name: "GradebookList",
    params: { node: route.params.node },
    query,
  }
}

function buildReportRoute(name) {
  const query = { ...route.query }
  const currentCategoryId = Number(overview.value?.currentCategory?.id || 0)
  const rootCategoryId = Number(overview.value?.rootCategoryId || 0)

  if (currentCategoryId > 0 && currentCategoryId !== rootCategoryId) {
    query.categoryId = currentCategoryId
  } else {
    delete query.categoryId
  }

  return {
    name,
    params: { node: route.params.node },
    query,
  }
}

function buildLearnerReportRoute(userId = null) {
  const params = { node: route.params.node }
  if (Number(userId || 0) > 0) {
    params.userId = Number(userId)
  }

  const query = { ...route.query }
  const currentCategoryId = Number(overview.value?.currentCategory?.id || 0)
  const rootCategoryId = Number(overview.value?.rootCategoryId || 0)
  if (currentCategoryId > 0 && currentCategoryId !== rootCategoryId) {
    query.categoryId = currentCategoryId
  } else {
    delete query.categoryId
  }

  return {
    name: "GradebookLearnerReport",
    params,
    query,
  }
}

function buildEvaluationResultsRoute(evaluation) {
  return {
    name: "GradebookEvaluationResults",
    params: {
      node: route.params.node,
      evaluationId: Number(evaluation?.id || 0),
    },
    query: { ...route.query },
  }
}

function buildHistoryRoute(kind, itemId) {
  return {
    name: "GradebookHistory",
    params: {
      node: route.params.node,
      kind,
      itemId: Number(itemId || 0),
    },
    query: { ...route.query },
  }
}

function itemIcon(item) {
  if (item.kind === "category") {
    return "folder-generic"
  }

  if (item.kind === "link") {
    return item.icon || "link"
  }

  return "gradebook"
}

function itemTypeLabel(item) {
  if (item.kind === "category") {
    return t("Category")
  }

  if (item.kind === "link") {
    return item.linkTypeLabel ? t(item.linkTypeLabel) : `${t("Type")} ${item.linkType}`
  }

  return t("Assessment")
}

function itemBadgeLabel(item) {
  if (item.kind === "link") {
    return item.linkTypeLabel ? t(item.linkTypeLabel) : ""
  }

  if (item.kind === "evaluation") {
    return t("Score")
  }

  return ""
}

function calculationModeLabel(mode) {
  if (mode === "weighted_average") {
    return t("Weighted average")
  }

  if (mode === "points_sum") {
    return t("Points sum")
  }

  return mode || "-"
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return "-"
  }

  const decimals = Math.max(0, Number(overview.value?.settings?.numberDecimals || 0))

  return number.toFixed(decimals)
}

function formatDetailedStat(result) {
  if (!result || result.hasResult !== true) {
    return "-"
  }

  const score = result.score === null || result.score === undefined ? "-" : formatNumber(result.score)
  const maxScore = result.maxScore === null || result.maxScore === undefined ? null : formatNumber(result.maxScore)
  let value = maxScore === null ? score : `${score} / ${maxScore}`

  if (
    overview.value?.settings?.hidePercentageUserResult !== true &&
    result.percentage !== null &&
    result.percentage !== undefined
  ) {
    value += ` (${formatNumber(result.percentage)}%)`
  }

  return value
}

function extractErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
}

function categoryLockedForTeacher(category) {
  return true === category?.locked && true !== overview.value?.canUnlock
}

function evaluationLockedForTeacher(evaluation) {
  return true === evaluation?.locked && true !== overview.value?.canUnlock
}

function linkLockedForTeacher(link) {
  return true === link?.locked && true !== overview.value?.canUnlock
}


let skillSearchTimer = null

function mergeAdvancedSkillOptions(skills) {
  const options = new Map(advancedSkillOptions.value.map((option) => [Number(option.value), option]))
  for (const skill of Array.isArray(skills) ? skills : []) {
    const skillId = Number(skill.id || 0)
    if (skillId <= 0) {
      continue
    }
    options.set(skillId, { label: skill.title || String(skillId), value: skillId })
  }

  advancedSkillOptions.value = Array.from(options.values()).sort((a, b) => a.label.localeCompare(b.label))
}

function getAdvancedSettingsParams(skillQuery = "") {
  const params = getContextParams(false)
  const editingId = Number(editingCategoryId.value || 0)

  if (editingId > 0) {
    params.categoryId = editingId
    const category = categoryOptions.value.find((item) => Number(item.id) === editingId)
    const parentId = Number(category?.parentId || 0)
    if (parentId > 0) {
      params.parentCategoryId = parentId
    }
  } else {
    const parentId = Number(overview.value?.currentCategory?.id || 0)
    if (parentId > 0) {
      params.parentCategoryId = parentId
    }
  }

  if (skillQuery.trim()) {
    params.skillQuery = skillQuery.trim()
  }

  return params
}

async function loadAdvancedSettings(syncForm = false, skillQuery = "") {
  if (!canManage.value) {
    return
  }

  isLoadingAdvancedSettings.value = true

  try {
    const data = await gradebookService.getAdvancedSettings(getAdvancedSettingsParams(skillQuery))
    advancedSettings.value = data
    mergeAdvancedSkillOptions(data?.skills)

    if (syncForm) {
      const selectedGradeModelId = Number(data?.gradeModelId || data?.defaultGradeModelId || -1)
      categoryForm.gradeModelId = selectedGradeModelId > 0 ? selectedGradeModelId : -1
      categoryForm.skillIds = Array.isArray(data?.skillIds) ? data.skillIds.map(Number) : []
    }
  } catch (error) {
    console.error("Error loading Gradebook advanced settings", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isLoadingAdvancedSettings.value = false
  }
}

function searchSkills(event) {
  if (skillSearchTimer) {
    clearTimeout(skillSearchTimer)
  }

  const query = String(event?.value || "")
  skillSearchTimer = setTimeout(() => loadAdvancedSettings(false, query), 250)
}

async function initializeGradebook() {
  if (isInitializing.value || !canManage.value || !overview.value?.csrfToken) {
    return
  }

  isInitializing.value = true

  try {
    await gradebookService.runCategoryAction(
      {
        action: "initialize",
        submittedCsrfToken: overview.value.csrfToken,
      },
      getContextParams(false),
    )
    await loadOverview(false)
  } catch (error) {
    console.error("Error initializing Gradebook", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isInitializing.value = false
  }
}

async function syncAchievementsIfNeeded() {
  if (!overview.value?.canSyncAchievements || !overview.value?.achievementCsrfToken || !overview.value?.hasGradebook) {
    return
  }

  const categoryId = Number(overview.value?.currentCategory?.id || overview.value?.rootCategoryId || 0)
  if (categoryId <= 0 || syncedAchievementCategories.has(categoryId)) {
    return
  }

  syncedAchievementCategories.add(categoryId)

  try {
    await gradebookService.syncAchievements(
      {
        categoryId,
        submittedCsrfToken: overview.value.achievementCsrfToken,
      },
      getContextParams(false),
    )
  } catch (error) {
    syncedAchievementCategories.delete(categoryId)
    console.error("Unable to synchronize Gradebook achievements", error)
  }
}

async function loadOverview(allowInitialize = true) {
  isLoading.value = true
  errorMessage.value = ""

  try {
    overview.value = await gradebookService.getOverview(getContextParams())
    selectedItems.value = []
    bulkAction.value = ""

    if (allowInitialize && !overview.value?.hasGradebook && true === overview.value?.canManage) {
      isLoading.value = false
      await initializeGradebook()

      return
    }

    await syncAchievementsIfNeeded()
  } catch (error) {
    console.error("Error loading Gradebook overview", error)
    overview.value = null
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

function selectAllItems() {
  selectedItems.value = [...items.value]
}

function deselectAllItems() {
  selectedItems.value = []
}

function confirmBulkAction() {
  if (!canApplyBulkAction.value) {
    return
  }

  if ("deleted" === bulkAction.value) {
    requireConfirmation({
      message: `${t("Delete all")}?`,
      accept: () => runBulkAction(),
      reject: () => {
        bulkAction.value = ""
      },
    })

    return
  }

  runBulkAction()
}

async function runBulkAction() {
  if (!canApplyBulkAction.value) {
    return
  }

  const selected = [...selectedItems.value]
  const action = bulkAction.value
  isRunningBulkAction.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    for (const item of selected) {
      await runBulkActionForItem(item, action)
    }

    infoMessage.value = "deleted" === action ? t("Deleted") : t("The visibility has been changed.")
    await loadOverview(false)
  } catch (error) {
    console.error("Error applying Gradebook bulk action", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    selectedItems.value = []
    bulkAction.value = ""
    isRunningBulkAction.value = false
  }
}

async function runBulkActionForItem(item, action) {
  const deleteItem = "deleted" === action
  const visible = "setvisible" === action

  if ("category" === item.kind) {
    if (categoryLockedForTeacher(item)) {
      return
    }

    await gradebookService.runCategoryAction(
      {
        action: deleteItem ? "delete" : "set_visibility",
        categoryId: Number(item.id),
        ...(deleteItem ? {} : { visible }),
        submittedCsrfToken: overview.value?.csrfToken || "",
      },
      getContextParams(false),
    )

    return
  }

  if ("evaluation" === item.kind) {
    if (evaluationLockedForTeacher(item)) {
      return
    }

    await gradebookService.runEvaluationAction(
      {
        action: deleteItem ? "delete" : "set_visibility",
        evaluationId: Number(item.id),
        ...(deleteItem ? {} : { visible }),
        submittedCsrfToken: overview.value?.evaluationCsrfToken || "",
      },
      getContextParams(false),
    )

    return
  }

  if ("link" === item.kind) {
    if (linkLockedForTeacher(item)) {
      return
    }

    await gradebookService.runLinkAction(
      {
        action: deleteItem ? "delete" : "set_visibility",
        linkId: Number(item.id),
        ...(deleteItem ? {} : { visible }),
        submittedCsrfToken: overview.value?.linkCsrfToken || "",
      },
      getContextParams(false),
    )
  }
}

function resetCategoryForm() {
  categoryForm.title = ""
  categoryForm.description = ""
  categoryForm.weight = Number(overview.value?.settings?.defaultWeight || 100)
  categoryForm.calculationMode = "weighted_average"
  categoryForm.visible = true === overview.value?.settings?.defaultCategoryVisible
  categoryForm.certificateMinScore = 0
  categoryForm.generateCertificates = false
  categoryForm.isRequirement = false
  categoryForm.allowSkillsBySubcategory = true
  categoryForm.gradeModelId = -1
  categoryForm.skillIds = []
  advancedSettings.value = null
  advancedSkillOptions.value = []
  categoryFormSubmitted.value = false
}

async function startCreateCategory() {
  editingCategoryId.value = null
  resetCategoryForm()
  isCategoryDialogVisible.value = true
  await loadAdvancedSettings(false)
}

async function startEditCategory(category) {
  if (!category || categoryLockedForTeacher(category)) {
    return
  }

  editingCategoryId.value = Number(category.id || 0)
  advancedSettings.value = null
  advancedSkillOptions.value = []
  categoryForm.title = category.title || ""
  categoryForm.description = category.description || ""
  categoryForm.weight = Number(category.weight || 0)
  categoryForm.calculationMode = category.calculationMode || "weighted_average"
  categoryForm.visible = true === category.visible
  categoryForm.certificateMinScore = Number(category.certificateMinScore || 0)
  categoryForm.generateCertificates = true === category.generateCertificates
  categoryForm.isRequirement = true === category.isRequirement
  categoryForm.allowSkillsBySubcategory = true === category.allowSkillsBySubcategory
  categoryForm.gradeModelId = -1
  categoryForm.skillIds = Array.isArray(category.skillIds) ? category.skillIds.map(Number) : []
  categoryFormSubmitted.value = false
  isCategoryDialogVisible.value = true
  await loadAdvancedSettings(true)
}

async function saveCategory() {
  categoryFormSubmitted.value = true
  if ((!isEditingRootCategory.value && !categoryForm.title.trim()) || isSavingCategory.value) {
    return
  }

  isSavingCategory.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const action = editingCategoryId.value ? "update" : "create"
    await gradebookService.runCategoryAction(
      {
        action,
        categoryId: editingCategoryId.value,
        parentCategoryId: Number(overview.value?.currentCategory?.id || 0),
        title: categoryForm.title,
        description: categoryForm.description,
        weight: Number(categoryForm.weight || 0),
        calculationMode: categoryForm.calculationMode,
        visible: true === categoryForm.visible,
        certificateMinScore: showCertificateMinimumField.value ? Number(categoryForm.certificateMinScore || 0) : null,
        generateCertificates:
          isEditingRootCategory.value && canChangeGradeModelSettings.value
            ? true === categoryForm.generateCertificates
            : null,
        isRequirement: true === categoryForm.isRequirement,
        allowSkillsBySubcategory: true === categoryForm.allowSkillsBySubcategory,
        gradeModelId:
          isEditingRootCategory.value && true === advancedSettings.value?.canChangeGradeModel
            ? Number(categoryForm.gradeModelId || -1)
            : null,
        skillIds:
          true === advancedSettings.value?.canManageSkills
            ? categoryForm.skillIds.map((skillId) => Number(skillId)).filter((skillId) => skillId > 0)
            : null,
        submittedCsrfToken: overview.value?.csrfToken || "",
      },
      getContextParams(false),
    )

    isCategoryDialogVisible.value = false
    infoMessage.value = t("Category saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error saving Gradebook category", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingCategory.value = false
  }
}

async function toggleCategoryVisibility(category) {
  if (categoryLockedForTeacher(category)) {
    return
  }

  try {
    await gradebookService.runCategoryAction(
      {
        action: "set_visibility",
        categoryId: Number(category.id),
        visible: !category.visible,
        submittedCsrfToken: overview.value?.csrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("Visibility of category changed")
    await loadOverview(false)
  } catch (error) {
    console.error("Error changing Gradebook category visibility", error)
    errorMessage.value = extractErrorMessage(error)
  }
}

async function toggleCategoryLock(category) {
  if (!overview.value?.settings?.lockingEnabled) {
    return
  }

  try {
    await gradebookService.runCategoryAction(
      {
        action: category.locked ? "unlock" : "lock",
        categoryId: Number(category.id),
        submittedCsrfToken: overview.value?.csrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("Category saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error changing Gradebook category lock", error)
    errorMessage.value = extractErrorMessage(error)
  }
}

function confirmDeleteCategory(category) {
  if (categoryLockedForTeacher(category)) {
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete this category?"),
    accept: () => deleteCategory(category),
  })
}

async function deleteCategory(category) {
  isSavingCategory.value = true
  errorMessage.value = ""

  try {
    await gradebookService.runCategoryAction(
      {
        action: "delete",
        categoryId: Number(category.id),
        submittedCsrfToken: overview.value?.csrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("Category deleted")
    await loadOverview(false)
  } catch (error) {
    console.error("Error deleting Gradebook category", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingCategory.value = false
  }
}

function startMoveCategory(category) {
  if (categoryLockedForTeacher(category)) {
    return
  }

  movingCategory.value = category
  moveTargetCategoryId.value = Number(category.parentId || overview.value?.rootCategoryId || 0)
  isMoveDialogVisible.value = true
}

function getCategoryDescendantIds(categoryId) {
  const descendantIds = new Set()
  let found = true

  while (found) {
    found = false
    for (const category of categoryOptions.value) {
      const id = Number(category.id)
      const parentId = Number(category.parentId || 0)
      if (!descendantIds.has(id) && (parentId === categoryId || descendantIds.has(parentId))) {
        descendantIds.add(id)
        found = true
      }
    }
  }

  return descendantIds
}

async function moveCategory() {
  if (!movingCategory.value || !moveTargetCategoryId.value || isSavingCategory.value) {
    return
  }

  isSavingCategory.value = true
  errorMessage.value = ""

  try {
    await gradebookService.runCategoryAction(
      {
        action: "move",
        categoryId: Number(movingCategory.value.id),
        targetCategoryId: Number(moveTargetCategoryId.value),
        submittedCsrfToken: overview.value?.csrfToken || "",
      },
      getContextParams(false),
    )
    isMoveDialogVisible.value = false
    movingCategory.value = null
    infoMessage.value = t("Category saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error moving Gradebook category", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingCategory.value = false
  }
}

function resetEvaluationForm() {
  evaluationForm.title = ""
  evaluationForm.description = ""
  evaluationForm.categoryId = Number(overview.value?.currentCategory?.id || 0)
  evaluationForm.weight = Number(overview.value?.settings?.defaultWeight || 100)
  evaluationForm.maxScore = Number(overview.value?.settings?.scoreModelMax ?? overview.value?.settings?.defaultWeight ?? 100)
  evaluationForm.minScore = null
  evaluationForm.hasResults = false
  evaluationForm.gradeLearners = false
  evaluationFormSubmitted.value = false
}

function startCreateEvaluation() {
  if (currentCategoryLockedForTeacher.value || true === overview.value?.currentCategory?.hasGradeModel) {
    return
  }

  editingEvaluationId.value = null
  resetEvaluationForm()
  isEvaluationDialogVisible.value = true
}

function startEditEvaluation(evaluation) {
  if (!evaluation || evaluationLockedForTeacher(evaluation)) {
    return
  }

  editingEvaluationId.value = Number(evaluation.id || 0)
  evaluationForm.title = evaluation.title || ""
  evaluationForm.description = evaluation.description || ""
  evaluationForm.categoryId = Number(overview.value?.currentCategory?.id || 0)
  evaluationForm.weight = Number(evaluation.weight || 0)
  evaluationForm.maxScore = Number(evaluation.maxScore || 0)
  evaluationForm.minScore = evaluation.minScore === null || evaluation.minScore === undefined ? null : Number(evaluation.minScore)
  evaluationForm.hasResults = true === evaluation.hasResults
  evaluationForm.gradeLearners = false
  evaluationFormSubmitted.value = false
  isEvaluationDialogVisible.value = true
}

async function saveEvaluation() {
  evaluationFormSubmitted.value = true
  if (!evaluationForm.title.trim() || !evaluationForm.categoryId || isSavingEvaluation.value) {
    return
  }

  isSavingEvaluation.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const isCreate = !editingEvaluationId.value
    const response = await gradebookService.runEvaluationAction(
      {
        action: isCreate ? "create" : "update",
        evaluationId: editingEvaluationId.value,
        categoryId: Number(evaluationForm.categoryId),
        title: evaluationForm.title,
        description: evaluationForm.description,
        weight: Number(evaluationForm.weight || 0),
        maxScore: Number(evaluationForm.maxScore || 0),
        minScore:
          evaluationForm.minScore === null || evaluationForm.minScore === undefined || evaluationForm.minScore === ""
            ? null
            : Number(evaluationForm.minScore),
        submittedCsrfToken: overview.value?.evaluationCsrfToken || "",
      },
      getContextParams(false),
    )
    isEvaluationDialogVisible.value = false

    if (isCreate && evaluationForm.gradeLearners && Number(response?.evaluationId || 0) > 0) {
      await router.push(buildEvaluationResultsRoute({ id: Number(response.evaluationId) }))

      return
    }

    infoMessage.value = t("Saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error saving Gradebook evaluation", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingEvaluation.value = false
  }
}

async function toggleEvaluationVisibility(evaluation) {
  if (evaluationLockedForTeacher(evaluation)) {
    return
  }

  try {
    await gradebookService.runEvaluationAction(
      {
        action: "set_visibility",
        evaluationId: Number(evaluation.id),
        visible: !evaluation.visible,
        submittedCsrfToken: overview.value?.evaluationCsrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("The visibility has been changed.")
    await loadOverview(false)
  } catch (error) {
    console.error("Error changing Gradebook evaluation visibility", error)
    errorMessage.value = extractErrorMessage(error)
  }
}

async function toggleEvaluationLock(evaluation) {
  if (!overview.value?.settings?.lockingEnabled) {
    return
  }

  try {
    await gradebookService.runEvaluationAction(
      {
        action: evaluation.locked ? "unlock" : "lock",
        evaluationId: Number(evaluation.id),
        submittedCsrfToken: overview.value?.evaluationCsrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("Saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error changing Gradebook evaluation lock", error)
    errorMessage.value = extractErrorMessage(error)
  }
}

function confirmDeleteEvaluation(evaluation) {
  if (evaluationLockedForTeacher(evaluation)) {
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteEvaluation(evaluation),
  })
}

async function deleteEvaluation(evaluation) {
  isSavingEvaluation.value = true
  errorMessage.value = ""

  try {
    await gradebookService.runEvaluationAction(
      {
        action: "delete",
        evaluationId: Number(evaluation.id),
        submittedCsrfToken: overview.value?.evaluationCsrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("Deleted")
    await loadOverview(false)
  } catch (error) {
    console.error("Error deleting Gradebook evaluation", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingEvaluation.value = false
  }
}

function startMoveEvaluation(evaluation) {
  if (!evaluation || evaluationLockedForTeacher(evaluation)) {
    return
  }

  movingEvaluation.value = evaluation
  moveEvaluationTargetCategoryId.value = Number(overview.value?.currentCategory?.id || 0)
  isMoveEvaluationDialogVisible.value = true
}

async function moveEvaluation() {
  if (!movingEvaluation.value || !moveEvaluationTargetCategoryId.value || isSavingEvaluation.value) {
    return
  }

  isSavingEvaluation.value = true
  errorMessage.value = ""

  try {
    await gradebookService.runEvaluationAction(
      {
        action: "move",
        evaluationId: Number(movingEvaluation.value.id),
        targetCategoryId: Number(moveEvaluationTargetCategoryId.value),
        submittedCsrfToken: overview.value?.evaluationCsrfToken || "",
      },
      getContextParams(false),
    )
    isMoveEvaluationDialogVisible.value = false
    movingEvaluation.value = null
    infoMessage.value = t("Saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error moving Gradebook evaluation", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingEvaluation.value = false
  }
}

function resetLinkForm() {
  linkForm.type = null
  linkForm.refId = null
  linkForm.categoryId = Number(overview.value?.currentCategory?.id || 0)
  linkForm.weight = 0
  linkForm.minScore = 0
  linkForm.pointsOne = 0
  linkForm.pointsMany = null
}

async function loadLinkOptions(linkId = null) {
  isLoadingLinkOptions.value = true
  errorMessage.value = ""

  try {
    const params = {
      ...getContextParams(false),
      categoryId: Number(overview.value?.currentCategory?.id || 0),
    }
    if (Number(linkId || 0) > 0) {
      params.linkId = Number(linkId)
    }

    linkOptions.value = await gradebookService.getLinkOptions(params)

    return linkOptions.value
  } catch (error) {
    console.error("Error loading Gradebook online activity options", error)
    errorMessage.value = extractErrorMessage(error)

    return null
  } finally {
    isLoadingLinkOptions.value = false
  }
}

async function startCreateLink() {
  if (currentCategoryLockedForTeacher.value || true === overview.value?.currentCategory?.hasGradeModel) {
    return
  }

  editingLinkId.value = null
  resetLinkForm()
  const options = await loadLinkOptions()
  if (!options) {
    return
  }

  const firstAvailableType = (Array.isArray(options.types) ? options.types : []).find(
    (type) => true === type.available && Array.isArray(type.items) && type.items.length > 0,
  )
  if (firstAvailableType) {
    linkForm.type = Number(firstAvailableType.type)
    linkForm.refId = Number(firstAvailableType.items[0]?.id || 0) || null
  }

  isLinkDialogVisible.value = true
}

async function startEditLink(link) {
  if (!link || linkLockedForTeacher(link) || false === link.valid) {
    return
  }

  editingLinkId.value = Number(link.id || 0)
  const options = await loadLinkOptions(editingLinkId.value)
  const current = options?.link
  if (!current) {
    editingLinkId.value = null
    return
  }

  linkForm.type = Number(current.linkType || 0) || null
  linkForm.refId = Number(current.refId || 0) || null
  linkForm.categoryId = Number(current.categoryId || overview.value?.currentCategory?.id || 0) || null
  linkForm.weight = Number(current.weight || 0)
  linkForm.minScore = current.minScore === null || current.minScore === undefined ? 0 : Number(current.minScore)
  linkForm.pointsOne = current.pointsOne === null || current.pointsOne === undefined ? 0 : Number(current.pointsOne)
  linkForm.pointsMany = current.pointsMany === null || current.pointsMany === undefined ? null : Number(current.pointsMany)
  isLinkDialogVisible.value = true
}

async function saveLink() {
  if (!canSaveLink.value || isSavingLink.value) {
    return
  }

  isSavingLink.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const isCreate = !editingLinkId.value
    const payload = {
      action: isCreate ? "create" : "update",
      linkId: editingLinkId.value,
      categoryId: Number(linkForm.categoryId),
      weight: linkUsesParticipationPoints.value ? null : Number(linkForm.weight || 0),
      minScore: Number(linkForm.minScore || 0),
      pointsOne: linkUsesParticipationPoints.value ? Number(linkForm.pointsOne || 0) : null,
      pointsMany:
        linkUsesParticipationPoints.value && linkForm.pointsMany !== null && linkForm.pointsMany !== undefined && linkForm.pointsMany !== ""
          ? Number(linkForm.pointsMany)
          : null,
      submittedCsrfToken: linkOptions.value?.csrfToken || overview.value?.linkCsrfToken || "",
    }
    if (isCreate) {
      payload.type = Number(linkForm.type)
      payload.refId = Number(linkForm.refId)
    }

    await gradebookService.runLinkAction(payload, getContextParams(false))
    isLinkDialogVisible.value = false
    editingLinkId.value = null
    linkOptions.value = null
    infoMessage.value = t("Saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error saving Gradebook online activity", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingLink.value = false
  }
}

async function toggleLinkVisibility(link) {
  if (!link || linkLockedForTeacher(link)) {
    return
  }

  try {
    await gradebookService.runLinkAction(
      {
        action: "set_visibility",
        linkId: Number(link.id),
        visible: !link.visible,
        submittedCsrfToken: overview.value?.linkCsrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("The visibility has been changed.")
    await loadOverview(false)
  } catch (error) {
    console.error("Error changing Gradebook online activity visibility", error)
    errorMessage.value = extractErrorMessage(error)
  }
}

function confirmDeleteLink(link) {
  if (!link || linkLockedForTeacher(link)) {
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteLink(link),
  })
}

async function deleteLink(link) {
  isSavingLink.value = true
  errorMessage.value = ""

  try {
    await gradebookService.runLinkAction(
      {
        action: "delete",
        linkId: Number(link.id),
        submittedCsrfToken: overview.value?.linkCsrfToken || "",
      },
      getContextParams(false),
    )
    infoMessage.value = t("Deleted")
    await loadOverview(false)
  } catch (error) {
    console.error("Error deleting Gradebook online activity", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingLink.value = false
  }
}

function startMoveLink(link) {
  if (!link || linkLockedForTeacher(link)) {
    return
  }

  movingLink.value = link
  moveLinkTargetCategoryId.value = Number(overview.value?.currentCategory?.id || 0)
  isMoveLinkDialogVisible.value = true
}

async function moveLink() {
  if (!movingLink.value || !moveLinkTargetCategoryId.value || isSavingLink.value) {
    return
  }

  isSavingLink.value = true
  errorMessage.value = ""

  try {
    await gradebookService.runLinkAction(
      {
        action: "move",
        linkId: Number(movingLink.value.id),
        targetCategoryId: Number(moveLinkTargetCategoryId.value),
        submittedCsrfToken: overview.value?.linkCsrfToken || "",
      },
      getContextParams(false),
    )
    isMoveLinkDialogVisible.value = false
    movingLink.value = null
    infoMessage.value = t("Saved")
    await loadOverview(false)
  } catch (error) {
    console.error("Error moving Gradebook online activity", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingLink.value = false
  }
}

watch(
  () => linkForm.type,
  (newType, oldType) => {
    if (editingLinkId.value || Number(newType || 0) === Number(oldType || 0)) {
      return
    }

    const selectedType = linkTypes.value.find((type) => Number(type.type) === Number(newType || 0))
    linkForm.refId = Number(selectedType?.items?.[0]?.id || 0) || null
  },
)

onMounted(loadOverview)

useStudentViewRefresh(loadOverview)
watch(
  () => [
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.categoryId,
    route.params.node,
  ],
  () => loadOverview(),
)
</script>

<style scoped>
:deep(.gradebook-overview-table .p-datatable-table-container),
:deep(.gradebook-overview-table .p-datatable-wrapper) {
  height: auto !important;
  max-height: none !important;
  overflow-y: hidden !important;
}
</style>
