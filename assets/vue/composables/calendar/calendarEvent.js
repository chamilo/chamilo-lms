import { type, subscriptionVisibility } from "../../constants/entity/ccalendarevent"

export function useCalendarEvent() {
  return {
    findUserLink,
    isEditableByUser,
    isSubscribeable,
    canSubscribeToEvent,
    allowSubscribeToEvent,
    allowUnsubscribeToEvent,
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

function isSubscribeable(event) {
  if (type.subscription !== event.invitationType) {
    return false
  }

  return subscriptionVisibility.no !== event.subscriptionVisibility
}

/**
 * @param {Object} event
 * @returns {boolean}
 */
function canSubscribeToEvent(event) {
  return event.resourceLinkListFromEntity.length < event.maxAttendees || 0 === event.maxAttendees
}

function allowSubscribeToEvent(event) {
  if (!isSubscribeable(event)) {
    return false
  }

  return canSubscribeToEvent(event)
}

function allowUnsubscribeToEvent(event, userId) {
  if (!isSubscribeable(event)) {
    return false
  }

  return !!findUserLink(event, userId)
}
