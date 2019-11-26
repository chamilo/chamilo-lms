CKEDITOR.dialog.add( 'asciisvg', function( editor ) {
    var lang = editor.lang.asciisvg;

    function getValues(dialog) {
        var dialogContents = dialog.definition.contents[0];
        var pageId = dialogContents.id;
        var dialogFieldValues = {};
        for (var i = 0; i < dialogContents.elements.length-1; i++) {
            for (var j = 0; j < dialogContents.elements[i].children.length; j++) {
                var fieldId = dialogContents.elements[i].children[j].id;
                if (isNaN(dialog.getValueOf(pageId, fieldId))) {
                    dialogFieldValues[fieldId] = dialog.getValueOf(pageId, fieldId);
                } else {
                    dialogFieldValues[fieldId] = Number(dialog.getValueOf(pageId, fieldId));
                }
            }
        }
        stroke = dialogFieldValues.color;
        strokewidth = dialogFieldValues.width;
        strokedasharray = dialogFieldValues.line;
        xmin = dialogFieldValues.xmin;
        xmax = dialogFieldValues.xmax;
        xscl = dialogFieldValues.xscl;
        ymin = dialogFieldValues.ymin;
        ymax = dialogFieldValues.ymax;
        yscl = dialogFieldValues.yscl;
        width = dialogFieldValues.resizeTo;
        height = dialogFieldValues.by;
        return dialogFieldValues;
    }

    function updateFields(control) {
        var dialog = control.getDialog();
        var pageId = (dialog.definition.contents[0]).id;
        var equationTypeField = dialog.getContentElement(pageId, 'equationType');
        var equationField = dialog.getContentElement(pageId, 'equation');
        var extraField = dialog.getContentElement(pageId, 'extraField');
        var extraFieldIsVisible = extraField.getElement().isVisible();
        var value = control.getValue();
        if (value.indexOf(',') > -1) {
            value = value.split(',');
        } else {
            value = [value];
        }
        var found = false;
        var i = 0;
        while (found === false && i < equationTypeField.items.length) {
            if (equationTypeField.items[i][1] === value[0]) {
                found = true;
            } else {
                i++;
            }
        }
        equationTypeField.getInputElement().$.selectedIndex = i;
        switch (value[0]) {
            case 'func':
                if (extraFieldIsVisible === true) {
                    extraField.getElement().hide();
                }
                equationField.setLabel(lang.FOfX);
                dialog.setValueOf(pageId, 'equation', lang.SinOfX);
                break;
            case 'polar':
                if (extraFieldIsVisible === true) {
                    extraField.getElement().hide();
                }
                equationField.setLabel(lang.ROfT);
                dialog.setValueOf(pageId, 'equation', lang.T);
                break;
            case 'param':
                equationField.setLabel(lang.FOfT);
                dialog.setValueOf(pageId, 'equation', lang.SinOfT);
                extraField.setLabel(lang.GOfT);
                dialog.setValueOf(pageId, 'extraField', lang.CosOfT);
                if (extraFieldIsVisible === false) {
                    extraField.getElement().show();
                }
                break;
            case 'slope':
                equationField.setLabel(lang.DySplitDxOfXAndY);
                dialog.setValueOf(pageId, 'equation', lang.XByY);
                extraField.setLabel(lang.Every);
                dialog.setValueOf(pageId, 'extraField', '1');
                if (extraFieldIsVisible === false) {
                    extraField.getElement().show();
                }
                break;
            case 'label':
                if (extraFieldIsVisible === true) {
                    extraField.getElement().hide();
                }
                equationField.setLabel(lang.Label);
                dialog.setValueOf(pageId, 'equation', lang.Text);
                break;
        }
        var fromField = dialog.getContentElement(pageId, 'from');
        var toField = dialog.getContentElement(pageId, 'to');
        if (value[0] === 'label') {
            fromField.setLabel(lang.XPosition);
            toField.setLabel(lang.YPosition);
            dialog.setValueOf(pageId, 'from', '0');
            dialog.setValueOf(pageId, 'to', '0');
        } else {
            fromField.setLabel(lang.From);
            toField.setLabel(lang.To);
            dialog.setValueOf(pageId, 'from', '-7.5');
            dialog.setValueOf(pageId, 'to', '7.5');
        }
        if (value.length > 1) {
            dialog.setValueOf(pageId, 'startWith', value[3]);
            dialog.setValueOf(pageId, 'endWith', value[4]);
            dialog.setValueOf(pageId, 'from', value[5]);
            dialog.setValueOf(pageId, 'to', value[6]);
            dialog.setValueOf(pageId, 'color', value[7]);
            dialog.setValueOf(pageId, 'width', value[8]);
            dialog.setValueOf(pageId, 'line', value[9]);
        } else {
            var fieldsToDefault = ['color','width','line','startWith','endWith'];
            for (var i = 0; i < fieldsToDefault.length; i++) {
                dialog.setValueOf(pageId, fieldsToDefault[i], dialog.getContentElement(pageId, fieldsToDefault[i]).default);
            }
        }
    }

    function addGraph(dialog) {
        var pageId = (dialog.definition.contents[0]).id;
        var equationTypeField = dialog.getContentElement(pageId, 'equationType');
        var selectedIndex = equationTypeField.getInputElement().$.selectedIndex;
        var equationType = [equationTypeField.items[selectedIndex][0]];
        var equation = [dialog.getValueOf(pageId, 'equation')];
        var graphEquation = [[],[]];
        // Check if there is more than one equal sign (more than one equation type)
        if (equationType[0].split('=').length - 1 > 1) {
            equationType = equationType[0].split(',');
            var extraField = dialog.getValueOf(pageId, 'extraField');
            equation = [equation[0], extraField];
        }
        // Store left and right parts of the graph equations
        for (var i = 0; i < equationType.length; i++) {
            // Left part of the graph equations
            if (equationType[0].indexOf('=') > -1) {
                graphEquation[0][i] = equationType[i].substring(0, equationType[i].indexOf('='));
            } else {
                graphEquation[0][i] = equationType[i];
            }
            // Right part of the graph equations
            graphEquation[1][i] = equation[i];
        }
        // Convert graphEquation arrays to strings separated by commas
        graphEquation[0] = graphEquation[0].join();
        graphEquation[1] = graphEquation[1].join();
        // Add square brackets if there is more than one equation type
        if (equationType.length > 1) {
            graphEquation = '['+graphEquation[0]+']=['+graphEquation[1]+']';
        } else {
            graphEquation = graphEquation[0]+'='+graphEquation[1];
            equation = equation[0];
        }
        var graphsField = dialog.getContentElement(pageId, 'graphs');
        // Add graph equation to graphs select
        var values = getValues(dialog);
        graphsField.items.push(graphEquation);
        var sscr = values.equationType+','+values.equation+','+values.extraField+','+values.startWith+','+values.endWith+','+values.from+','+values.to+','+values.color+','+values.width+','+values.line;
        graphsField.add(graphEquation, sscr);
        sscr = xmin+','+xmax+','+ymin+','+ymax+','+xscl+','+yscl+','+(xscl||yscl)+','+xgrid+','+ygrid+','+width+','+height;
        var graphsFieldLastIndex = graphsField.getInputElement().$.options.length-1;
        graphsField.getInputElement().$.selectedIndex = graphsFieldLastIndex;
        picture.sscr = sscr;
        switch(values.equationType) {
            case 'func':
                var functionCall = values.equation;
                plot(functionCall, values.from, values.to, null, null, values.startWith, values.endWith);
                break;
            case 'polar':
                var functionCall = ["cos(t)*("+values.equation+")","sin(t)*("+values.equation+")"];
                plot(functionCall, values.from, values.to, null, null, values.startWith, values.endWith);
                break;
            case 'param':
                var functionCall = [values.equation, values.extraField];
                plot(functionCall, values.from, values.to, null, null, values.startWith, values.endWith);
                break;
            case 'slope':
                slopefield(values.equation, values.extraField, values.extraField);
                plot(null, values.from, values.to, null, null, values.startWith, values.endWith);
                break;
            case 'label':
                text([values.from, values.to], values.equation);
                break;
        }
    }

    function removeSelectedGraph(dialog) {
        var pageId = (dialog.definition.contents[0]).id;
        var graphsField = dialog.getContentElement(pageId, 'graphs');
        var selectedIndex = graphsField.getInputElement().$.selectedIndex;
        graphsField.items.splice(selectedIndex, 1);
        graphsField.remove(selectedIndex);
    }

    function updateGraphs(dialog) {
        initialized = false;
        var sscr = xmin+','+xmax+','+ymin+','+ymax+','+xscl+','+yscl+','+(xscl||yscl)+','+xgrid+','+ygrid;
        picture.sscr = sscr;
        parseShortScript(sscr, width, height);
        var pageId = (dialog.definition.contents[0]).id;
        var graphsField = dialog.getContentElement(pageId, 'graphs');
        for (var i = 0; i < graphsField.items.length; i++) {
            var values = (graphsField.getInputElement().$.options[i].getAttribute('value')).split(',');
            stroke = values[7];
            strokewidth = values[8];
            strokedasharray = values[9];
            for (var j = 0; j < values.length; j++) {
                if (!isNaN(values[j])) {
                    values[j] = Number(values[j]);
                }
            }
            switch(values[0]) {
                case 'func':
                    var functionCall = values[1];
                    plot(functionCall, values[5], values[6], null, null, values[3], values[4]);
                    break;
                case 'polar':
                    var functionCall = ["cos(t)*("+values[1]+")","sin(t)*("+values[1]+")"];
                    plot(functionCall, values[5], values[6], null, null, values[3], values[4]);
                    break;
                case 'param':
                    var functionCall = [values[1], values[2]];
                    plot(functionCall, values[5], values[6], null, null, values[3], values[4]);
                    break;
                case 'slope':
                    slopefield(values[1], values[2], values[2]);
                    plot(null, values[5], values[6], null, null, values[3], values[4]);
                    break;
                case 'label':
                    text([values[5], values[6]], values[1]);
                    break;
            }
        }
    }

    return {
        title: lang.GraphEditor,
        minWidth: 350,
        minHeight: 100,
        contents: [
            {
                id: 'info',
                elements: [
                    {
                        type: 'hbox',
                        widths: ['20%', '20%', '20%', '20%', '20%'],
                        children: [
                            {
                                id: 'equationType',
                                type: 'select',
                                label: lang.EquationType,
                                items: [
                                    [lang.YEqualsFOfX, 'func'],
                                    [lang.REqualsFOfT, 'polar'],
                                    [lang.XEqualsFOfTCommaYEqualsGOfT, 'param'],
                                    [lang.DySplitDxEqualsFOfXAndY, 'slope'],
                                    [lang.Label, 'label']
                                ],
                                default: 'func',
                                onChange: function (api) {
                                    updateFields(this);
                                }
                            },
                            {
                                id: 'equation',
                                type: 'text',
                                label: lang.FOfX,
                                default: lang.XSquared
                            },
                            {
                                id: 'extraField',
                                type: 'text',
                                label: '',
                                default: ''
                            },
                            {
                                id: 'from',
                                type: 'text',
                                label: lang.From,
                                default: '-7.5'
                            },
                            {
                                id: 'to',
                                type: 'text',
                                label: lang.To,
                                default: '7.5'
                            }
                        ]
                    },
                    {
                        type: 'hbox',
                        widths: ['20%', '20%', '20%', '20%', '20%'],
                        children: [
                            {
                                id: 'color',
                                type: 'select',
                                label: lang.Color,
                                items: [
                                    [lang.Black, 'black'],
                                    [lang.Red, 'red'],
                                    [lang.Orange, 'orange'],
                                    [lang.Yellow, 'yellow'],
                                    [lang.Green, 'green'],
                                    [lang.Blue, 'blue'],
                                    [lang.Purple, 'purple']
                                ],
                                default: 'black'
                            },
                            {
                                id: 'width',
                                type: 'select',
                                label: lang.Width,
                                items: [
                                    ['1'],
                                    ['2'],
                                    ['3'],
                                    ['4']
                                ],
                                default: '1'
                            },
                            {
                                id: 'line',
                                type: 'select',
                                label: lang.Line,
                                items: [
                                    [lang.Solid, '0'],
                                    [lang.Dotted, '1'],
                                    [lang.Dashed, '2'],
                                    [lang.TightDash, '3'],
                                    [lang.DashDot, '4']
                                ],
                                default: '0'
                            },
                            {
                                id: 'startWith',
                                type: 'select',
                                label: lang.StartWith,
                                items: [
                                    [lang.None, '0'],
                                    [lang.Arrow, '1'],
                                    [lang.OpenDot, '2'],
                                    [lang.Dot, '3']
                                ],
                                default: '0'
                            },
                            {
                                id: 'endWith',
                                type: 'select',
                                label: lang.EndWith,
                                items: [
                                    [lang.None, '0'],
                                    [lang.Arrow, '1'],
                                    [lang.OpenDot, '2'],
                                    [lang.Dot, '3']
                                ],
                                default: '0'
                            }
                        ]
                    },
                    {
                        type: 'hbox',
                        widths: ['17%', '17%', '16%', '17%', '17%', '16%'],
                        children: [
                            {
                                id: 'xmin',
                                type: 'text',
                                label: lang.XMin,
                                default: '-7.5'
                            },
                            {
                                id: 'xmax',
                                type: 'text',
                                label: lang.XMax,
                                default: '7.5'
                            },
                            {
                                id: 'xscl',
                                type: 'text',
                                label: lang.XScl,
                                default: '1'
                            },
                            {
                                id: 'ymin',
                                type: 'text',
                                label: lang.YMin,
                                default: '-5'
                            },
                            {
                                id: 'ymax',
                                type: 'text',
                                label: lang.YMax,
                                default: '5'
                            },
                            {
                                id: 'yscl',
                                type: 'text',
                                label: lang.YScl,
                                default: '1'
                            }
                        ]
                    },
                    {
                        type: 'hbox',
                        widths: ['20%', '20%', '20%', '20%', '20%'],
                        children: [
                            {
                                type: 'checkbox',
                                id: 'showAxisLabels',
                                label: lang.ShowAxisLabels,
                                default: 'checked',
                                onClick: function() {
                                    if (this.getValue()) {
                                        xscl = 1;
                                        xtick = 1;
                                        yscl = 1;
                                        ytick = 1;
                                    } else {
                                        xscl = null;
                                        xtick = null;
                                        yscl = null;
                                        ytick = null;
                                    }
                                    updateGraphs(this.getDialog());
                                }
                            },
                            {
                                type: 'checkbox',
                                id: 'showXYGrid',
                                label: lang.ShowXYGrid,
                                default: 'checked',
                                onClick: function() {
                                    if (this.getValue()) {
                                        xgrid = 1;
                                        ygrid = 1;
                                    } else {
                                        xgrid = 0;
                                        ygrid = 0;
                                    }
                                    updateGraphs(this.getDialog());
                                }
                            },
                            {
                                id: 'resizeTo',
                                type: 'text',
                                label: lang.ResizeTo,
                                default: defaultwidth
                            },
                            {
                                id: 'by',
                                type: 'text',
                                label: lang.By,
                                default: defaultheight
                            },
                            {
                                type: 'button',
                                id: 'update',
                                label: lang.Update,
                                title: lang.Update,
                                onClick: function() {
                                    var dialog = this.getDialog();
                                    var pageId = (dialog.definition.contents[0]).id;
                                    width = dialog.getValueOf(pageId, 'resizeTo');
                                    height = dialog.getValueOf(pageId, 'by');
                                    updateGraphs(dialog);
                                }
                            }
                        ]
                    },
                    {
                        type: 'hbox',
                        widths: ['20%', '20%', '20%', '20%', '20%'],
                        children: [
                            {
                                type: 'button',
                                id: 'addGraph',
                                label: lang.AddGraph,
                                title: lang.AddGraph,
                                onClick: function() {
                                    addGraph(this.getDialog());
                                }
                            },
                            {
                                id: 'graphs',
                                type: 'select',
                                label: lang.Graphs,
                                items: [],
                                onChange: function (api) {
                                    updateFields(this);
                                }
                            },
                            {
                                type: 'button',
                                id: 'replaceSelectedGraph',
                                label: lang.ReplaceSelectedGraph,
                                title: lang.ReplaceSelectedGraph,
                                onClick: function() {
                                    removeSelectedGraph(this.getDialog());
                                    updateGraphs(this.getDialog());
                                    addGraph(this.getDialog());
                                }
                            },
                            {
                                type: 'button',
                                id: 'remove',
                                label: lang.Remove,
                                title: lang.Remove,
                                onClick: function() {
                                    removeSelectedGraph(this.getDialog());
                                    updateGraphs(this.getDialog());
                                }
                            },
                            {
                                id: 'position',
                                type: 'select',
                                label: lang.Position,
                                items: [
                                    [lang.Top, 'top'],
                                    [lang.Middle, 'middle'],
                                    [lang.Bottom, 'bottom'],
                                    [lang.FloatLeft, 'floatLeft'],
                                    [lang.FloatRight, 'floatRight']
                                ],
                                default: 'middle',
                                onChange: function (api) {
                                    var currentStyle = picture.attributes.style.value;
                                    var regExpPattern = /vertical-align:\s?(\w|-)+/;
                                    var verticalAlignValue = (regExpPattern.exec(currentStyle))[0];
                                    var regExpPattern = /float:\s?\w+/;
                                    var floatValue = (regExpPattern.exec(currentStyle))[0];
                                    switch(this.getValue()) {
                                        case 'top':
                                            currentStyle = currentStyle.replace(verticalAlignValue, 'vertical-align: text-top');
                                            currentStyle = currentStyle.replace(floatValue, 'float: none');
                                            break;
                                        case 'middle':
                                            currentStyle = currentStyle.replace(verticalAlignValue, 'vertical-align: middle');
                                            currentStyle = currentStyle.replace(floatValue, 'float: none');
                                            break;
                                        case 'bottom':
                                            currentStyle = currentStyle.replace(verticalAlignValue, 'vertical-align: text-bottom');
                                            currentStyle = currentStyle.replace(floatValue, 'float: none');
                                            break;
                                        case 'floatLeft':
                                            currentStyle = currentStyle.replace(verticalAlignValue, 'vertical-align: middle');
                                            currentStyle = currentStyle.replace(floatValue, 'float: left');
                                            break;
                                        case 'floatRight':
                                            currentStyle = currentStyle.replace(verticalAlignValue, 'vertical-align: middle');
                                            currentStyle = currentStyle.replace(floatValue, 'float: right');
                                            break;
                                    }
                                    picture.attributes.style.value = currentStyle;
                                }
                            }
                        ]
                    },
                    {
                        id: 'preview',
                        type: 'html',
                        html: '<embed ' +
                            'type="image/svg+xml" ' +
                            'src="' + CKEDITOR.plugins.getPath('asciisvg') + 'd.svg" ' +
                            'style="width:300px; height:200px; ' +
                            'vertical-align:middle; float:none;" sscr="-7.5,7.5,-5,5,1,1,1,1,1,'+defaultwidth+','+defaultheight+'" />',
                        onShow: function( widget ) {
                            xmin = -7.5;
                            xmax = 7.5;
                            ymin = -5;
                            ymax = 5;
                            xscl = 1;
                            yscl = 1;
                            xgrid = 1;
                            ygrid = 1;
                            xtick = 1;
                            ytick = 1;
                            width = defaultwidth;
                            height = defaultheight;
                            drawPics();
                            var dialog = this.getDialog();
                            var pageId = (dialog.definition.contents[0]).id;
                            var fieldsToDefault = ['equationType','equation','color','width','line','startWith','endWith'];
                            for (var i = 0; i < fieldsToDefault.length; i++) {
                                dialog.setValueOf(pageId, fieldsToDefault[i], dialog.getContentElement(pageId, fieldsToDefault[i]).default);
                            }
                            var extraField = dialog.getContentElement(pageId,'extraField');
                            extraField.getElement().hide();
                            var graphsField = dialog.getContentElement(pageId, 'graphs');
                            var graphData = null;
                            graphsField.clear();
                            graphsField.items = [];
                            if (graphData !== editor.getSelection().getSelectedElement()) {
                                graphData = (editor.getSelection().getSelectedElement().$.attributes[0].value).split(',');
                                for (var i = 11; i < graphData.length; i+=10) {
                                    var graphEquation = '';
                                    switch(graphData[i]) {
                                        case 'func':
                                            graphEquation = 'y='+graphData[i+1];
                                            break;
                                        case 'polar':
                                            graphEquation = 'r='+graphData[i+1];
                                            break;
                                        case 'param':
                                            graphEquation = '[x,y]=['+graphData[i+1]+','+graphData[i+2]+']';
                                            break;
                                        case 'slope':
                                            graphEquation = 'dx/dy='+graphData[i+1];
                                            break;
                                        case 'label':
                                            graphEquation = lang.Label+'='+graphData[i+1];
                                            break;
                                    }
                                    graphsField.items.push(graphEquation);
                                    var sscr = graphData[i]+','+graphData[i+1]+','+graphData[i+2]+','+graphData[i+3]+','+graphData[i+4]+','+graphData[i+5]+','+graphData[i+6]+','+graphData[i+7]+','+graphData[i+8]+','+graphData[i+9];
                                    graphsField.add(graphEquation, sscr);
                                }
                                xmin = graphData[0];
                                dialog.setValueOf(pageId, 'xmin', xmin);
                                xmax = graphData[1];
                                dialog.setValueOf(pageId, 'xmax', xmax);
                                ymin = graphData[2];
                                dialog.setValueOf(pageId, 'ymin', ymin);
                                ymax = graphData[3];
                                dialog.setValueOf(pageId, 'ymax', ymax);
                                if (graphData[6] === 'null') {
                                    dialog.setValueOf(pageId, 'showAxisLabels', null);
                                    xscl = null;
                                    xtick = null;
                                    yscl = null;
                                    ytick = null;
                                } else {
                                    xscl = 1;
                                    xtick = 1;
                                    yscl = 1;
                                    ytick = 1;
                                }
                                if ((graphData[7]||graphData[8]) === '0') {
                                    dialog.setValueOf(pageId, 'showXYGrid', null);
                                    xgrid = 0;
                                    ygrid = 0;
                                } else {
                                    xgrid = 1;
                                    ygrid = 1;
                                }
                                width = graphData[9];
                                dialog.setValueOf(pageId, 'resizeTo', width);
                                height = graphData[10];
                                dialog.setValueOf(pageId, 'by', height);
                                updateGraphs(dialog);
                            }
                            else {
                                updateGraphs(dialog);
                                addGraph(dialog);
                            }
                        },
                        commit : function(data)
                        {
                            var dialog = this.getDialog();
                            var pageId = (dialog.definition.contents[0]).id;
                            var previewField = dialog.getContentElement(pageId,this.id);
                            var sscr = xmin+','+xmax+','+ymin+','+ymax+','+xscl+','+yscl+','+(xscl||yscl)+','+xgrid+','+ygrid+','+width+','+height;
                            var graphsField = dialog.getContentElement(pageId, 'graphs');
                            for (var i = 0; i < graphsField.items.length; i++) {
                                sscr += ','+graphsField.getInputElement().$.options[i].getAttribute('value');
                            }
                            picture.sscr = sscr;
                            data.preview = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="'+width+'" height="'+height+'">'+previewField.getInputElement().$.innerHTML+'</svg>';
                        }
                    }
                ]
            }
        ],
        onOk: function( widget ) {
            var data = {};
            this.commitContent(data);
            var currentStyle = picture.attributes.style.value;
            var regExpPattern = /vertical-align:\s?(\w|-)+/;
            var verticalAlignValue = (regExpPattern.exec(currentStyle))[0];
            var regExpPattern = /float:\s?\w+/;
            var floatValue = (regExpPattern.exec(currentStyle))[0];
            var imgElement = '<img src="data:image/svg+xml;base64,'+btoa(data.preview)+'" style="'+verticalAlignValue+';'+floatValue+'" sscr="'+picture.sscr+'" />';
            picture.sscr = '-7.5,7.5,-5,5,1,1,1,1,1,'+defaultwidth+','+defaultheight;
            var element = CKEDITOR.dom.element.createFromHtml(imgElement);
            editor.insertElement(element);
        }
    };
} );
