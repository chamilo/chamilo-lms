<?php

/* For licensing terms, see /license.txt */

/**
 * This script pre-generates a list of commits to generate a changelog in the
 * form (branch, then commits in HTML form, then a final feedback):
 *
 * @example
 * 1.9.x
 * <li>(<a href="https://github.com/chamilo/chamilo-lms/commit/7333997ce358870bac139d15816dcaa7dd7794fa">7333997c</a> - <a href="https://task.beeznest.com/issues/8680">BT#8680</a>) Fixing custom lost password to work as classic Chamilo</li>
 * ...
 * <li>(<a href="https://github.com/chamilo/chamilo-lms/commit/acdc14c47997315b151efda9a530c47a53100d68">acdc14c4</a> - <a href="https://task.beeznest.com/issues/8676">BT#8676</a>) Adding unique email validation option</li>
 * Printed 367 commits of 500 requested (others were minor)
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
/**
 * Includes a modified version of Git lib by Sebastian Bergmann of PHPUnit
 * @usage php gitlog.php [-t|some-commit|-max20171001]
 * @see https://github.com/ywarnier/git
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}
require 'php-git/src/Git.php';
$repository = __DIR__.'/../..';
$number = 2000; //the number of commits to check (including minor)
$formatHTML = true;
$showDate = false;
$endCommit = false;
$isMaxDate = false;
if (!empty($argv[1])) {
    if ($argv[1] == '-t') {
        $showDate = true;
    } else if (substr($argv[1], 0, 4) == '-max') {
        $isMaxDate = true;
        $tempDate = substr($argv[1], 4);
        $y = substr($tempDate, 0, 4);
        $m = substr($tempDate, 4, 2);
        $d = substr($tempDate, 6, 2);
        $maxDate = new DateTime($y.'-'.$m.'-'.$d);
    } else {
        $endCommit = $argv[1];
        echo "An initial commit has been defined as ".$endCommit.PHP_EOL;
    }
}

$git = new \YWarnier\PHPGit\Git($repository);
echo "Log from branch: ".$git->getCurrentBranch().PHP_EOL;

$logs = $git->getRevisions('DESC', $number);
$i = 0;
foreach ($logs as $log) {
    $commitDate = $log['date']->format('Y-m-d');
    if ($showDate) {
        echo $commitDate.' '.substr($log['sha1'],0,8).PHP_EOL;
    }
    if ($isMaxDate) {
        // if the commit date is older than the max date, just forget about it
        if ($log['date'] < $maxDate) {
            continue;
        }
    }

    // Replace "Something - Something" by "Something: Something"
    $matches = array();
    if (preg_match('/^(\w*)\s-\s(.*)/', $log['message'], $matches)) {
        $log['message'] = $matches[1].': '.$matches[2];
    }
    // Replace "Something - Something" by "Something: Something"
    $matches = array();
    if (preg_match('/^(\w*\s\w*)\s-\s(.*)/', $log['message'], $matches)) {
        $log['message'] = $matches[1].': '.$matches[2];
    }
    // Replace "Something : Something" by "Something: Something"
    $matches = array();
    if (preg_match('/^(\w*)\s:\s(.*)/', $log['message'], $matches)) {
        $log['message'] = $matches[1].': '.$matches[2];
    }

    //Skip language update messages (not important)
    $langMsg = array(
        'Update language terms',
        'Update language vars',
        'Update lang vars',
        'Merge',
        'merge',
        'Scrutinizer Auto-Fixes',
        'Update changelog',
        'Fix PHP Warning'
    );
    foreach ($langMsg as $msg) {
        if (strpos($log['message'], $msg) === 0) {
            continue 2;
        }
    }
    $log['message'] = sanitizeCategory($log['message']);

    // Check for Minor importance messages to ignore...
    if (strncasecmp($log['message'], 'Minor', 5) === 0) {
        //Skip minor messages
        continue;
    }

    // Look for tasks references
    $issueLink = '';
    $matches = array();
    if (preg_match_all('/((BT)?#(\d){2,5})/', $log['message'], $matches)) {
        $issue = $matches[0][0];
        if (substr($issue, 0, 1) == '#') {
            // not a BeezNest task
            $num = substr($issue, 1);
            if ($num > 10000) {
                //should be Chamilo support site
                if ($formatHTML) {
                    $issueLink = ' - <a href="https://support.chamilo.org/issues/' . $num . '">CT#' . $num . '</a>';
                } else {
                    $issueLink = ' - ' . $num;
                }
            } else {
                //should be Github
                if ($formatHTML) {
                    $issueLink = ' - <a href="https://github.com/chamilo/chamilo-lms/issues/' . $num . '">GH#' . $num . '</a>';
                } else {
                    $issueLink = ' - ' . $num;
                }
            }
        } else {
            $num = substr($issue, 3);
            if ($num != '7683') {
                if ($formatHTML) {
                    //7683 is an internal task at BeezNest for all general contributions to Chamilo - no use in adding this reference
                    $issueLink = ' - <a href="https://task.beeznest.com/issues/' . $num . '">BT#' . $num . '</a>';
                } else {
                    $issueLink = ' - ' . $num;
                }
            }
        }
        if ($hasRefs = stripos($log['message'], ' see '.$issue)) {
            $log['message'] = substr($log['message'], 0, $hasRefs);
        }
        if ($hasRefs = stripos($log['message'], ' - ref')) {
            $log['message'] = substr($log['message'], 0, $hasRefs);
        }
        if ($hasRefs = stripos($log['message'], ' -refs ')) {
            $log['message'] = substr($log['message'], 0, $hasRefs);
        }
        if ($hasRefs = stripos($log['message'], ' - refs ')) {
            $log['message'] = substr($log['message'], 0, $hasRefs);
        }
        if ($hasRefs = stripos($log['message'], ' '.$matches[0][0])) {
            $log['message'] = substr($log['message'], 0, $hasRefs);
        }
    }
    $commitLink = '';
    if ($formatHTML) {
        $log['message'] = ucfirst($log['message']);
        $commitLink = '<a href="https://github.com/chamilo/chamilo-lms/commit/' . $log['sha1'] . '">' .
            substr($log['sha1'], 0, 8) . '</a>';
        echo '<li>['.$commitDate.'] ('.$commitLink.$issueLink.') '.$log['message'].'</li>'.PHP_EOL;
    } else {
        $commitLink = substr($log['sha1'], 0, 8);
        echo '('.$commitLink.$issueLink.') '.$log['message'].''.PHP_EOL;
    }
    // check end commit to stop processing
    if ($endCommit) {
        $length = strlen($endCommit);
        if (substr($log['sha1'], 0, $length) == $endCommit) {
            echo "Found the end commit ".$endCommit.", exiting...".PHP_EOL;
            break;
        }
    }
    $i++;
}
echo "Printed $i commits of $number requested (others were minor)".PHP_EOL;

/**
 * Get a message string and replace prefixes that do not match specs
 * in /documentation/changelog.html#terminology with prefixes that do
 * @param string $message
 * @return string The modified log message
 */
function sanitizeCategory(string $message): string {
    $knownMistakes = [
        'Quiz' => 'Exercise',
        'Exercises' => 'Exercise',
        'LP' => 'Learnpath',
        'Learning Paths' => 'Learnpath',
        'LearningPath' => 'Learnpath',
        'Learnpaths' => 'Learnpath',
        'Documents' => 'Document',
        'Announcements' => 'Announcement',
        'RemedialCourse' => 'Plugin: RemedialCourse',
        'Groups' => 'Group',
        'Survey report' => 'Survey',
        'Survey list export' => 'Survey',
        'Learnpath report' => 'Learnpath',
        'TopLink' => 'Plugin: TopLinks',
        'TopLinks' => 'Plugin: TopLinks',
        'Sessions' => 'Session',
        'Cas' => 'Authentication: CAS',
        'Webservices' => 'Webservice',
        'WebService' => 'Webservice',
        'Web services' => 'Webservice',
        'BBB' => 'Plugin: BigBlueButton',
        'My Progress' => 'Tracking',
        'My Progres' => 'Tracking',
        'Reports' => 'Tracking',
        'Courses' => 'Display',
        '[LP]' => 'Learnpath',
        'Student follow page' => 'Tracking: Student follow-up',
        'REST' => 'Webservice: REST',
        'Import CSV' => 'Admin: CSV import',
        'ImportCSV' => 'Admin: CSV import',
        'Import_csv.php' => 'Admin: CSV import',
        '[Minor]' => 'Minor:',
        '[usergroup]' => 'Group',
        '[admin]' => 'Admin',
        'MySpace' => 'Tracking',
        'Career diagram' => 'Career',
        'Careers' => 'Career',
        'Users' => 'User',
        'Style:' => 'Display:',
        'Course Announcement' => 'Announcement',
        'Testing' => 'CI',
        'Blogs' => 'Blog',
        'Gradebook eval' => 'Gradebook',
        'Survey test' => 'CI: Survey',
        'Editor' => 'WYSIWYG',
        'Global' => 'Internal',
        'Extra field' => 'Extra Fields',
        'Settings' => 'Admin',
        'Changelog' => 'Documentation',
        'Session import' => 'Admin: Session import',
        'XAPI' => 'xAPI',
        'CourseCopy' => 'Maintenance',
        'Course Copy' => 'Maintenance',
        'Reporting' => 'Tracking',
        'Course Backup' => 'Maintenance',
        'SSO' => 'Authentication: Single Sign On',
        'Skills' => 'Skill',
        'Messages' => 'Message',
        'Security fixes -' => 'Security:',
        'Assignments' => 'Work',
        'Improve code' => 'Internal: Improve code',
        'Pending works' => 'Work',
        'Thematic' => 'Course Progress',
        'Thematic advance' => 'Course Progress',
        'Agenda' => 'Calendar',
        'Course import' => 'Maintenance',
        'Student publication' => 'Work',
        'Student publications' => 'Work',
    ];
    foreach ($knownMistakes as $term => $fix) {
        if (strncasecmp($message, $term, strlen($term)) === 0) {
            //Skip minor messages
            $message = $fix.substr($message, strlen($term));
        }
    }
    return $message;
}
