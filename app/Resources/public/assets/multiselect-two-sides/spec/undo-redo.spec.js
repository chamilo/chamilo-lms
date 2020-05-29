describe("undo redo", function() {
    var $multiselect,
        $multiselect_to;

    beforeEach(function() {
        var html = '<div class="row">'+
            '    <div class="col-xs-5">'+
            '        <select name="from[]" id="undo_redo" class="form-control" size="13" multiple="multiple">'+
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
            '        <button type="button" id="undo_redo_undo" class="btn btn-primary btn-block">undo</button>'+
            '        <button type="button" id="undo_redo_rightAll" class="btn btn-default btn-block"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="undo_redo_rightSelected" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="undo_redo_leftSelected" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="undo_redo_leftAll" class="btn btn-default btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '        <button type="button" id="undo_redo_redo" class="btn btn-warning btn-block">redo</button>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-5">'+
            '        <select name="to[]" id="undo_redo_to" class="form-control" size="13" multiple="multiple"></select>'+
            '    </div>'+
            '</div>';
    
        jasmine.getFixtures().set(html);
        
        $multiselect = $('#undo_redo').multiselect();
        $multiselect_to = $('#undo_redo_to');
    });

    it("multiselect is instantiated and contains options", function() {
        expect($multiselect.attr('id')).toBe('undo_redo');
        expect($multiselect.find('option').length).toBe(13);

        expect($multiselect_to.attr('id')).toBe('undo_redo_to');
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move all to right", function() {
        // Click move all to right
        $('#undo_redo_rightAll').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
        expect($multiselect_to.find('option').length).toBe(13);
    });

    it("move one to right", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(7);
        expect($multiselect_to.find('option').length).toBe(6);
    });

    it("move all to left", function() {
        // Click move all to right
        $('#undo_redo_rightAll').trigger('click');

        // Click move all to left
        $('#undo_redo_leftAll').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move one to left", function() {
        // Click move all to right
        $('#undo_redo_rightAll').trigger('click');

        $multiselect_to.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to.find('option').length).toBe(12);
    });

    it("move all selected to left", function() {
        // Click move all to right
        $('#undo_redo_rightAll').trigger('click');

        $multiselect_to.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(6);
        expect($multiselect_to.find('option').length).toBe(7);
    });

    it("move one to right then undo", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to.find('option').length).toBe(1);

        // Click undo
        $('#undo_redo_undo').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move one to right then undo then redo", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to.find('option').length).toBe(1);

        // Click undo
        $('#undo_redo_undo').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to.find('option').length).toBe(0);

        // Click redo
        $('#undo_redo_redo').trigger('click');

        expect($multiselect.find('option').length).toBe(12);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right then undo", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(7);
        expect($multiselect_to.find('option').length).toBe(6);

        // Click undo
        $('#undo_redo_undo').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move all selected to right then undo then redo", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#undo_redo_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(7);
        expect($multiselect_to.find('option').length).toBe(6);

        // Click undo
        $('#undo_redo_undo').trigger('click');

        expect($multiselect.find('option').length).toBe(13);
        expect($multiselect_to.find('option').length).toBe(0);

        // Click redo
        $('#undo_redo_redo').trigger('click');

        expect($multiselect.find('option').length).toBe(7);
        expect($multiselect_to.find('option').length).toBe(6);
    });
});
