/**
 * RadioGroup widget
 *
 * @param {H5P.jQuery} $
 */
H5PEditor.RadioGroup = H5PEditor.widgets.radioGroup = (function ($) {

  var groupCounter = 0;
  /**
   * Creates an radio button group.
   *
   * @class H5PEditor.ImageRadioButtonGroup
   * @param {Object} parent
   * @param {Object} field
   * @param {Object} params
   * @param {function} setValue
   */
  function RadioGroup(parent, field, params, setValue) {
    this.parent = parent;
    this.field = field;
    this.value = params;
    this.setValue = setValue;

    this.alignment = this.field.alignment || 'vertical';

    // Setup event dispatching on change
    this.changes = [];
    this.triggerListeners = function () {
      // Run callbacks
      for (var i = 0; i < this.changes.length; i++) {
        this.changes[i](this.value);
      }
    };

    groupCounter++;
  }

  /**
   * Append the field to the wrapper.
   * @public
   * @param {H5P.jQuery} $wrapper
   */
  RadioGroup.prototype.appendTo = function ($wrapper) {
    var self = this;

    var buttons = [];

    var toggleSelected = function ($selectedInput) {
      buttons.forEach(function ($button) {
        $button.removeClass('checked');
      });

      $selectedInput.parent().addClass('checked');
    };

    self.$container = $(H5PEditor.createFieldMarkup(
      self.field,
      '<div class="h5p-editor-radio-group-container ' + self.alignment + '" role="radiogroup"></div>'
    ));

    var $buttonGroup = self.$container.find('.h5p-editor-radio-group-container');

    for (var i = 0; i < self.field.options.length; i++) {
      var option = self.field.options[i];
      var inputId = 'h5p-editor-radio-group-button-' + groupCounter + '-' + i;
      var isChecked = (self.value === option.value) ||
        (self.value === undefined && this.field.default === option.value);

      var $button = $('<div>', {
        'class': 'h5p-editor-radio-group-button ' + option.value + (isChecked ? ' checked' : '')
      }).appendTo($buttonGroup);
      buttons.push($button);

      $('<input>', {
        type: 'radio',
        name: self.field.name + groupCounter,
        value: option.value,
        role: 'radio',
        id: inputId,
        checked: isChecked,
        change: function () {
          toggleSelected($(this));
          self.value = $('input:checked', $buttonGroup).val();
          self.setValue(self.field, self.value);
          self.triggerListeners();
        }
      }).appendTo($button);

      $('<label>', {
        'for': inputId
      }).append($('<span>', {
        html: option.label
      })).appendTo($button);

      if (option.description) {
        $('<div>', {
          'class': 'h5p-option-description',
          html: option.description
        }).appendTo($button);
      }
    }

    self.$container.appendTo($wrapper);
  };


  /**
   * Validate the current values.
   */
  RadioGroup.prototype.validate = function () {
    return true;
  };

  RadioGroup.prototype.remove = function () {};

  return RadioGroup;
})(H5P.jQuery);
