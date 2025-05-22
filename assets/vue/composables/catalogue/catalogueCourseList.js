import { ref } from "vue"
import courseService from "../../services/courseService"

import { useLanguage } from "../language"
import { useNotification } from "../notification"

import { FilterMatchMode } from "@primevue/core/api"

import * as trackCourseRanking from "../../services/trackCourseRankingService"

export function useCatalogueCourseList() {
  const isLoading = ref(true)
  const courses = ref([])
  const filters = ref({})

  const { findByIsoCode: findLanguageByIsoCode } = useLanguage()
  const { showErrorNotification } = useNotification()

  function initFilters() {
    filters.value = {
      global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    }
  }

  /**
   * @returns {Promise<void>}
   */
  async function load() {
    isLoading.value = true

    try {
      const items = await courseService.listCatalogueCourses()

      courses.value = items.map((course) => ({
        ...course,
        courseLanguage: findLanguageByIsoCode(course.courseLanguage)?.originalName,
      }))
    } catch (error) {
      showErrorNotification(error)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * @param {number} courseId
   * @param {number} totalScore
   * @returns {Promise<void>}
   */
  async function createRating(courseId, totalScore) {
    try {
      const response = await trackCourseRanking.saveRanking({
        totalScore,
        courseIri: `/api/courses/${courseId}`,
        urlId: window.access_url_id,
        sessionId: 0,
      })

      courses.value.forEach((course) => {
        if (course.id === courseId) {
          course.trackCourseRanking = response
        }
      })
    } catch (e) {
      showErrorNotification(e)
    }
  }

  /**
   * @param {number} trackCourseRankingId
   * @param {number} totalScore
   * @returns {Promise<void>}
   */
  async function updateRating(trackCourseRankingId, totalScore) {
    try {
      const response = await trackCourseRanking.updateRanking({
        trackCourseRankingId,
        totalScore,
      })

      courses.value.forEach((course) => {
        if (course.trackCourseRanking && course.trackCourseRanking.id === trackCourseRankingId) {
          course.trackCourseRanking.realTotalScore = response.realTotalScore
        }
      })
    } catch (e) {
      showErrorNotification(e)
    }
  }

  /**
   * @param {number} value
   * @param {Object} course
   * @returns {Promise<void>}
   */
  async function onRatingChange({ value }, course) {
    if (value > 0) {
      if (course.trackCourseRanking) {
        await updateRating(course.trackCourseRanking.id, value)
      } else {
        await createRating(course.id, value)
      }
    }
  }

  return {
    isLoading,
    courses,
    filters,
    load,
    initFilters,
    onRatingChange,
  }
}
