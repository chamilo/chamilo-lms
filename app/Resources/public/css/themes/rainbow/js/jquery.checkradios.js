/*
	CHECKRADIOS - jQuery plugin to allow custom styling of checkboxes
	by Chris McGuckin (https://github.com/cosmicwheels)
	
	
	License: MIT (http://opensource.org/licenses/MIT)
	
	
	Credits:
	---------------------------------------------------------------------------------
	
	icomoon (http://icomoon.io/)
	
	Please refer to the icomoon website for the license regarding their icons.
	
	---------------------------------------------------------------------------------
	
	Fontawesome (http://fortawesome.github.io/Font-Awesome/)
	
	Please refer to the fontawesome website for the license regarding their icons.
	
	---------------------------------------------------------------------------------
	
	Stephan Richter (https://github.com/strichter)
	
	Thanks to Stephan for pointing out and helping to add the triggering of events 
	to help further mimic the checkboxes and radios as well as providing other
	important bug fixes.
	
	---------------------------------------------------------------------------------
	---------------------------------------------------------------------------------
	

*/
(function ($) {


	$.fn.checkradios = function (options) {

		var $elements = this;

		//Default Settings
		var settings = $.extend({

			checkbox: {

				iconClass: 'icon-checkradios-checkmark'

			},

			radio: {

				iconClass: 'icon-checkradios-circle'

			},


			onChange: function (checked, $element, $realElement) {}

		}, options);


		// Functionality
		var form = {

			checkbox: function ($checkbox) {
				

				var THIS = this;

				//Make sure checkradios isnt already in use
				if (!$checkbox.parent().hasClass('checkradios-checkbox')) {

					//Get the elements classes
					var classes = $checkbox.attr('class');

					if (classes === undefined) {

						classes = '';

					}

					//Wrap the input
					var $item = $checkbox.wrap('<div class="checkradios-checkbox ' + classes + '"/>');

					//Get the new checkbox
					var $holder = $item.parent();

					//Check if box is checked
					if ($item.is(':checked')) {

						THIS.checkboxEnable($item);

					} else {

						THIS.checkboxDisable($item);

					}

					//Add keyboard support
					$checkbox.keypress(function (e) {

						var key = e.keyCode;

						//On enter/return or space
						if ((key < 1) || (key == 13) || (key == 32)) {

							$holder.click();

						}

					});


					//Add tabbing support
					$checkbox.on({

						focusin: function () {

							$holder.addClass('focus');

						},

						focusout: function () {

							var $this = $(this);

							setTimeout(function () {

								if (!$this.is(':focus')) {

									$holder.removeClass('focus');

								}

							}, 10);

						}
					});


					$holder.mousedown(function () {

						setTimeout(function () {

							//Add focus
							$checkbox.focus();

						}, 10);

					});


					//Disable usual click functionality
					$checkbox.click(function (e) {

						e.stopPropagation();
						e.preventDefault();

					});


					//On button click
					$holder.click(function () {

						//Add check
						if ($item.is(':checked')) {

							THIS.checkboxDisable($item);

							//Callback
							settings.onChange(false, $holder, $item);

						} else {

							THIS.checkboxEnable($item);

							//Callback
							settings.onChange(true, $holder, $item);

						}

						return false;

					});

				}

			},


			radio: function ($radio) {

				var THIS = this;

				//Make sure checkradios isnt already in use
				if (!$radio.parent().hasClass('checkradios-radio')) {

					//Get the elements classes
					var classes = $radio.attr('class');

					if (classes === undefined) {

						classes = '';

					}

					//Wrap the input
					var $item = $radio.wrap('<div class="checkradios-radio ' + classes + '"/>');

					//Get the new checkbox
					var $holder = $item.parent();

					//Check if box is checked
					if ($item.is(':checked')) {

						THIS.radioEnable($item);

					} else {

						THIS.radioDisable($item);

					}

					//Add tabbing suppot
					$radio.on({
						
						focusin: function () {
							
							//Add focus class
							$holder.addClass('focus');
							
							
							THIS.radioEnable($item);

							//Get group Name
							var radio_name = $item.attr('name');

							var $group = $('input[name="' + radio_name + '"]');

							//Set checked/unchecked for each element in group
							$group.each(function () {
								
								if ($(this).is(':checked')) {
									
									THIS.radioEnable($(this));
									//Callback
									settings.onChange(true, $(this).parent(), $(this));
									
								} else {
									
									THIS.radioDisable($(this));
									//Callback
									settings.onChange(false, $(this).parent(), $(this));
									
								}

							});

						},

						focusout: function () {
							
							var $this = $(this);

							setTimeout(function () {
								
								if (!$this.is(':focus')) {
									
									$holder.removeClass('focus');
									
								}
								
							}, 10);
							
						}
					});

					$holder.click(function () {
						
						//Add focus
						$radio.focus();
						
					});

					//Disable usual click functionality
					$radio.click(function (e) {
						
						e.stopPropagation();
						e.preventDefault();
						
					});

				}
			},

			/*Private*/

			checkboxEnable: function ($checkbox) {
				
				$checkbox.parent().removeClass(settings.checkbox.iconClass);
				$checkbox.parent().removeClass('unchecked');

				$checkbox.parent().addClass(settings.checkbox.iconClass);
				$checkbox.parent().addClass('checked');
				$checkbox.prop('checked', true).trigger('change');
				
					
			},

			checkboxDisable: function ($checkbox) {
				
				$checkbox.parent().removeClass(settings.checkbox.iconClass);
				$checkbox.parent().addClass('unchecked');
				$checkbox.prop('checked', false).trigger('change');
				
				
			},

			radioEnable: function ($radio) {
				
				$radio.parent().removeClass(settings.radio.iconClass);
				$radio.parent().removeClass('unchecked');

				$radio.parent().addClass(settings.radio.iconClass);
				$radio.parent().addClass('checked');
				$radio.prop('checked', true).trigger('change');
				
				
					
			},

			radioDisable: function ($radio) {
				
				$radio.parent().removeClass('checked');
				$radio.parent().removeClass(settings.radio.iconClass);
				$radio.parent().addClass('unchecked');
				$radio.prop('checked', false).trigger('change');
				
				
			}
		};

		//Loop through elements
		$elements.each(function (i, val) {

			var $this = $(this);

			//Check for checkbox
			if ($this.is("input[type=checkbox]")) {
				
				//Setup checkboxes
				form.checkbox($this);
				
			}

			//Check for radio
			if ($this.is("input[type=radio]")) {
				
				//Setup checkboxes
				form.radio($this);
				
			}

			return this;
		});

	};

}(jQuery));