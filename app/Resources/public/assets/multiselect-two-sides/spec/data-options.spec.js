describe("data options", function() {
    var $multiselect,
        $multiselect_to;

    beforeEach(function() {
        var html = '<div class="row">'+
            '    <div class="col-xs-5">'+
            '        <select name="from[]" class="multiselect form-control" size="8" multiple="multiple" data-right="#multiselect_to_1" data-right-all="#right_All_1" data-right-selected="#right_Selected_1" data-left-all="#left_All_1" data-left-selected="#left_Selected_1">'+
            '            <option value="1">Item 1</option>'+
            '            <option value="2">Item 5</option>'+
            '            <option value="2">Item 2</option>'+
            '            <option value="2">Item 4</option>'+
            '            <option value="3">Item 3</option>'+
            '        </select>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-2">'+
            '        <button type="button" id="right_All_1" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="right_Selected_1" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="left_Selected_1" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="left_All_1" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-5">'+
            '        <select name="to[]" id="multiselect_to_1" class="form-control" size="8" multiple="multiple"></select>'+
            '    </div>'+
            '</div>';
    
        jasmine.getFixtures().set(html);
        
        $multiselect = $('.multiselect').multiselect();
        $multiselect_to = $('#multiselect_to_1');
    });

    it("multiselect is instantiated and contains options", function() {
        expect($multiselect.hasClass('multiselect')).toBe(true);
        expect($multiselect.find('option').length).toBe(5);

        expect($multiselect_to.attr('id')).toBe('multiselect_to_1');
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move all to right", function() {
        // Click move all to right
        $('#right_All_1').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
        expect($multiselect_to.find('option').length).toBe(5);
    });

    it("move one to right", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#right_Selected_1').trigger('click');

        expect($multiselect.find('option').length).toBe(4);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#right_Selected_1').trigger('click');

        expect($multiselect.find('option').length).toBe(3);
        expect($multiselect_to.find('option').length).toBe(2);
    });

    it("move all to left", function() {
        // Click move all to right
        $('#right_All_1').trigger('click');

        // Click move all to left
        $('#left_All_1').trigger('click');

        expect($multiselect.find('option').length).toBe(5);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move one to left", function() {
        // Click move all to right
        $('#right_All_1').trigger('click');

        $multiselect_to.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#left_Selected_1').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to.find('option').length).toBe(4);
    });

    it("move all selected to left", function() {
        // Click move all to right
        $('#right_All_1').trigger('click');

        $multiselect_to.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#left_Selected_1').trigger('click');

        expect($multiselect.find('option').length).toBe(2);
        expect($multiselect_to.find('option').length).toBe(3);
    });
});
