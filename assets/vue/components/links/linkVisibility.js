import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

/**
 * @param {Boolean} visibility
 */
export function visibilityFromBoolean(visibility) {
  return visibility ? RESOURCE_LINK_PUBLISHED : RESOURCE_LINK_DRAFT
}

/**
 * @param {Number} visibilityProperty
 */
export function toggleVisibilityProperty(visibilityProperty) {
  if (visibilityProperty === RESOURCE_LINK_DRAFT) {
    return RESOURCE_LINK_PUBLISHED
  } else if (visibilityProperty === RESOURCE_LINK_PUBLISHED) {
    return RESOURCE_LINK_DRAFT
  } else {
    console.error(`Toggle visibility is not posible with value "${visibilityProperty}"`)
  }
}

/**
 * @param {Number} visibilityProperty
 */
export function isVisible(visibilityProperty) {
  return visibilityProperty === RESOURCE_LINK_PUBLISHED
}
