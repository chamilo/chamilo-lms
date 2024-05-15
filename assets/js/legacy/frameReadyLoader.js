import { useMediaElementLoader } from "../../vue/composables/mediaElementLoader"
import "../../css/legacy/frameReadyLoader.scss"

const { domLoader: mejsLoader } = useMediaElementLoader()

mejsLoader()
