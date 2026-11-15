export const INTRODUCTION_POSITIONS = Object.freeze(["above", "below", "left", "right"])
export const INTRODUCTION_POSITION_DEFAULT = "above"

export function normalizeIntroductionPosition(value) {
  return INTRODUCTION_POSITIONS.includes(value) ? value : INTRODUCTION_POSITION_DEFAULT
}

/**
 * Resolve the layout actually applied on the course home.
 *
 * Left and right only apply at desktop widths; they stack as "above" on
 * smaller screens. When the course sequence column is shown, "right" falls
 * back to "above" so the introduction does not compete with the sequence
 * for the right-hand column.
 */
export function resolveIntroductionLayout(position, { showSequence = false, isDesktop = true } = {}) {
  const normalized = normalizeIntroductionPosition(position)

  if (!isDesktop && (normalized === "left" || normalized === "right")) {
    return INTRODUCTION_POSITION_DEFAULT
  }

  if (showSequence && normalized === "right") {
    return INTRODUCTION_POSITION_DEFAULT
  }

  return normalized
}

export function introductionPositionOptions(t) {
  return [
    { label: t("Above the tools"), value: "above" },
    { label: t("Below the tools"), value: "below" },
    { label: t("Left of the tools"), value: "left" },
    { label: t("Right of the tools"), value: "right" },
  ]
}
