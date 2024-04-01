import baseService from "./baseService"
import { USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_FRIEND_REQUEST } from "../constants/entity/userreluser"

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(searchParams = {}) {
  return await baseService.getCollection("/api/user_rel_users", searchParams)
}

/**
 * @param {string} userIri
 * @returns {Promise<Array<Object>>}
 */
async function getFriendList(userIri) {
  const { items } = await findAll({
    user: userIri,
    relationType: [USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_FRIEND_REQUEST],
  })

  return items
}

async function sendFriendRequest(userIri, friendIri) {
  return await baseService.post("/api/user_rel_users", {
    user: userIri,
    friend: friendIri,
    relationType: USER_RELATION_TYPE_FRIEND_REQUEST,
  })
}

/**
 * @param {string} userIri
 * @param {string} searchTerm
 * @returns {Promise<{totalItems, items}>}
 */
async function searchRelationshipByUsername(userIri, searchTerm) {
  return await findAll({
    user: userIri,
    "friend.username": searchTerm,
  })
}

export default {
  findAll,
  getFriendList,
  sendFriendRequest,
  searchRelationshipByUsername,
}
