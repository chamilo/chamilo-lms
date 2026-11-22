# Shared FontAwesome 4.5 assets for CStudio H5P templates

The three H5P Standalone activity templates in `hvpdragthewords`,
`hvpfillintheblanks`, and `hvpmarkthewords` load their own `FontAwesome-4.5`
`h5p-font-awesome.min.css` and `library.json` files. Only their six identical font
binaries are centralized here. The relative font URLs in each stylesheet still
resolve when CStudio copies `oel-plug/` into a standalone SCORM/xAPI package.

This is **not** a replacement with the current Chamilo FontAwesome package:
existing H5P 4.5 icon codepoints and CSS are retained for compatibility.

Font Awesome 4.5 font binaries: SIL Open Font License 1.1.
The original stylesheet headers and H5P library metadata retain the
FontAwesome copyright and licensing references.
