describe("with search", function() {
    var $multiselect,
        $multiselect_to;

    beforeEach(function() {
        var html = '<div class="row">'+
            '    <div class="col-xs-5">'+
            '        <select name="from[]" id="search" class="form-control" size="8" multiple="multiple">'+
            '            <option value="1">Item 1</option>'+
            '            <option value="2">Item 5</option>'+
            '            <option value="2">Item 2</option>'+
            '            <option value="2">Item 4</option>'+
            '            <option value="3">Item 3</option>'+
            '        </select>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-2">'+
            '        <button type="button" id="search_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="search_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="search_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="search_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-5">'+
            '        <select name="to[]" id="search_to" class="form-control" size="8" multiple="multiple"></select>'+
            '    </div>'+
            '</div>';
    
        jasmine.getFixtures().set(html);
        
        $multiselect = $('#search').multiselect({
            search: {
                left: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
                right: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
            },
            fireSearch: function(value) {
                return value.length > 3;
            }
        });
        $multiselect_to = $('#search_to');
    });

    it("multiselect is instantiated and contains options", function() {
        expect($multiselect.attr('id')).toBe('search');
        expect($multiselect.find('option').length).toBe(5);

        expect($multiselect_to.attr('id')).toBe('search_to');
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move all to right", function() {
        // Click move all to right
        $('#search_rightAll').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
        expect($multiselect_to.find('option').length).toBe(5);
    });

    it("move one to right", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#search_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(4);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#search_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(3);
        expect($multiselect_to.find('option').length).toBe(2);
    });

    it("move all to left", function() {
        // Click move all to right
        $('#search_rightAll').trigger('click');

        // Click move all to left
        $('#search_leftAll').trigger('click');

        expect($multiselect.find('option').length).toBe(5);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move one to left", function() {
        // Click move all to right
        $('#search_rightAll').trigger('click');

        $multiselect_to.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#search_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to.find('option').length).toBe(4);
    });

    it("move all selected to left", function() {
        // Click move all to right
        $('#search_rightAll').trigger('click');

        $multiselect_to.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#search_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(2);
        expect($multiselect_to.find('option').length).toBe(3);
    });

    it("search on the left side", function() {
        // Search for "Item 1"
        $('#search').prev('[name="q"]').val('Item 1').trigger('keyup');

        expect($multiselect.find('option:visible').length).toBe(1);

        // Search for "Item"
        $('#search').prev('[name="q"]').val('Item').trigger('keyup');

        expect($multiselect.find('option:visible').length).toBe(5);
    });

    it("search on the right side", function() {
        // Click move all to right
        $('#search_rightAll').trigger('click');

        // Search for "Item 1"
        $('#search_to').prev('[name="q"]').val('Item 1').trigger('keyup');

        expect($multiselect_to.find('option:visible').length).toBe(1);

        // Search for "Item"
        $('#search_to').prev('[name="q"]').val('Item').trigger('keyup');

        expect($multiselect_to.find('option:visible').length).toBe(5);
    });
});
