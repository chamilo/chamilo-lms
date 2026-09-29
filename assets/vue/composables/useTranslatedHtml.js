import { toValue } from "vue"
import { useCidReqStore } from "../store/cidReq"
import { usePlatformConfig } from "../store/platformConfig"
import { filterTranslatedHtml } from "../../js/translatehtml.js"

/**
 * Applies translatehtml language filtering to stored multilingual content,
 * falling back from the viewer's own locale to the course language, then to
 * the platform default language, instead of rendering blank when no block
 * matches the viewer's locale.
 *
 * Rendering is intentionally independent from editor.translate_html. That
 * setting controls whether the TinyMCE translatehtml authoring plugin is
 * available; it must not make already-stored multilingual content unreadable.
 * Legacy pages follow the same rule because translateHtml() runs regardless
 * of the editor setting.
 *
 * Note: this deliberately does not honor the per-course
 * "show_course_in_user_language" setting. When that setting is on, the course
 * language equals the user's own locale, which is already the first (and, by
 * definition, already-failed) candidate — so following it here could only
 * ever remove a fallback candidate and make blank content more likely.
 *
 * @param {string|(() => string)|import("vue").Ref<string>|null} [courseLanguageSource]
 *   The course language to use as a fallback. Accepts a ref, a getter, or a
 *   plain string. Defaults to the current cidReq course's language.
 */
export function useTranslatedHtml(courseLanguageSource = null) {
  const cidReqStore = useCidReqStore()
  const platformConfigStore = usePlatformConfig()

  function displayTranslatedHtml(html) {
    if (!html) {
      return ""
    }

    const fallbackLocales = [
      toValue(courseLanguageSource) ?? cidReqStore.course?.courseLanguage,
      platformConfigStore.getSetting("language.platform_language"),
    ].filter(Boolean)

    return filterTranslatedHtml(html, window.user?.locale, fallbackLocales)
  }

  return { displayTranslatedHtml }
}
