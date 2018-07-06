describe("multiple destinations", function() {
    var $multiselect,
        $multiselect_to,
        $multiselect_to_2;

    beforeEach(function() {
        var html = '<div class="row">'+
            '    <div class="col-xs-5">'+
            '        <select name="from[]" id="multi_d" class="form-control" size="26" multiple="multiple">'+
            '            <option value="1">C++</option>'+
            '            <option value="2">C#</option>'+
            '            <option value="3">Haskell</option>'+
            '            <option value="4">Java</option>'+
            '            <option value="5">JavaScript</option>'+
            '            <option value="6">Lisp</option>'+
            '            <option value="7">Lua</option>'+
            '            <option value="8">MATLAB</option>'+
            '            <option value="9">NewLISP</option>'+
            '            <option value="10">PHP</option>'+
            '            <option value="11">Perl</option>'+
            '            <option value="12">SQL</option>'+
            '            <option value="13">Unix shell</option>'+
            '        </select>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-2">'+
            '        <button type="button" id="multi_d_rightAll" class="btn btn-default btn-block" style="margin-top: 20px;"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="multi_d_rightSelected" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="multi_d_leftSelected" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="multi_d_leftAll" class="btn btn-default btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '        '+
            '        <hr style="margin: 40px 0 60px;" />'+
            '        '+
            '        <button type="button" id="multi_d_rightAll_2" class="btn btn-default btn-block"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="multi_d_rightSelected_2" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="multi_d_leftSelected_2" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="multi_d_leftAll_2" class="btn btn-default btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-5">'+
            '        <b>Known languages</b>'+
            '        <select name="to[]" id="multi_d_to" class="form-control" size="8" multiple="multiple"></select>'+
            '        '+
            '        <br/><hr/><br/>'+
            '        '+
            '        <b>I want to learn</b>'+
            '        <select name="to_2[]" id="multi_d_to_2" class="form-control" size="8" multiple="multiple"></select>'+
            '    </div>'+
            '</div>';
    
        jasmine.getFixtures().set(html);
        
        $multiselect = $('#multi_d').multiselect({
            right: '#multi_d_to, #multi_d_to_2',
            rightSelected: '#multi_d_rightSelected, #multi_d_rightSelected_2',
            leftSelected: '#multi_d_leftSelected, #multi_d_leftSelected_2',
            rightAll: '#multi_d_rightAll, #multi_d_rightAll_2',
            leftAll: '#multi_d_leftAll, #multi_d_leftAll_2',
     
            search: {
                left: '<input type="text" name="q" class="form-control" placeholder="Search..." />'
            },
     
            moveToRight: function(Multiselect, $options, event, silent, skipStack) {
                var button = $(event.currentTarget).attr('id');
     
                if (button == 'multi_d_rightSelected') {
                    var $left_options = Multiselect.$left.find('> option:selected');
                    Multiselect.$right.eq(0).append($left_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$right.eq(0).find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$right.eq(0));
                    }
                } else if (button == 'multi_d_rightAll') {
                    var $left_options = Multiselect.$left.children(':visible');
                    Multiselect.$right.eq(0).append($left_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$right.eq(0).find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$right.eq(0));
                    }
                } else if (button == 'multi_d_rightSelected_2') {
                    var $left_options = Multiselect.$left.find('> option:selected');
                    Multiselect.$right.eq(1).append($left_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$right.eq(1).find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$right.eq(1));
                    }
                } else if (button == 'multi_d_rightAll_2') {
                    var $left_options = Multiselect.$left.children(':visible');
                    Multiselect.$right.eq(1).append($left_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$right.eq(1).eq(1).find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$right.eq(1));
                    }
                }
            },
     
            moveToLeft: function(Multiselect, $options, event, silent, skipStack) {
                var button = $(event.currentTarget).attr('id');
     
                if (button == 'multi_d_leftSelected') {
                    var $right_options = Multiselect.$right.eq(0).find('> option:selected');
                    Multiselect.$left.append($right_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$left.find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$left);
                    }
                } else if (button == 'multi_d_leftAll') {
                    var $right_options = Multiselect.$right.eq(0).children(':visible');
                    Multiselect.$left.append($right_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$left.find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$left);
                    }
                } else if (button == 'multi_d_leftSelected_2') {
                    var $right_options = Multiselect.$right.eq(1).find('> option:selected');
                    Multiselect.$left.append($right_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$left.find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$left);
                    }
                } else if (button == 'multi_d_leftAll_2') {
                    var $right_options = Multiselect.$right.eq(1).children(':visible');
                    Multiselect.$left.append($right_options);
     
                    if ( typeof Multiselect.callbacks.sort == 'function' && !silent ) {
                        Multiselect.$left.find('> option').sort(Multiselect.callbacks.sort).appendTo(Multiselect.$left);
                    }
                }
            }
        });
        $multiselect_to = $('#multi_d_to');
        $multiselect_to_2 = $('#multi_d_to_2');
    });

    it("multiselect is instantiated and contains options", function() {
        expect($multiselect.attr('id')).toBe('multi_d');
        expect($multiselect.find('option').length).toBe(13);

        expect($multiselect_to.attr('id')).toBe('multi_d_to');
        expect($multiselect_to.find('option').length).toBe(0);

        expect($multiselect_to_2.attr('id')).toBe('multi_d_to_2');
        expect($multiselect_to_2.find('option').length).toBe(0);
    });

    it("move all to right 1", function() {
        // Click move all to right
        $('#multi_d_rightAll').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
        expect($multiselect_to.find('option').length).toBe(13);
    });

    it("move one to right 1", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right 1", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(7);
        expect($multiselect_to.find('option').length).toBe(6);
    });

    it("move all to left 1", function() {
        // Click move all to right
        $('#multi_d_rightAll').trigger('click');

        // Click move all to left
        $('#multi_d_leftAll').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move one to left 1", function() {
        // Click move all to right
        $('#multi_d_rightAll').trigger('click');

        $multiselect_to.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to.find('option').length).toBe(12);
    });

    it("move all selected to left 1", function() {
        // Click move all to right
        $('#multi_d_rightAll').trigger('click');

        $multiselect_to.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(6);
        expect($multiselect_to.find('option').length).toBe(7);
    });





    it("move all to right 2", function() {
        // Click move all to right
        $('#multi_d_rightAll_2').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
        expect($multiselect_to_2.find('option').length).toBe(13);
    });

    it("move one to right 2", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_rightSelected_2').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to_2.find('option').length).toBe(1);
    });

    it("move all selected to right 2", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_rightSelected_2').trigger('click');

        expect($multiselect.find('option').length).toBe(7);
        expect($multiselect_to_2.find('option').length).toBe(6);
    });

    it("move all to left 2", function() {
        // Click move all to right
        $('#multi_d_rightAll_2').trigger('click');

        // Click move all to left
        $('#multi_d_leftAll_2').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to_2.find('option').length).toBe(0);
    });

    it("move one to left 2", function() {
        // Click move all to right
        $('#multi_d_rightAll_2').trigger('click');

        $multiselect_to_2.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_leftSelected_2').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to_2.find('option').length).toBe(12);
    });

    it("move all selected to left 2", function() {
        // Click move all to right
        $('#multi_d_rightAll_2').trigger('click');

        $multiselect_to_2.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_leftSelected_2').trigger('click');

        expect($multiselect.find('option').length).toBe(6);
        expect($multiselect_to_2.find('option').length).toBe(7);
    });

    it("move one to right 1 and one to right 2", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to.find('option').length).toBe(1);
        expect($multiselect_to_2.find('option').length).toBe(0);

        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multi_d_rightSelected_2').trigger('click');

        expect($multiselect.find('option').length).toBe(11);
        expect($multiselect_to.find('option').length).toBe(1);
        expect($multiselect_to_2.find('option').length).toBe(1);
    });
});
