import baseService from "./baseService"

/**
 * @returns {Promise<{totalItems, items}>}
 */
export async function findAll() {
  return baseService.getCollection("/api/skill_profiles")
}

/**
 * @param {string} title
 * @param {string} description
 * @param {Array<{skill}>} skills
 * @returns {Promise<Object>}
 */
export async function createProfile({ title, description, skills }) {
  return baseService.post("/api/skill_profiles", { title, description, skills })
}

/**
 * @param {string} iri
 * @param {string} title
 * @param {string} description
 * @returns {Promise<Object>}
 */
export async function updateProfile({ iri, title, description }) {
  return baseService.put(iri, { title, description })
}

/**
 * @param {string} iri
 * @returns {Promise<void>}
 */
export async function deleteProfile(iri) {
  await baseService.delete(iri)
}

/**
 * @param {Array<number>} idList
 * @returns {Promise<string>}
 */
export async function matchProfiles(idList) {
  return await baseService.get("/main/inc/ajax/skill.ajax.php", {
    a: "profile_matches",
    skill_id: idList,
  })
}
