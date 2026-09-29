import { defineStore } from "pinia"
import { ref } from "vue"
import pageService from "../services/page"

const CACHE_DURATION_MS = 5 * 60 * 1000

function buildKey(categoryTitle, locale) {
  return `${categoryTitle}:${locale}`
}

export const usePagesStore = defineStore("pages", () => {
  const entries = ref({})

  function getPages(categoryTitle, locale) {
    return entries.value[buildKey(categoryTitle, locale)]?.pages ?? []
  }

  async function fetchByCategory(categoryTitle, locale) {
    const key = buildKey(categoryTitle, locale)
    const entry = entries.value[key]

    if (entry?.fetchedAt && Date.now() - entry.fetchedAt < CACHE_DURATION_MS) {
      return entry.pages
    }

    if (entry?.promise) {
      return entry.promise
    }

    const promise = pageService
      .findAll({
        params: {
          "category.title": categoryTitle,
          enabled: "1",
          locale,
        },
        skipCourseContext: true,
      })
      .then((response) => response.json())
      .then((json) => {
        const pages = json["hydra:member"] || []
        entries.value[key] = { pages, fetchedAt: Date.now(), promise: null }
        return pages
      })
      .catch((error) => {
        if (entries.value[key]) {
          entries.value[key].promise = null
        }
        throw error
      })

    entries.value[key] = { pages: entry?.pages ?? [], fetchedAt: entry?.fetchedAt ?? null, promise }

    return promise
  }

  return {
    getPages,
    fetchByCategory,
  }
})
