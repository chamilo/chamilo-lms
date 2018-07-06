if (typeof window === 'undefined') {
    var jsdom = require("jsdom");
    global.document = jsdom.jsdom();
    global.window = global.document.defaultView;
    var navigator = {userAgent: "node-js", platform: "Linux i686"};
    global.window.navigator = global.navigator = navigator;
    navigator.platform = "Linux i686";
    global.jQuery = global.$ = require("jquery");
}

describe("zero configuration", function() {
    var $multiselect,
        $multiselect_to;

    beforeEach(function() {
        var html = '<div class="row">'+
            '    <div class="col-xs-5">'+
            '        <select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple">'+
            '            <option value="1">Item 1</option>'+
            '            <option value="2">Item 5</option>'+
            '            <option value="2">Item 2</option>'+
            '            <option value="2">Item 4</option>'+
            '            <option value="3">Item 3</option>'+
            '        </select>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-2">'+
            '        <button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>'+
            '        <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'+
            '        <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'+
            '        <button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>'+
            '    </div>'+
            '    '+
            '    <div class="col-xs-5">'+
            '        <select name="to[]" id="multiselect_to" class="form-control" size="8" multiple="multiple"></select>'+
            '    </div>'+
            '</div>';

        jasmine.getFixtures().set(html);
        
        $multiselect = $('#multiselect').multiselect();
        $multiselect_to = $('#multiselect_to');
    });

    it("multiselect is instantiated and contains options", function() {
        expect($multiselect.attr('id')).toBe('multiselect');
        expect($multiselect.find('option').length).toBe(5);

        expect($multiselect_to.attr('id')).toBe('multiselect_to');
        expect($multiselect_to.find('option').length).toBe(0);
    });

    it("move all to right", function() {
        // Click move all to right
        $('#multiselect_rightAll').trigger('click');

        expect($multiselect.find('option').length).toBe(0);
    });

    it("move one to right", function() {
        $multiselect.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multiselect_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(4);
        expect($multiselect_to.find('option').length).toBe(1);
    });

    it("move all selected to right", function() {
        $multiselect.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#multiselect_rightSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(3);
        expect($multiselect_to.find('option').length).toBe(2);
    });

    it("move all to left", function() {
        // Click move all to right
        $('#multiselect_rightAll').trigger('click');

        // Click move all to left
        $('#multiselect_leftAll').trigger('click');

        expect($multiselect.find('option').length).toBe(5);
    });

    it("move one to left", function() {
        // Click move all to right
        $('#multiselect_rightAll').trigger('click');

        $multiselect_to.find('option:eq(0)').attr('selected', true);

        // Click move selected to right
        $('#multiselect_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(1);
        expect($multiselect_to.find('option').length).toBe(4);
    });

    it("move all selected to left", function() {
        // Click move all to right
        $('#multiselect_rightAll').trigger('click');

        $multiselect_to.find('option:nth-child(2n)').attr('selected', true);

        // Click move selected to right
        $('#multiselect_leftSelected').trigger('click');

        expect($multiselect.find('option').length).toBe(2);
        expect($multiselect_to.find('option').length).toBe(3);
    });
});
