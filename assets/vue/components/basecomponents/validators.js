// common validators across base components

import { chamiloIconToClass } from "./ChamiloIcons"

export const iconValidator = (value) => {
  if (typeof value !== "string") {
    return false
  }

  return Object.keys(chamiloIconToClass).includes(value)
}

export const sizeValidator = (value) => {
  if (typeof value !== "string") {
    return false
  }

  return ["normal", "small"].includes(value)
}

export const buttonTypeValidator = (value) => {
  if (typeof value !== "string") {
    return false
  }

  const baseTypes = [
    "primary",
    "primary-alternative",
    "secondary",
    "black",
    "success",
    "info",
    "warning",
    "danger",
    "tertiary",
  ]

  return baseTypes.includes(value) || baseTypes.includes(value.replace("-text", ""))
}
