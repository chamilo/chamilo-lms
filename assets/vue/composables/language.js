export function useLanguage() {
  const defaultLanguage = { originalName: "English", isocode: "en_US" }

  /**
   * @type {{originalName: string, isocode: string}[]}
   */
  const languageList = window.languages || [defaultLanguage]

  /**
   * @param {string} isoCode
   * @returns {{originalName: string, isocode: string}|undefined}
   */
  function findByIsoCode(isoCode) {
    return languageList.find((language) => isoCode === language.isocode)
  }

  return {
    defaultLanguage,
    languageList,
    findByIsoCode,
  }
}
