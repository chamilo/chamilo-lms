export const NOT_VISIBLE = 0
export const VISIBLE = 2

/**
 * @param {Boolean} visibility
 */
export function visibilityFromBoolean(visibility) {
  return visibility ? VISIBLE : NOT_VISIBLE
}

/**
 * @param {Number} visibilityProperty
 */
export function toggleVisibilityProperty(visibilityProperty) {
  if (visibilityProperty === NOT_VISIBLE) {
    return VISIBLE
  } else if (visibilityProperty === VISIBLE) {
    return NOT_VISIBLE
  } else {
    console.error(`Toggle visibility is not posible with value "${visibilityProperty}"`)
  }
}

/**
 * @param {Number} visibilityProperty
 */
export function isVisible(visibilityProperty) {
  return visibilityProperty === VISIBLE
}
