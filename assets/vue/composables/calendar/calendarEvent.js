import { type } from "../../constants/entity/ccalendarevent"

export function useCalendarEvent() {
  return {
    findUserLink,
    isEditableByUser,
  }
}

/**
 * @param {Object} event
 * @param {number} userId
 * @returns {Object|undefined}
 */
function findUserLink(event, userId) {
  return event.resourceLinkListFromEntity.find((linkEntity) => linkEntity.user.id === userId)
}

/**
 * @param {Object} event
 * @param {number} userId
 * @returns {boolean}
 */
function isEditableByUser(event, userId) {
  if (event.resourceNode.creator.id === userId) {
    return true
  }

  if (type.invitation === event.invitationType && event.collective) {
    const userLink = findUserLink(event, userId)

    if (userLink) {
      return true
    }
  }

  return false
}
