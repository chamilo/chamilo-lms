/*global sinon, window*/
(function( $ ) {
	/*
		======== A Handy Little QUnit Reference ========
		http://docs.jquery.com/QUnit

		Test methods:
			expect(numAssertions)
			stop(increment)
			start(decrement)
		Test assertions:
			ok(value, [message])
			equal(actual, expected, [message])
			notEqual(actual, expected, [message])
			deepEqual(actual, expected, [message])
			notDeepEqual(actual, expected, [message])
			strictEqual(actual, expected, [message])
			notStrictEqual(actual, expected, [message])
			raises(block, [expected], [message])
	*/

module( "ajaxQueue", {
	setup: function() {
	},
	teardown: function() {
	}
});

test( "OK", function() {
	expect( 1 );
	ok( true );
});


}(jQuery));
