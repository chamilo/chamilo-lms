var H5P = H5P || {};

/**
 * H5P-Text Utilities
 *
 * Some functions that can be useful when dealing with texts in H5P.
 *
 * @param {H5P.jQuery} $
 */
H5P.TextUtilities = function () {
  'use strict';
  /**
   * Create Text Utilities.
   *
   * Might be needed later.
   *
   * @constructor
   */
  function TextUtilities () {
  }

  // Inheritance
  TextUtilities.prototype = Object.create(H5P.EventDispatcher.prototype);
  TextUtilities.prototype.constructor = TextUtilities;

  /** @constant {object} */
  TextUtilities.WORD_DELIMITER = /[\s.?!,\';\"]/g;

  /**
   * Check if a candidate string is considered isolated (in a larger string) by
   * checking the symbol before and after the candidate.
   *
   * @param {string} candidate - String to be looked for.
   * @param {string} text - (Larger) string that should contain candidate.
   * @param {object} params - Parameters.
   * @param {object} params.delimiter - Regular expression containing symbols used to isolate the candidate.
   * @return {boolean} True if string is isolated.
   */
  TextUtilities.isIsolated = function (candidate, text, params) {
    // Sanitization
    if (!candidate || !text) {
      return;
    }
    var delimiter = (!!params && !!params.delimiter) ? params.delimiter : TextUtilities.WORD_DELIMITER;

    var pos = (!!params && !!params.index && typeof params.index === 'number') ? params.index : text.indexOf(candidate);
    if (pos < 0 || pos > text.length-1) {
      return false;
    }

    var pred = (pos === 0 ? '' : text[pos - 1].replace(delimiter, ''));
    var succ = (pos + candidate.length === text.length ? '' : text[pos + candidate.length].replace(delimiter, ''));

    if (pred !== '' || succ !== '') {
      return false;
    }
    return true;
  };

  /**
   * Check whether two strings are considered to be similar.
   * The similarity is temporarily computed by word length and number of number of operations
   * required to change one word into the other (Damerau-Levenshtein). It's subject to
   * change, cmp. https://github.com/otacke/udacity-machine-learning-engineer/blob/master/submissions/capstone_proposals/h5p_fuzzy_blanks.md
   *
   * @param {String} string1 - String #1.
   * @param {String} string2 - String #2.
   * @param {object} params - Parameters.
   * @return {boolean} True, if strings are considered to be similar.
   */
  TextUtilities.areSimilar = function (string1, string2) {
    // Sanitization
    if (!string1 || typeof string1 !== 'string') {
      return;
    }
    if (!string2 || typeof string2 !== 'string') {
      return;
    }

    // Just temporariliy this unflexible. Will be configurable via params.
    var length = Math.min(string1.length, string2.length);
    var levenshtein = H5P.TextUtilities.computeLevenshteinDistance(string1, string2, true);
    if (levenshtein === 0) {
      return true;
    }
    if ((length > 9) && (levenshtein <= 2)) {
      return true;
    }
    if ((length > 3) && (levenshtein <= 1)) {
      return true;
    }
    return false;
  };

  /**
   * Compute the (Damerau-)Levenshtein distance for two strings.
   *
   * The (Damerau-)Levenshtein distance that is returned is equivalent to the
   * number of operations that are necessary to transform one string into the
   * other. Consequently, lower numbers indicate higher similarity between the
   * two strings.
   *
   * While the Levenshtein distance counts deletions, insertions and mismatches,
   * the Damerau-Levenshtein distance also counts swapping two characters as
   * only one operation (instead of two mismatches), because this seems to
   * happen quite often.
   *
   * See http://en.wikipedia.org/wiki/Damerau%E2%80%93Levenshtein_distance for details
   *
   * @public
   * @param {string} str1 - String no. 1.
   * @param {string} str2 - String no. 2.
   * @param {boolean} [countSwapping=false] - If true, swapping chars will count as operation.
   * @returns {number} Distance.
   */
  TextUtilities.computeLevenshteinDistance = function(str1, str2, countSwapping) {
    // sanity checks
    if (typeof str1 !== 'string' || typeof str2 !== 'string') {
      return undefined;
    }
    if (countSwapping && typeof countSwapping !== 'boolean') {
      countSwapping = false;
    }

    // degenerate cases
    if (str1 === str2) {
      return 0;
    }
    if (str1.length === 0) {
      return str2.length;
    }
    if (str2.length === 0) {
      return str1.length;
    }

    // counter variables
    var i, j;

    // indicates characters that don't match
    var cost;

    // matrix for storing distances
    var distance = [];

    // initialization
    for (i = 0; i <= str1.length; i++) {
      distance[i] = [i];
    }
    for (j = 0; j <= str2.length; j++) {
      distance[0][j] = j;
    }

    // computation
    for (i = 1; i <= str1.length; i++) {
      for (j = 1; j <= str2.length; j++) {
        cost = (str1[i-1] === str2[j-1]) ? 0 : 1;
        distance[i][j] = Math.min(
          distance[i-1][j] + 1,     // deletion
          distance[i][j-1] + 1,     // insertion
          distance[i-1][j-1] + cost // mismatch
        );
        // in Damerau-Levenshtein distance, transpositions are operations
        if (countSwapping) {
          if (i > 1 && j > 1 && str1[i-1] === str2[j-2] && str1[i-2] === str2[j-1]) {
            distance[i][j] = Math.min(distance[i][j], distance[i-2][j-2] + cost);
          }
        }
      }
    }
    return distance[str1.length][str2.length];
  };

  /**
   * Compute the Jaro(-Winkler) distance for two strings.
   *
   * The Jaro(-Winkler) distance will return a value between 0 and 1 indicating
   * the similarity of two strings. The higher the value, the more similar the
   * strings are.
   *
   * See https://en.wikipedia.org/wiki/Jaro%E2%80%93Winkler_distance for details
   *
   * It seems that a more generalized implementation of Winkler's modification
   * can improve the results. This might be implemented later.
   * http://disi.unitn.it/~p2p/RelatedWork/Matching/Hermans_bnaic-2012.pdf
   *
   * @public
   * @param {string} str1 - String no. 1.
   * @param {string} str2 - String no. 2.
   * @param {boolean} [favorSameStart=false] - If true, strings with same start get higher distance value.
   * @param {boolean} [longTolerance=false] - If true, Winkler's tolerance for long words will be used.
   * @returns {number} Distance.
   */
  TextUtilities.computeJaroDistance = function(str1, str2, favorSameStart, longTolerance) {
    // sanity checks
    if (typeof str1 !== 'string' || typeof str2 !== 'string') {
      return undefined;
    }
    if (favorSameStart && typeof favorSameStart !== 'boolean') {
      favorSameStart = false;
    }
    if (longTolerance && typeof longTolerance !== 'boolean') {
      longTolerance = false;
    }

    // degenerate cases
    if (str1.length === 0 || str2.length === 0) {
      return 0;
    }
    if (str1 === str2) {
      return 1;
    }

    // counter variables
    var i, j, k;

    // number of matches between both strings
    var matches = 0;

    // number of transpositions between both strings
    var transpositions = 0;

    // The Jaro-Winkler distance
    var distance = 0;

    // length of common prefix up to 4 chars
    var l = 0;

    // scaling factor, should not exceed 0.25 (Winkler default = 0.1)
    var p = 0.1;

    // will be used often
    var str1Len = str1.length;
    var str2Len = str2.length;

    // determines the distance that still counts as a match
    var matchWindow = Math.floor(Math.max(str1Len, str2Len) / 2)- 1;

    // will store matches
    var str1Flags = new Array(str1Len);
    var str2Flags = new Array(str2Len);

    // count matches
    for (i = 0; i < str1Len; i++) {
      var start  = (i >= matchWindow) ? i - matchWindow : 0;
      var end = (i + matchWindow <= (str2Len - 1)) ? (i + matchWindow) : (str2Len - 1);

      for (j = start; j <= end; j++) {
        if (str1Flags[i] !== true && str2Flags[j] !== true && str1[i] === str2[j]) {
          str1Flags[i] = str2Flags[j] = true;
          matches += 1;
          break;
        }
      }
    }
    if (matches === 0) {
      return 0;
    }

    // count transpositions
    k = 0;
    for (i = 0; i < str1Len; i++) {
      if (!str1Flags[i]) {
        continue;
      }
      while (!str2Flags[k]) {
        k += 1;
      }
      if (str1[i] !== str2[k]) {
        transpositions += 1;
      }
      k += 1;
    }
    transpositions = transpositions / 2;

    // compute Jaro distance
    distance = (matches/str1Len + matches/str2Len + (matches - transpositions) / matches) / 3;

    // modification used by Winkler
    if (favorSameStart) {
      if (distance > 0.7 && str1Len > 3 && str2Len > 3) {
        while (str1[l] === str2[l] && l < 4) {
          l += 1;
        }
        distance = distance + l * p * (1 - distance);

        // modification for long words
        if (longTolerance) {
          if (Math.max(str1Len, str2Len) > 4 && matches > l + 1 && 2 * matches >= Math.max(str1Len, str2Len) + l) {
            distance += ((1.0 - distance) * ((matches - l - 1) / (str1Len + str2Len - 2 * l + 2)));
          }
        }
      }
    }

    return distance;
  };


  /**
   * Check whether a text contains a string, but fuzzy.
   *
   * This function is naive. It moves a window of needle's length (+2)
   * over the haystack's text and each move compares for similarity using
   * a given string metric. This will be slow for long texts!!!
   *
   * TODO: You might want to look into the bitap algorithm or experiment
   *       with regexps
   *
   * @param {String} needle - String to look for.
   * @param {String} haystack - Text to look in.
   */
  TextUtilities.fuzzyContains = function (needle, haystack) {
    return this.fuzzyFind(needle, haystack).contains;
  };

  /**
   * Find the first position of a fuzzy string within a text
   * @param {String} needle - String to look for.
   * @param {String} haystack - Text to look in.
   */
  TextUtilities.fuzzyIndexOf = function (needle, haystack) {
    return this.fuzzyFind(needle, haystack).indexOf;
  };

  /**
   * Find the first fuzzy match of a string within a text
   * @param {String} needle - String to look for.
   * @param {String} haystack - Text to look in.
   */
  TextUtilities.fuzzyMatch = function (needle, haystack) {
    return this.fuzzyFind(needle, haystack).match;
  };

  /**
   * Find a fuzzy string with in a text.
   * TODO: This could be cleaned ...
   * @param {String} needle - String to look for.
   * @param {String} haystack - Text to look in.
   * @param {object} params - Parameters.
   */
  TextUtilities.fuzzyFind = function (needle, haystack, params) {
    // Sanitization
    if (!needle || typeof needle !== 'string') {
      return false;
    }
    if (!haystack || typeof haystack !== 'string') {
      return false;
    }
    if (params === undefined || params.windowSize === undefined || typeof params.windowSize !== 'number') {
      params = {'windowSize': 3};
    }

    var match;

    var found = haystack.split(' ').some(function(hay) {
      match = hay;
      return H5P.TextUtilities.areSimilar(needle, hay);
    });
    if (found) {
      return {'contains' : found, 'match': match, 'index': haystack.indexOf(match)};
    }

    // This is not used for single words but for phrases
    for (var i = 0; i < haystack.length - needle.length + 1; i++) {
      var hay = [];
      for (var j = 0; j < params.windowSize; j++) {
        hay[j] = haystack.substr(i, needle.length + j);
      }

      // Checking isIsolated will e.g. prevent finding beginnings of words
      for (var j = 0; j < hay.length; j++) {
        if (TextUtilities.isIsolated(hay[j], haystack) && TextUtilities.areSimilar(hay[j], needle)) {
          match = hay[j];
          found = true;
          break;
        }
      }
      if (found) {
        break;
      }
    }
    if (!found) {
      match = undefined;
    }
    return {'contains' : found, 'match': match, 'index': haystack.indexOf(match)};
  };

  return TextUtilities;
}();
