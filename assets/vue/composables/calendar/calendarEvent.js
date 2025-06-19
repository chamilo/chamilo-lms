import { DateTime } from "luxon"

import cCalendarEventService from "../../services/ccalendarevent"
const { getCurrentTimezone } = useFormatDate()

import { subscriptionVisibility, type } from "../../constants/entity/ccalendarevent"
import { useFormatDate } from "../formatDate"

export function useCalendarEvent() {
  return {
    findUserLink,
    isEditableByUser,
    isSubscribeable,
    canSubscribeToEvent,
    allowSubscribeToEvent,
    allowUnsubscribeToEvent,
    getCalendarEvents,
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

/**
 * @param {Object} params
 * @returns {Promise<Object[]>}
 */
async function requestCalendarEvents(params) {
  const calendarEvents = await cCalendarEventService.findAll({ params }).then((response) => response.json())

  return calendarEvents["hydra:member"].map((event) => {
    const timezone = getCurrentTimezone()
    const start = DateTime.fromISO(event.startDate, { zone: "utc" }).setZone(timezone)
    const end = DateTime.fromISO(event.endDate, { zone: "utc" }).setZone(timezone)

    return {
      ...event,
      start: start.toString(),
      end: end.toString(),
      color: event.color || "#007BFF",
    }
  })
}

/**
 * @param {Object} startDate
 * @param {Object} endDate
 * @param {Object} commonParams
 * @returns {Promise<Object[]>}
 */
async function getCalendarEvents(startDate, endDate, commonParams) {
  const endingEventsPromise = requestCalendarEvents({
    ...commonParams,
    "endDate[before]": endDate.toISOString(),
    "endDate[after]": startDate.toISOString(),
  })

  const currentEventsPromise = requestCalendarEvents({
    ...commonParams,
    "startDate[before]": startDate.toISOString(),
    "endDate[after]": endDate.toISOString(),
  })

  const startingEventsPromise = requestCalendarEvents({
    ...commonParams,
    "startDate[before]": endDate.toISOString(),
    "startDate[after]": startDate.toISOString(),
  })

  const [endingEvents, currentEvents, startingEvents] = await Promise.all([
    endingEventsPromise,
    currentEventsPromise,
    startingEventsPromise,
  ])

  const uniqueEventsMap = new Map()

  endingEvents
    .concat(startingEvents)
    .concat(currentEvents)
    .forEach((event) => uniqueEventsMap.set(event.id, event))

  return Array.from(uniqueEventsMap.values())
}
