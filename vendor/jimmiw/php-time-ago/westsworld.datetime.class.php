<?php

/**
 * NOTE: This class is no longer maintained as of version 0.2.0.
 * You should use the timeAgoInWords("date") convenience method instead.
 *
 * A specialization of the DateTime class, that can print out,
 * "how long ago" it was.
 *
 * @author jimmiw
 * @since 2009-09-28
 * @site http://github.com/jimmiw/php-time-ago
 */
class WWDateTime extends DateTime {

  /**
   *
   */
  public function __construct(/*string*/ $time = "now", 
    DateTimeZone $timezone = NULL) {
    
    // if the $timezone is null, we take 'Europe/London' as the default
    // this was done, because the parent construct tossed an exception
    if($timezone == NULL) {
      $timezone = new DateTimeZone('Europe/London');
    }
    
    // initializes the parant
    parent::__construct($time, $timezone);
  }
  
  /**
   *  Returns how long time ago this date object is, in words.
   *  E.g. current date could be "2009-01-02" and this object could hold
   *  "2009-01-01", then this function would return "1 day".
   *
   *  @return the number of time ago, in words (e.g. 2 days).
   */
  public function timeAgoInWords() {
    $timeAgoInWords = NULL;
    $yearDiff = 0;
    $monthDiff = 0;
    $dayDiff = 0;
    $hourDiff = 0;
    $minuteDiff = 0;
    $secondDiff = 0;
    
    $now = new DateTime("now", $this->getTimezone());
    
    // tests the years
    $yearDiff = $this->findDiff($now, $this, 'Y');
    if($yearDiff == 1) {
      $timeAgoInWords = $this->constructTimeAgoWord(
        $yearDiff,
        'year',
        1,
        'about'
      );
    }
    else if($yearDiff >= 2) {
      $timeAgoInWords = $this->constructTimeAgoWord(
        $yearDiff,
        'year',
        1,
        'over'
      );
    }
    
    // tests the months
    if($timeAgoInWords == NULL) {
      $timeAgoInWords = $this->constructTimeAgoWord(
        $this->findDiff($now, $this, 'n'),
        'month',
        1,
        'about'
      );
    }
    
    // tests the days
    if($timeAgoInWords == NULL) {
      $timeAgoInWords = $this->constructTimeAgoWord(
        $this->findDiff($now, $this, 'j'),
        'day',
        1
      );
    }
    
    // tests the hours
    if($timeAgoInWords == NULL) {
      $timeAgoInWords = $this->constructTimeAgoWord(
        $this->findDiff($now, $this, 'G'),
        'hour',
        1,
        'about'
      );
    }
    
    // tests the minutes
    if($timeAgoInWords == NULL) {
      $minuteDiff = $this->findDiff($now, $this, 'i');
      // if under 44 mins!
      if($minuteDiff <= 44) {
        $timeAgoInWords = $this->constructTimeAgoWord(
          $minuteDiff,
          'minute',
          1
        );
      }
      // else it's about an hour
      else {
        $timeAgoInWords = $this->constructTimeAgoWord(
          1,
          'hour',
          1,
          'about'
        );
      }
    }
    
    // tests the seconds
    if($timeAgoInWords == NULL) {
      $secondDiff = $this->findDiff($now, $this, 's');
      // if under 29 secs
      if($secondDiff <= 29) {
        $timeAgoInWords = "less than a minute";
      }
      // else it's a minute!
      else {
        $timeAgoInWords = $this->constructTimeAgoWord(
          1,
          'minute'
        );
      }
    }

    return $timeAgoInWords;
  }
  
  /** 
   *  Finds the difference in the two DateTime objects, using the given format.
   *  @param from
   *  @param to
   *  @param format
   *  @return the difference in the two DateTime objects
   */
  private function findDiff(DateTime $from = NULL,
    DateTime $to = NULL,
    $format = NULL) {
    
    return $from->format($format) - $to->format($format);
  }
  
  /**
   *  Constructs the actual "time ago"-word
   *  @param timeDifference
   *  @param timeName
   *  @param decidingTimeDifference
   *  @param prefix
   *  @param postfix
   *  @return the "time ago"-word generated
   */
  private function constructTimeAgoWord($timeDiffrence = 0,
    $timeName = NULL,
    $decidingTimeDifference = 1,
    $prefix = NULL,
    $postfix = NULL) {
    
    // initializes the timeAgoInWord placeholder
    $timeAgoInWords = NULL;
    
    if($timeDiffrence > 0) {
      // sets the difference
      $timeAgoInWords = $timeDiffrence . " ";
      
      // adds the "prefix word", if any
      if($prefix != NULL) {
        // blindly adds a space between the words
        $timeAgoInWords = $prefix . " " . $timeAgoInWords;
      }
      
      // tests if we are to pluralize the time name or not
      if($timeDiffrence > $decidingTimeDifference) {
        $timeAgoInWords .= $this->pluralize($timeName);
      }
      else {
        $timeAgoInWords .= $timeName;
      }
      
      // adds the "postfix word", if any
      if($postfix != NULL) {
        // blindly adds a space between the words
        $timeAgoInWords .=  " " . $postfix;
      }
    }
    
    // returns the "time ago in words" found, else NULL
    return $timeAgoInWords;
  }
  
  /**
   *  Pluralizes the given word (only if it's in my list ofc!)
   *  @param $word        the word to pluralize
   *  @return the pluralized word, if possible.
   */
  private function pluralize($word = NULL) {
    $pluralizedWord = $word;
    
    if($word == 'year') {
      $pluralizedWord = 'years';
    }
    else if($word == 'month') {
      $pluralizedWord = 'months';
    }
    else if($word == 'day') {
      $pluralizedWord = 'days';
    }
    else if($word == 'hour') {
      $pluralizedWord = 'hours';
    }
    else if($word == 'minute') {
      $pluralizedWord = 'minutes';
    }
    else if($word == 'second') {
      $pluralizedWord = 'seconds';
    }
    
    return $pluralizedWord;
  }
}

?>
