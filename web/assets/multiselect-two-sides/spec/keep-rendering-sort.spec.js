describe("keep rendering sort", function() {
    var $multiselect,
        $multiselect_to;

    beforeEach(function() {
        var html = '<div class="row">'+
            '    <div class="col-xs-5">'+
            '        <select name="from[]" id="keepRenderingSort" class="form-control" size="8" multiple="multiple">'+
            '            <option value="1">Item 1</option>'+
            '            <option value="2">Item 5</option>'+
            '            <option value="2">Item 2</option>'+
            '            <option value="2">Item 4</option>'+
            '            <option value="3">Item 3</option>'+
            '        </select>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-2">'+
            '        <button type="button" id="keepRenderingSort_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="keepRenderingSort_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="keepRenderingSort_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="keepRenderingSort_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-5">'+
            '        <select name="to[]" id="keepRenderingSort_to" class="form-control" size="8" multiple="multiple"></select>'+
            '    </div>'+
            '</div>';
    
        jasmine.getFixtures().set(html);
        
        $multiselect = $('#keepRenderingSort').multiselect({
            keepRenderingSort: true
        });
        $multiselect_to = $('#keepRenderingSort_to');
    });

    it("multiselect is instantiated and contains options", function() {
        expect($multiselect.attr('id')).toBe('keepRenderingSort');
        expect($multiselect.find('option').length).toBe(5);

        expect($multiselect_to.attr('id')).toBe('keepRenderingSort_to');
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move all to right", function() {
        // Click move all to right
        $('#keepRenderingSort_rightAll').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
        expect($multiselect_to.find('option').length).toBe(5);
    });

    it("move one to right", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#keepRenderingSort_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(4);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#keepRenderingSort_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(3);
        expect($multiselect_to.find('option').length).toBe(2);
    });

    it("move all to left", function() {
        // Click move all to right
        $('#keepRenderingSort_rightAll').trigger('click');

        // Click move all to left
        $('#keepRenderingSort_leftAll').trigger('click');

        expect($multiselect.find('option').length).toBe(5);
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move one to left", function() {
        // Click move all to right
        $('#keepRenderingSort_rightAll').trigger('click');

        $multiselect_to.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#keepRenderingSort_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to.find('option').length).toBe(4);
    });

    it("move all selected to left", function() {
        // Click move all to right
        $('#keepRenderingSort_rightAll').trigger('click');

        $multiselect_to.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#keepRenderingSort_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(2);
        expect($multiselect_to.find('option').length).toBe(3);
    });

    it("options in the left are displayed in the way they were rendered", function() {
        expect($multiselect.find('option:eq(0)').text()).toBe('Item 1');
        expect($multiselect.find('option:eq(1)').text()).toBe('Item 5');
        expect($multiselect.find('option:eq(2)').text()).toBe('Item 2');
        expect($multiselect.find('option:eq(3)').text()).toBe('Item 4');
        expect($multiselect.find('option:eq(4)').text()).toBe('Item 3');
    });

    it("options in the right are displayed in the way they were rendered", function() {
        // Click move all to right
        $('#keepRenderingSort_rightAll').trigger('click');

        expect($multiselect_to.find('option:eq(0)').text()).toBe('Item 1');
        expect($multiselect_to.find('option:eq(1)').text()).toBe('Item 5');
        expect($multiselect_to.find('option:eq(2)').text()).toBe('Item 2');
        expect($multiselect_to.find('option:eq(3)').text()).toBe('Item 4');
        expect($multiselect_to.find('option:eq(4)').text()).toBe('Item 3');
    });
});
