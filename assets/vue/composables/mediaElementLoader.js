import "mediaelement/build/mediaelementplayer.min.css"
import "mediaelement/full"

import iconSprite from "../../css/scss/libs/mediaelementjs/icons.svg"

const videoSelector = "video:not(.skip), audio:not(.skip)"

const mejsOptions = {
  iconSprite,
}

function newVideosCallback(newVideo) {
  const attrId = newVideo.getAttribute("id")

  if (attrId && attrId.startsWith("mejs")) {
    return
  }

  newVideo.classList.add("not-prose")

  // eslint-disable-next-line no-undef
  new MediaElementPlayer(newVideo, mejsOptions)
}

function addedNodesCallback(newNode) {
  if (!newNode.querySelectorAll) {
    return
  }

  const newVideos = newNode.querySelectorAll(videoSelector)

  if (!newVideos.length) {
    return
  }

  newVideos.forEach(newVideosCallback)
}

function observerCallback(mutationList) {
  for (const { type, addedNodes } of mutationList) {
    if ("childList" !== type) {
      continue
    }

    addedNodes.forEach(addedNodesCallback)
  }
}

function loader() {
  const observer = new MutationObserver(observerCallback)

  observer.observe(document.querySelector("body"), {
    childList: true,
    subtree: true,
  })
}

function domLoader() {
  document.addEventListener("DOMContentLoaded", function () {
    loader()
  })
}

export function useMediaElementLoader() {
  return {
    loader,
    domLoader,
  }
}
