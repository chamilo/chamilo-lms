<?php
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
 * @see https://github.com/ywarnier/git
 */
require 'php-git/src/Git.php';
$repository = __DIR__.'/../..';
$number = 500; //the number of commits to check (including minor)
$formatHTML = true;
$showDate = false;
if (!empty($argv[1]) && $argv[1] == '-t') {
    $showDate = true;
}

$git = new \SebastianBergmann\Git\Git($repository);
echo "Log from branch: ".$git->getCurrentBranch().PHP_EOL;

$logs = $git->getRevisions('DESC', $number);
$i = 0;
foreach ($logs as $log) {
    if ($showDate) {
      echo $log['date']->format('Y-m-d H:i:s').' '.substr($log['sha1'],0,8).PHP_EOL;
    }
    if (strncasecmp($log['message'], 'Minor', 5) === 0) {
        //Skip minor messages
        continue;
    }
    $issueLink = '';
    $matches = array();
    if (preg_match_all('/((BT)?#(\d){2,5})/', $log['message'], $matches)) {
        $issue = $matches[0][0];
        if (substr($issue, 0, 1) == '#') {
            // not a BeezNest task
            $num = substr($issue, 1);
            if ($formatHTML) {
                $issueLink = ' - <a href="https://support.chamilo.org/issues/' . $num . '">#' . $num . '</a>';
            } else {
                $issueLink = ' - ' . $num;
            }
        } else {
            $num = substr($issue, 3);
            if ($formatHTML) {
                $issueLink = ' - <a href="https://task.beeznest.com/issues/' . $num . '">BT#' . $num . '</a>';
            } else {
                $issueLink = ' - ' . $num;
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
        
    }
    $commitLink = '';
    if ($formatHTML) {
        $commitLink = '<a href="https://github.com/chamilo/chamilo-lms/commit/' . $log['sha1'] . '">' .
            substr($log['sha1'], 0, 8) . '</a>';
        echo '<li>('.$commitLink.$issueLink.') '.$log['message'].'</li>'.PHP_EOL;
    } else {
        $commitLink = substr($log['sha1'], 0, 8);
        echo '('.$commitLink.$issueLink.') '.$log['message'].''.PHP_EOL;
    }
    $i++;
}
echo "Printed $i commits of $number requested (others were minor)".PHP_EOL;
