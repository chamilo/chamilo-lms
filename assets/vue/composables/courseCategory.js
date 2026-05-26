import { ref } from "vue"
import * as courseCategoryService from "../services/coursecategory"
import { usePlatformConfig } from "../store/platformConfig"
import { useSecurityStore } from "../store/securityStore"

async function getForCatalogue() {
  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()

  let categoryToAvoid = ""

  if (securityStore.isStudent) {
    categoryToAvoid = platformConfigStore.getSetting("course.course_category_code_to_use_as_model")
  }

  /** @type {string[]} */
  const showOnlyCategory = platformConfigStore.getSetting("catalog.only_show_course_from_selected_category")

  const categories = await courseCategoryService.findAll()

  return categories.filter((category) => {
    if (Array.isArray(showOnlyCategory) && showOnlyCategory.length > 0 && !showOnlyCategory.includes(category.code)) {
      return false
    }

    return !categoryToAvoid || category.code !== categoryToAvoid
  })
}

async function getForCourseCreation() {
  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()

  let categoryToAvoid = ""

  if (!securityStore.isAdmin) {
    categoryToAvoid = platformConfigStore.getSetting("course.course_category_code_to_use_as_model")
  }

  const categories = await courseCategoryService.findAll()

  return categories.filter((category) => !(categoryToAvoid && category.code === categoryToAvoid))
}

export function useCourseCategories(action) {
  const categories = ref([])
  const error = ref(null)
  const isLoading = ref(true)

  let promise

  if ("catalogue" === action) {
    promise = getForCatalogue()
  } else if ("course-creation" === action) {
    promise = getForCourseCreation()
  }

  if (promise) {
    promise
      .then((items) => (categories.value = items))
      .catch((e) => (error.value = e))
      .finally(() => (isLoading.value = false))
  }

  return { isLoading, categories, error }
}
