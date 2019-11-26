/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

'use strict';

CKEDITOR.dialog.add( 'asciimath', function( editor ) {

    var preview,
        lang = editor.lang.asciimath;

    var imagePath = CKEDITOR.plugins.getPath('asciimath') + "images/";

    return {
        title: lang.title,
        minWidth: 350,
        minHeight: 100,
        contents: [
            {
                id: 'info',
                elements: [
                    {
                        id: 'equation',
                        type: 'textarea',
                        label: lang.dialogInput,
                        class: 'asciimath_textarea',

                        onLoad: function( widget ) {
                            var that = this;

                            if ( !( CKEDITOR.env.ie && CKEDITOR.env.version == 8 ) ) {
                                this.getInputElement().on( 'keyup', function() {
                                    // Add ` and ` for preview.
                                    preview.setValue( '`' + that.getInputElement().getValue() + '`' );
                                } );

                                $('.Hand').on('click', function() {
                                    preview.setValue( '`' + that.getInputElement().getValue() + '`' );
                                });
                            }
                        },

                        setup: function( widget ) {
                            // Remove ` and `.
                            this.setValue( CKEDITOR.plugins.asciimath.trim( widget.data.math ) );
                        },

                        commit: function( widget ) {
                            // Add ` and ` to make ASCII be parsed by MathJax by default.
                            widget.setData( 'math', '`' + this.getValue() + '`' );
                        }
                    },
                    {
                        id: 'clickInput',
                        type: 'html',
                        html:
                            '<style type="text/css">'+
                            'body, td, input, textarea, select, label, button { font-family: Arial, Verdana, Geneva, helvetica, sans-serif; font-size: 11px; }' +
                            'form { padding: 0px; margin: 0px; }' +
                            'form p { margin-top: 5px; margin-bottom: 5px; }' +

                            '#clickInput' +
                            '{' +
                                'width: 100%;' +
                                'border-collapse: collapse;' +
                                'background-color: white;' +
                                'text-align: center;' +
                            '}' +
                            '#clickInput td' +
                            '{' +
                                'border: 1px solid gray;' +
                                'font-size: 1.1em;' +
                            '}' +
                            '#clickInput img' +
                            '{' +
                            'cursor: pointer;' +
                            '}' +

                            '.Hand' +
                            '{' +
                                'cursor: pointer;' +
                            '}' +

                            '</style>'+
                            '<table id="clickInput">' +
                                '<tr>' +
                                    '<td colspan="3" class="Hand" title="(x+1)/(x-1)" onclick="javascript: Set(\'(x+1)/(x-1)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'x1x1.png" /></td>' +
                                    '<td colspan="2" class="Hand" title="x^(m+n)" onclick="javascript: Set(\'x^(m+n)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'xmn.png" /></td>' +
                                    '<td colspan="2" class="Hand" title="x_(mn)" onclick="javascript: Set(\'x_(mn)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'x_mn.png" /></td>' +
                                    '<td colspan="2" class="Hand" title="sqrt(x)" onclick="javascript: Set(\'sqrt(x)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'sqrtx.png" /></td>' +
                                    '<td colspan="3" class="Hand" title="root(n)(x)" onclick="javascript: Set(\'root(n)(x)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rootnx.png" /></td>' +
                                    '<td colspan="3" class="Hand" title="{(1 if x&gt;=0),(0 if x&lt;0):}" onclick="javascript: Set(\'{(1 if x&gt;=0),(0 if x&lt;0):}\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ifx.png" /></td>' +
                                    '<td colspan="2" class="Hand" title="&quot;text&quot;" onclick="javascript: Set(\'&quot;text&quot;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'text.png" /></td>' +
                                '</tr><tr>' +
                                '<td colspan="2" class="Hand" title="dy/dx" onclick="javascript: Set(\'dy/dx\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'dydx.png" /></td>' +
                                '<td colspan="3" class="Hand" title="lim_(x-&gt;oo)" onclick="javascript: Set(\'lim_(x-&gt;oo)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'lim.png" /></td>' +
                                '<td colspan="3" class="Hand" title="sum_(n=1)^oo" onclick="javascript: Set(\'sum_(n=1)^oo\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'sumn.png" /></td>' +
                                '<td colspan="3" class="Hand" title="int_a^bf(x)dx" onclick="javascript: Set(\'int_a^bf(x)dx\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'intab.png" /></td>' +
                                '<td colspan="3" class="Hand" title="[[a,b],[c,d]]" onclick="javascript: Set(\'[[a,b],[c,d]]\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'abcd.png" /></td>' +
                                '<td colspan="2" class="Hand" title="((n),(k))" onclick="javascript: Set(\'((n),(k))\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'nk.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="*" onclick="javascript: Set(\'*\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'dot.png" /></td>' +
                                '<td class="Hand" title="**" onclick="javascript: Set(\'**\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'star.png" /></td>' +
                                '<td class="Hand" title="//" onclick="javascript: Set(\'//\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'slash.png" /></td>' +
                                '<td class="Hand" title="\\" onclick="javascript: Set(\'\\\\\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'backslash.png" /></td>' +
                                '<td class="Hand" title="xx" onclick="javascript: Set(\'xx\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'times.png" /></td>' +
                                '<td class="Hand" title="-:" onclick="javascript: Set(\'-:\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'div.png" /></td>' +
                                '<td class="Hand" title="@" onclick="javascript: Set(\'@\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'circ.png" /></td>' +
                                '<td class="Hand" title="o+" onclick="javascript: Set(\'o+\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'oplus.png" /></td>' +
                                '<td class="Hand" title="ox" onclick="javascript: Set(\'ox\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'otimes.png" /></td>' +
                                '<td class="Hand" title="o." onclick="javascript: Set(\'o.\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'odot.png" /></td>' +
                                '<td class="Hand" title="sum" onclick="javascript: Set(\'sum\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'sum.png" /></td>' +
                                '<td class="Hand" title="prod" onclick="javascript: Set(\'prod\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'prod.png" /></td>' +
                                '<td class="Hand" title="^^" onclick="javascript: Set(\'^^\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'wedge.png" /></td>' +
                                '<td class="Hand" title="^^^" onclick="javascript: Set(\'^^^\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bigwedge.png" /></td>' +
                                '<td class="Hand" title="vv" onclick="javascript: Set(\'vv\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'vee.png" /></td>' +
                                '<td class="Hand" title="vvv" onclick="javascript: Set(\'vvv\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bigvee.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="!=" onclick="javascript: Set(\'!=\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ne.png" /></td>' +
                                '<td class="Hand" title="&lt;=" onclick="javascript: Set(\'&lt;=\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'le.png" /></td>' +
                                '<td class="Hand" title="&gt;=" onclick="javascript: Set(\'&gt;=\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ge.png" /></td>' +
                                '<td class="Hand" title="-&lt;" onclick="javascript: Set(\'-&lt;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'prec.png" /></td>' +
                                '<td class="Hand" title="&gt;-" onclick="javascript: Set(\'&gt;-\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'succ.png" /></td>' +
                                '<td class="Hand" title="in" onclick="javascript: Set(\'in\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'in.png" /></td>' +
                                '<td class="Hand" title="!in" onclick="javascript: Set(\'!in\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'notin.png" /></td>' +
                                '<td class="Hand" title="sub" onclick="javascript: Set(\'sub\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'subset.png" /></td>' +
                                '<td class="Hand" title="sup" onclick="javascript: Set(\'sup\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'supset.png" /></td>' +
                                '<td class="Hand" title="sube" onclick="javascript: Set(\'sube\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'subseteq.png" /></td>' +
                                '<td class="Hand" title="supe" onclick="javascript: Set(\'supe\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'supseteq.png" /></td>' +
                                '<td class="Hand" title="O/" onclick="javascript: Set(\'O/\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'emptyset.png" /></td>' +
                                '<td class="Hand" title="nn" onclick="javascript: Set(\'nn\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'cap.png" /></td>' +
                                '<td class="Hand" title="nnn" onclick="javascript: Set(\'nnn\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bigcap.png" /></td>' +
                                '<td class="Hand" title="uu" onclick="javascript: Set(\'uu\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'cup.png" /></td>' +
                                '<td class="Hand" title="uuu" onclick="javascript: Set(\'uuu\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bigcup.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="and" onclick="javascript: Set(\'and\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'text_and.png" /></td>' +
                                '<td class="Hand" title="or" onclick="javascript: Set(\'or\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'text_or.png" /></td>' +
                                '<td class="Hand" title="not" onclick="javascript: Set(\'not\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'not.png" /></td>' +
                                '<td class="Hand" title="=&gt;" onclick="javascript: Set(\'=&gt;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rightarrow.png" /></td>' +
                                '<td class="Hand" title="if" onclick="javascript: Set(\'if\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'if.png" /></td>' +
                                '<td class="Hand" title="&lt;=&gt;" onclick="javascript: Set(\'&lt;=&gt;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'leftrightarrow.png" /></td>' +
                                '<td class="Hand" title="AA" onclick="javascript: Set(\'AA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'forall.png" /></td>' +
                                '<td class="Hand" title="EE" onclick="javascript: Set(\'EE\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'exists.png" /></td>' +
                                '<td class="Hand" title="_|_" onclick="javascript: Set(\'_|_\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bot.png" /></td>' +
                                '<td class="Hand" title="TT" onclick="javascript: Set(\'TT\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'top.png" /></td>' +
                                '<td class="Hand" title="|--" onclick="javascript: Set(\'|--\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'vdash.png" /></td>' +
                                '<td class="Hand" title="|==" onclick="javascript: Set(\'|==\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'models.png" /></td>' +
                                '<td class="Hand" title="-=" onclick="javascript: Set(\'-=\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'equiv.png" /></td>' +
                                '<td class="Hand" title="~=" onclick="javascript: Set(\'~=\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'cong.png" /></td>' +
                                '<td class="Hand" title="~~" onclick="javascript: Set(\'~~\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'approx.png" /></td>' +
                                '<td class="Hand" title="prop" onclick="javascript: Set(\'prop\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'propto.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="int" onclick="javascript: Set(\'int\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'int.png" /></td>' +
                                '<td class="Hand" title="oint" onclick="javascript: Set(\'oint\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'oint.png" /></td>' +
                                '<td class="Hand" title="del" onclick="javascript: Set(\'del\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'partial.png" /></td>' +
                                '<td class="Hand" title="grad" onclick="javascript: Set(\'grad\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'nabla.png" /></td>' +
                                '<td class="Hand" title="+-" onclick="javascript: Set(\'+-\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'pm.png" /></td>' +
                                '<td class="Hand" title="oo" onclick="javascript: Set(\'oo\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'infty.png" /></td>' +
                                '<td class="Hand" title="A\ B (space between A and B)" onclick="javascript: Set(\'A\\ B\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'space.png" /></td>' +
                                '<td class="Hand" title="AquadB (double space between A and B)" onclick="javascript: Set(\'AquadB\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'quad.png" /></td>' +
                                '<td class="Hand" title="diamond" onclick="javascript: Set(\'diamond\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'diamond.png" /></td>' +
                                '<td class="Hand" title="square" onclick="javascript: Set(\'square\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'square.png" /></td>' +
                                '<td class="Hand" title="|__" onclick="javascript: Set(\'|__\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'lfloor.png" /></td>' +
                                '<td class="Hand" title="__|" onclick="javascript: Set(\'__|\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rfloor.png" /></td>' +
                                '<td class="Hand" title="|~" onclick="javascript: Set(\'|~\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'lceil.png" /></td>' +
                                '<td class="Hand" title="~|" onclick="javascript: Set(\'~|\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rceil.png" /></td>' +
                                '<td class="Hand" title="&lt;&lt;x&gt;&gt;" onclick="javascript: Set(\'&lt;&lt;x&gt;&gt;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'braxcket.png" /></td>' +
                                '<td class="Hand" title="/_" onclick="javascript: Set(\'/_\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'angle.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="uarr" onclick="javascript: Set(\'uarr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'uarr.png" /></td>' +
                                '<td class="Hand" title="darr" onclick="javascript: Set(\'darr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'darr.png" /></td>' +
                                '<td class="Hand" title="larr" onclick="javascript: Set(\'larr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'larr.png" /></td>' +
                                '<td class="Hand" title="-&gt;" onclick="javascript: Set(\'-&gt;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rarr.png" /></td>' +
                                '<td class="Hand" title="|-&gt;" onclick="javascript: Set(\'|-&gt;\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'mapsto.png" /></td>' +
                                '<td class="Hand" title="harr" onclick="javascript: Set(\'harr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'harr.png" /></td>' +
                                '<td class="Hand" title="lArr" onclick="javascript: Set(\'lArr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'llarr.png" /></td>' +
                                '<td class="Hand" title="rArr" onclick="javascript: Set(\'rArr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rrarr.png" /></td>' +
                                '<td class="Hand" title="hArr" onclick="javascript: Set(\'hArr\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'hharr.png" /></td>' +
                                '<td class="Hand" title="hata" onclick="javascript: Set(\'hata\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'hata.png" /></td>' +
                                '<td class="Hand" title="ula" onclick="javascript: Set(\'ula\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ula.png" /></td>' +
                                '<td class="Hand" title="dota" onclick="javascript: Set(\'dota\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'dota.png" /></td>' +
                                '<td class="Hand" title="ddota" onclick="javascript: Set(\'ddota\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ddota.png" /></td>' +
                                '<td class="Hand" title="veca" onclick="javascript: Set(\'veca\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'veca.png" /></td>' +
                                '<td class="Hand" title="bara" onclick="javascript: Set(\'bara\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bara.png" /></td>' +
                                '<td class="Hand" title=":." onclick="javascript: Set(\':.\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'therefore.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="NN" onclick="javascript: Set(\'NN\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'nn.png" /></td>' +
                                '<td class="Hand" title="ZZ" onclick="javascript: Set(\'ZZ\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'zz.png" /></td>' +
                                '<td class="Hand" title="QQ" onclick="javascript: Set(\'QQ\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'qq.png" /></td>' +
                                '<td class="Hand" title="RR" onclick="javascript: Set(\'RR\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rr.png" /></td>' +
                                '<td class="Hand" title="CC" onclick="javascript: Set(\'CC\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'cc.png" /></td>' +
                                '<td class="Hand" title="bbA" onclick="javascript: Set(\'bbA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bba.png" /></td>' +
                                '<td class="Hand" title="bbbA" onclick="javascript: Set(\'bbbA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'bbba.png" /></td>' +
                                '<td class="Hand" title="ccA" onclick="javascript: Set(\'ccA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'cca.png" /></td>' +
                                '<td class="Hand" title="frA" onclick="javascript: Set(\'frA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'fra.png" /></td>' +
                                '<td class="Hand" title="sfA" onclick="javascript: Set(\'sfA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'sfa.png" /></td>' +
                                '<td class="Hand" title="ttA" onclick="javascript: Set(\'ttA\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'tta.png" /></td>' +
                                '<td colspan="3" class="Hand" title="stackrel(-&gt;)(+)" onclick="javascript: Set(\'stackrel(-&gt;)(+)\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'stackrel.png" /></td>' +
                                '<td class="Hand" title="aleph" onclick="javascript: Set(\'aleph\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'aleph.png" /></td>' +
                                '<td class="Hand" title="upsilon" onclick="javascript: Set(\'upsilon\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'upsilon.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="alpha" onclick="javascript: Set(\'alpha\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'alpha.png" /></td>' +
                                '<td class="Hand" title="beta" onclick="javascript: Set(\'beta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'beta.png" /></td>' +
                                '<td class="Hand" title="gamma" onclick="javascript: Set(\'gamma\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'gamma.png" /></td>' +
                                '<td class="Hand" title="Gamma" onclick="javascript: Set(\'Gamma\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ggamma.png" /></td>' +
                                '<td class="Hand" title="delta" onclick="javascript: Set(\'delta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'delta.png" /></td>' +
                                '<td class="Hand" title="Delta" onclick="javascript: Set(\'Delta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ddelta.png" /></td>' +
                                '<td class="Hand" title="epsi" onclick="javascript: Set(\'epsi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'epsilon.png" /></td>' +
                                '<td class="Hand" title="zeta" onclick="javascript: Set(\'zeta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'zeta.png" /></td>' +
                                '<td class="Hand" title="eta" onclick="javascript: Set(\'eta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'eta.png" /></td>' +
                                '<td class="Hand" title="theta" onclick="javascript: Set(\'theta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'theta.png" /></td>' +
                                '<td class="Hand" title="Theta" onclick="javascript: Set(\'Theta\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ttheta.png" /></td>' +
                                '<td class="Hand" title="iota" onclick="javascript: Set(\'iota\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'iota.png" /></td>' +
                                '<td class="Hand" title="kappa" onclick="javascript: Set(\'kappa\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'kappa.png" /></td>' +
                                '<td class="Hand" title="lambda" onclick="javascript: Set(\'lambda\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'lambda.png" /></td>' +
                                '<td class="Hand" title="Lambda" onclick="javascript: Set(\'Lambda\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'llambda.png" /></td>' +
                                '<td class="Hand" title="mu" onclick="javascript: Set(\'mu\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'mu.png" /></td>' +
                            '</tr><tr>' +
                                '<td class="Hand" title="nu" onclick="javascript: Set(\'nu\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'nu.png" /></td>' +
                                '<td class="Hand" title="pi" onclick="javascript: Set(\'pi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'pi.png" /></td>' +
                                '<td class="Hand" title="Pi" onclick="javascript: Set(\'Pi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ppi.png" /></td>' +
                                '<td class="Hand" title="rho" onclick="javascript: Set(\'rho\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'rho.png" /></td>' +
                                '<td class="Hand" title="sigma" onclick="javascript: Set(\'sigma\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'sigma.png" /></td>' +
                                '<td class="Hand" title="Sigma" onclick="javascript: Set(\'Sigma\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ssigma.png" /></td>' +
                                '<td class="Hand" title="tau" onclick="javascript: Set(\'tau\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'tau.png" /></td>' +
                                '<td class="Hand" title="xi" onclick="javascript: Set(\'xi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'xi.png" /></td>' +
                                '<td class="Hand" title="Xi" onclick="javascript: Set(\'Xi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'xxi.png" /></td>' +
                                '<td class="Hand" title="phi" onclick="javascript: Set(\'phi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'phi.png" /></td>' +
                                '<td class="Hand" title="Phi" onclick="javascript: Set(\'Phi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'pphi.png" /></td>' +
                                '<td class="Hand" title="chi" onclick="javascript: Set(\'chi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'chi.png" /></td>' +
                                '<td class="Hand" title="psi" onclick="javascript: Set(\'psi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'psi.png" /></td>' +
                                '<td class="Hand" title="Psi" onclick="javascript: Set(\'Psi\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'ppsi.png" /></td>' +
                                '<td class="Hand" title="omega" onclick="javascript: Set(\'omega\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'omega.png" /></td>' +
                                '<td class="Hand" title="Omega" onclick="javascript: Set(\'Omega\');" onmouseover="javascript: over(this);" onmouseout="javascript: out(this);"><img src="'+imagePath+'oomega.png" /></td>' +
                            '</tr>' +
                            '</table>'
                    },
                    {
                        id: 'documentation',
                        type: 'html',
                        html:
                        '<div style="width:100%;text-align:right;margin:-8px 0 10px">' +
                        '<a class="cke_mathjax_doc" href="' + lang.docUrl + '" target="_black" style="cursor:pointer;color:#00B2CE;text-decoration:underline">' +
                        lang.docLabel +
                        '</a>' +
                        '</div>'
                    },
                    ( !( CKEDITOR.env.ie && CKEDITOR.env.version == 8 ) ) && {
                        id: 'preview',
                        type: 'html',
                        html:
                        '<div style="width:100%;text-align:center;">' +
                        '<iframe style="border:0;width:0;height:0;font-size:20px" scrolling="no" frameborder="0" allowTransparency="true" src="' + CKEDITOR.plugins.asciimath.fixSrc + '"></iframe>' +
                        '</div>',

                        onLoad: function( widget ) {
                            var iFrame = CKEDITOR.document.getById( this.domId ).getChild( 0 );
                            preview = new CKEDITOR.plugins.asciimath.frameWrapper( iFrame, editor );
                        },

                        setup: function( widget ) {
                            preview.setValue( widget.data.math );
                        }
                    }
                ]
            }
        ]
    };
} );

// Highlighting formulas.

function over(td)
{
    td.className = 'LightBackground Hand' ;
}

function out(td)
{
    td.className = 'Hand' ;
}

function Set( string )
{
    var inputField = document.getElementsByClassName('asciimath_textarea')[0];
    inputField.value += string;
    return false;
}