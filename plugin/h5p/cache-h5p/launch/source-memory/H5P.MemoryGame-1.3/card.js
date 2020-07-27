(function (MemoryGame, EventDispatcher, $) {

  /**
   * Controls all the operations for each card.
   *
   * @class H5P.MemoryGame.Card
   * @extends H5P.EventDispatcher
   * @param {Object} image
   * @param {number} id
   * @param {string} alt
   * @param {Object} l10n Localization
   * @param {string} [description]
   * @param {Object} [styles]
   */
  MemoryGame.Card = function (image, id, alt, l10n, description, styles, audio) {
    /** @alias H5P.MemoryGame.Card# */
    var self = this;

    // Initialize event inheritance
    EventDispatcher.call(self);

    var path, width, height, $card, $wrapper, removedState, flippedState, audioPlayer;

    alt = alt || 'Missing description'; // Default for old games

    if (image && image.path) {
      path = H5P.getPath(image.path, id);

      if (image.width !== undefined && image.height !== undefined) {
        if (image.width > image.height) {
          width = '100%';
          height = 'auto';
        }
        else {
          height = '100%';
          width = 'auto';
        }
      }
      else {
        width = height = '100%';
      }
    }

    if (audio) {
      // Check if browser supports audio.
      audioPlayer = document.createElement('audio');
      if (audioPlayer.canPlayType !== undefined) {
        // Add supported source files.
        for (var i = 0; i < audio.length; i++) {
          if (audioPlayer.canPlayType(audio[i].mime)) {
            var source = document.createElement('source');
            source.src = H5P.getPath(audio[i].path, id);
            source.type = audio[i].mime;
            audioPlayer.appendChild(source);
          }
        }
      }

      if (!audioPlayer.children.length) {
        audioPlayer = null; // Not supported
      }
      else {
        audioPlayer.controls = false;
        audioPlayer.preload = 'auto';

        var handlePlaying = function () {
          if ($card) {
            $card.addClass('h5p-memory-audio-playing');
            self.trigger('audioplay');
          }
        };
        var handleStopping = function () {
          if ($card) {
            $card.removeClass('h5p-memory-audio-playing');
            self.trigger('audiostop');
          }
        };
        audioPlayer.addEventListener('play', handlePlaying);
        audioPlayer.addEventListener('ended', handleStopping);
        audioPlayer.addEventListener('pause', handleStopping);
      }
    }

    /**
     * Update the cards label to make it accessible to users with a readspeaker
     *
     * @param {boolean} isMatched The card has been matched
     * @param {boolean} announce Announce the current state of the card
     * @param {boolean} reset Go back to the default label
     */
    self.updateLabel = function (isMatched, announce, reset) {

      // Determine new label from input params
      var label = (reset ? l10n.cardUnturned : alt);
      if (isMatched) {
        label = l10n.cardMatched + ' ' + label;
      }

      // Update the card's label
      $wrapper.attr('aria-label', l10n.cardPrefix.replace('%num', $wrapper.index() + 1) + ' ' + label);

      // Update disabled property
      $wrapper.attr('aria-disabled', reset ? null : 'true');

      // Announce the label change
      if (announce) {
        $wrapper.blur().focus(); // Announce card label
      }
    };

    /**
     * Flip card.
     */
    self.flip = function () {
      if (flippedState) {
        $wrapper.blur().focus(); // Announce card label again
        return;
      }

      $card.addClass('h5p-flipped');
      self.trigger('flip');
      flippedState = true;

      if (audioPlayer) {
        audioPlayer.play();
      }
    };

    /**
     * Flip card back.
     */
    self.flipBack = function () {
      self.stopAudio();
      self.updateLabel(null, null, true); // Reset card label
      $card.removeClass('h5p-flipped');
      flippedState = false;
    };

    /**
     * Remove.
     */
    self.remove = function () {
      $card.addClass('h5p-matched');
      removedState = true;
    };

    /**
     * Reset card to natural state
     */
    self.reset = function () {
      self.stopAudio();
      self.updateLabel(null, null, true); // Reset card label
      flippedState = false;
      removedState = false;
      $card[0].classList.remove('h5p-flipped', 'h5p-matched');
    };

    /**
     * Get card description.
     *
     * @returns {string}
     */
    self.getDescription = function () {
      return description;
    };

    /**
     * Get image clone.
     *
     * @returns {H5P.jQuery}
     */
    self.getImage = function () {
      return $card.find('img').clone();
    };

    /**
     * Append card to the given container.
     *
     * @param {H5P.jQuery} $container
     */
    self.appendTo = function ($container) {
      $wrapper = $('<li class="h5p-memory-wrap" tabindex="-1" role="button"><div class="h5p-memory-card">' +
                  '<div class="h5p-front"' + (styles && styles.front ? styles.front : '') + '>' + (styles && styles.backImage ? '' : '<span></span>') + '</div>' +
                  '<div class="h5p-back"' + (styles && styles.back ? styles.back : '') + '>' +
                    (path ? '<img src="' + path + '" alt="' + alt + '" style="width:' + width + ';height:' + height + '"/>' + (audioPlayer ? '<div class="h5p-memory-audio-button"></div>' : '') : '<i class="h5p-memory-audio-instead-of-image">') +
                  '</div>' +
                '</div></li>')
        .appendTo($container)
        .on('keydown', function (event) {
          switch (event.which) {
            case 13: // Enter
            case 32: // Space
              self.flip();
              event.preventDefault();
              return;
            case 39: // Right
            case 40: // Down
              // Move focus forward
              self.trigger('next');
              event.preventDefault();
              return;
            case 37: // Left
            case 38: // Up
              // Move focus back
              self.trigger('prev');
              event.preventDefault();
              return;
            case 35:
              // Move to last card
              self.trigger('last');
              event.preventDefault();
              return;
            case 36:
              // Move to first card
              self.trigger('first');
              event.preventDefault();
              return;
          }
        });

      $wrapper.attr('aria-label', l10n.cardPrefix.replace('%num', $wrapper.index() + 1) + ' ' + l10n.cardUnturned);
      $card = $wrapper.children('.h5p-memory-card')
        .children('.h5p-front')
          .click(function () {
            self.flip();
          })
          .end();

      if (audioPlayer) {
        $card.children('.h5p-back')
          .click(function () {
            if ($card.hasClass('h5p-memory-audio-playing')) {
              self.stopAudio();
            }
            else {
              audioPlayer.play();
            }
          })
      }
    };

    /**
     * Re-append to parent container.
     */
    self.reAppend = function () {
      var parent = $wrapper[0].parentElement;
      parent.appendChild($wrapper[0]);
    };

    /**
     * Make the card accessible when tabbing
     */
    self.makeTabbable = function () {
      if ($wrapper) {
        $wrapper.attr('tabindex', '0');
      }
    };

    /**
     * Prevent tabbing to the card
     */
    self.makeUntabbable = function () {
      if ($wrapper) {
        $wrapper.attr('tabindex', '-1');
      }
    };

    /**
     * Make card tabbable and move focus to it
     */
    self.setFocus = function () {
      self.makeTabbable();
      if ($wrapper) {
        $wrapper.focus();
      }
    };

    /**
     * Check if the card has been removed from the game, i.e. if has
     * been matched.
     */
    self.isRemoved = function () {
      return removedState;
    };

    /**
     * Stop any audio track that might be playing.
     */
    self.stopAudio = function () {
      if (audioPlayer) {
        audioPlayer.pause();
        audioPlayer.currentTime = 0;
      }
    };
  };

  // Extends the event dispatcher
  MemoryGame.Card.prototype = Object.create(EventDispatcher.prototype);
  MemoryGame.Card.prototype.constructor = MemoryGame.Card;

  /**
   * Check to see if the given object corresponds with the semantics for
   * a memory game card.
   *
   * @param {object} params
   * @returns {boolean}
   */
  MemoryGame.Card.isValid = function (params) {
    return (params !== undefined &&
             (params.image !== undefined &&
             params.image.path !== undefined) ||
           params.audio);
  };

  /**
   * Checks to see if the card parameters should create cards with different
   * images.
   *
   * @param {object} params
   * @returns {boolean}
   */
  MemoryGame.Card.hasTwoImages = function (params) {
    return (params !== undefined &&
             (params.match !== undefined &&
              params.match.path !== undefined) ||
           params.matchAudio);
  };

  /**
   * Determines the theme for how the cards should look
   *
   * @param {string} color The base color selected
   * @param {number} invertShades Factor used to invert shades in case of bad contrast
   */
  MemoryGame.Card.determineStyles = function (color, invertShades, backImage) {
    var styles =  {
      front: '',
      back: '',
      backImage: !!backImage
    };

    // Create color theme
    if (color) {
      var frontColor = shade(color, 43.75 * invertShades);
      var backColor = shade(color, 56.25 * invertShades);

      styles.front += 'color:' + color + ';' +
                      'background-color:' + frontColor + ';' +
                      'border-color:' + frontColor +';';
      styles.back += 'color:' + color + ';' +
                     'background-color:' + backColor + ';' +
                     'border-color:' + frontColor +';';
    }

    // Add back image for card
    if (backImage) {
      var backgroundImage = 'background-image:url(' + backImage + ')';

      styles.front += backgroundImage;
      styles.back += backgroundImage;
    }

    // Prep style attribute
    if (styles.front) {
      styles.front = ' style="' + styles.front + '"';
    }
    if (styles.back) {
      styles.back = ' style="' + styles.back + '"';
    }

    return styles;
  };

  /**
   * Convert hex color into shade depending on given percent
   *
   * @private
   * @param {string} color
   * @param {number} percent
   * @return {string} new color
   */
  var shade = function (color, percent) {
    var newColor = '#';

    // Determine if we should lighten or darken
    var max = (percent < 0 ? 0 : 255);

    // Always stay positive
    if (percent < 0) {
      percent *= -1;
    }
    percent /= 100;

    for (var i = 1; i < 6; i += 2) {
      // Grab channel and convert from hex to dec
      var channel = parseInt(color.substr(i, 2), 16);

      // Calculate new shade and convert back to hex
      channel = (Math.round((max - channel) * percent) + channel).toString(16);

      // Make sure to always use two digits
      newColor += (channel.length < 2 ? '0' + channel : channel);
    }

    return newColor;
  };

})(H5P.MemoryGame, H5P.EventDispatcher, H5P.jQuery);
