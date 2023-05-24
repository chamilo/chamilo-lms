/**
 * Use it like chamiloIconToClass['eye'] to get the correct class for an icon
 *
 * Transform name of icons according to https://github.com/chamilo/chamilo-lms/wiki/Graphical-design-guide#default-icons-terminology
 * to the classes needed for represent every icon
*/
export const chamiloIconToClass = {
    "pencil": "mdi mdi-pencil",
    "delete": "",
    "hammer-wrench": "",
    "download": "",
    "download-box": "",
    "upload": "",
    "arrow-left-bold-box": "",
    "account-multiple-plus": "",
    "cursor-move": "",
    "chevron-left": "",
    "chevron-right": "",
    "arrow-up-bold": "",
    "arrow-down-bold": "",
    "arrow-right-bold": "",
    "magnify-plus-outline": "",
    "archive-arrow-up": "",
    "folder-multiple-plus": "",
    "folder-plus": "",
    "alert": "",
    "checkbox-marked": "",
    "pencil-off": "",
    "eye": "mdi mdi-eye",
    "eye-off": "",
    "checkbox-multiple-blank": "",
    "checkbox-multiple-blank-outline": "",
    "sync": "",
    "sync-circle": "",
    "fullscreen": "",
    "fullscreen-exit": "",
    "overscan": "",
    "play-box-outline": "",
    "fit-to-screen": "",
    "bug-check": "",
    "bug-outline": "",
    "package": "",
    "text-box-plus": "",
    "rocket-launch": "",
    "file-pdf-box": "",
    "content-save": "",
    "send": "",
    "file-plus": "",
    "cloud-upload": "",
    "dots-vertical": "",
    "information": "",
    "account-key": "",
    "cog": "mdi mdi-cog",
    "plus": "mdi mdi-plus",
};

export const validator = (value) => {
    if (typeof (value) !== "string") {
        return false;
    }

    return Object.keys(chamiloIconToClass).includes(value);
};
