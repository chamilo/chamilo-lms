/**
 * Plugin created by BeezNest Latino S.A.C
 *
 * For licensing terms, see /license.txt
 *
 * This plugin allows set quizzes markers in video with mediaelement.
 */

(function () {

    CKEDITOR.plugins.add('qmarkersrolls', {
        lang: [
            'en',
            'es',
        ],
        requires: ['video'],
        init: function (editor) {
            var lang = editor.lang.qmarkersrolls;

            editor
                .addCommand(
                    'qmarkersrolls',
                    new CKEDITOR.dialogCommand('qMarkersrollsDialog')
                );

            if (editor.contextMenu) {
                editor.addMenuGroup('qMarkersRollsGroup');
                editor.addMenuItem('qMarkersRollsItem', {
                    label: lang.setQuizMarkersRolls,
                    icon: this.path + 'images/icon.png',
                    command: 'qmarkersrolls',
                    group: 'qMarkersRollsGroup'
                });
                editor.contextMenu.addListener(function (element) {
                    if (element &&
                        element.is('img') &&
                        !element.isReadOnly() &&
                        element.data('cke-real-element-type') == 'video'
                    ) {
                        return {
                            qMarkersRollsItem: CKEDITOR.TRISTATE_OFF
                        };
                    }
                });
            }

            CKEDITOR.dialog.add('qMarkersrollsDialog', this.path + 'dialogs/qmarkersrolls.js');
        }
    });

})();
