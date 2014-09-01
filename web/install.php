<?php
/* For licensing terms, see /chamilo_license.txt */

if (!isset($_SERVER['HTTP_HOST'])) {
    exit('This script cannot be run from the CLI. Run it from a browser.');
}

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../app/ChamiloRequirements.php';
require_once __DIR__ . '/../app/autoload.php';

// check for installed system
$paramFile = __DIR__ . '/../app/config/parameters.yml';
if (file_exists($paramFile)) {
    $data = Yaml::parse($paramFile);
    if (is_array($data)
        && isset($data['parameters'])
        && isset($data['parameters']['installed'])
        && false != $data['parameters']['installed']
    ) {
        require_once __DIR__.'/app_dev.php';
        exit;

        require_once __DIR__.'/../app/AppKernel.php';

        $kernel = new AppKernel('dev', false);
        $kernel->loadClassCache();
        $request = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
        exit;
    }
}

/**
 * @todo Identify correct locale (headers?)
 */
$locale           = 'en';
$collection       = new ChamiloRequirements();
$translator       = new Translator($locale);
$majorProblems    = $collection->getFailedRequirements();
$minorProblems    = $collection->getFailedRecommendations();

$translator->addLoader('yml', new YamlFileLoader());
$translator->addResource('yml', __DIR__ . '/../app/Resources/translations/install.' . $locale . '.yml', $locale);

function iterateRequirements(array $collection)
{
    foreach ($collection as $requirement) :
?>
    <tr>
        <td class="dark">
            <?php if ($requirement->isFulfilled()) : ?>
            <span class="icon-yes">
            <?php elseif (!$requirement->isOptional()) : ?>
            <span class="icon-no">
            <?php else : ?>
            <span class="icon-warning">
            <?php endif; ?>
            <?php echo $requirement->getTestMessage(); ?>
            </span>
            <?php if ($requirement instanceof CliRequirement && !$requirement->isFulfilled()) : ?>
                <pre class="output"><?php echo $requirement->getOutput(); ?></pre>
            <?php endif; ?>
        </td>
        <td>
            <?php
                if ($requirement->isFulfilled()) {
                    echo '<span class="label label-success">OK</span>';
                } else {
                    if (!$requirement->isOptional()) {
                        echo '<span class="label label-danger">';
                    } else {
                        echo '<span class="label label-warning">';
                    }
                    $requirement->getHelpHtml();
                    echo '</span>';
                }
            ?>
        </td>
    </tr>
<?php
    endforeach;
}
?>
<!doctype html>
<!--[if IE 7 ]><html class="no-js ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="no-js ie ie8" lang="en"> <![endif]-->
<!--[if IE 9 ]><html class="no-js ie ie9" lang="en"> <![endif]-->
<!--[if (gte IE 10)|!(IE)]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo $translator->trans('title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="bundles/avanzuadmintheme/vendor/AdminLTE/css/bootstrap.css" />
    <script type="text/javascript" src="bundles/avanzuadmintheme/vendor/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $('.progress-bar li:last-child em.fix-bg').width($('.progress-bar li:last-child').width() / 2);
            $('.progress-bar li:first-child em.fix-bg').width($('.progress-bar li:first-child').width() / 2);

            var splash = $('div.start-box'),
                body = $('body'),
                winHeight = $(window).height();

            $('#begin-install').click(function() {
                splash.hide();
                body.css({ 'overflow': 'visible', 'height': 'auto' });
            });

            if ('localStorage' in window && window['localStorage'] !== null) {
                if (!localStorage.getItem('oroInstallSplash')) {
                    splash.show().height(winHeight);
                    body.css({ 'overflow': 'hidden', 'height': winHeight });

                    localStorage.setItem('oroInstallSplash', true);
                }
            }

            <?php if (!count($majorProblems)) : ?>
            // initiate application in background
            $.get('app_dev.php/installer/flow/chamilo_installer/configure');
            <?php endif; ?>
        });
    </script>
    <style>
        td pre.output {
            background-color: #232125;
            overflow: auto;
            line-height: 1.3em;
            color: #fff;
            font-size: 14px;
            padding: .7em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="logo"><?php echo $translator->trans('title'); ?></h1>
        </div>
        <div class="content">
            <div class="progress-bar">
                <ul>
                    <li class="active">
                        <em class="fix-bg">&nbsp;</em>
                        <strong class="step">1</strong>
                        <span><?php echo $translator->trans('process.step.check.header'); ?></span>
                    </li>
                    <li>
                        <em class="fix-bg">&nbsp;</em>
                        <strong class="step">2</strong>
                        <span><?php echo $translator->trans('process.step.configure'); ?></span>
                    </li>
                    <li>
                        <em class="fix-bg">&nbsp;</em>
                        <strong class="step">3</strong>
                        <span><?php echo $translator->trans('process.step.schema'); ?></span>
                    </li>
                    <li>
                        <em class="fix-bg">&nbsp;</em>
                        <strong class="step">4</strong>
                        <span><?php echo $translator->trans('process.step.setup'); ?></span>
                    </li>
                    <li>
                        <em class="fix-bg">&nbsp;</em>
                        <strong class="step">5</strong>
                        <span><?php echo $translator->trans('process.step.final'); ?></span>
                    </li>
                </ul>
            </div>

            <div class="page-title">
                <h2><?php echo $translator->trans('process.step.check.header'); ?></h2>
            </div>

            <div>
                <?php if (count($majorProblems)) : ?>
                <div class="alert alert-warning" role="alert">
                    <ul>
                        <li><?php echo $translator->trans('process.step.check.invalid'); ?></li>
                        <?php if ($collection->hasPhpIniConfigIssue()): ?>
                        <li id="phpini">*
                            <?php
                                if ($collection->getPhpIniConfigPath()) :
                                    echo $translator->trans(
                                        'process.step.check.phpchanges',
                                        array(
                                            '%path%' => $collection->getPhpIniConfigPath()
                                        )
                                    );
                                else :
                                    echo $translator->trans('process.step.check.phpchanges');
                                endif;
                            ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php
                $requirements = array(
                    'mandatory' => $collection->getMandatoryRequirements(),
                    'php'       => $collection->getPhpIniRequirements(),
                    'oro'       => $collection->getRequirements(),
                    'cli'       => $collection->getCliRequirements(),
                    'optional'  => $collection->getRecommendations(),
                );

                foreach ($requirements as $type => $requirement) : ?>
                    <table class="table table-striped">
                        <col width="75%" valign="top">
                        <col width="25%" valign="top">
                        <thead>
                            <tr>
                                <th><?php echo $translator->trans('process.step.check.table.' . $type); ?></th>
                                <th><?php echo $translator->trans('process.step.check.table.check'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php iterateRequirements($requirement); ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
            <div class="button-set">
                <div class="pull-right">
                    <?php if (count($majorProblems) || count($minorProblems)): ?>
                    <a href="install.php" class="btn btn-default icon-reset">
                        <span><?php echo $translator->trans('process.button.refresh'); ?></span>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo count($majorProblems) ? 'javascript: void(0);' : 'app_dev.php/installer/flow/chamilo_installer/welcome'; ?>" class="btn btn-primary next <?php echo count($majorProblems) ? 'disabled' : 'primary'; ?>">
                        <span><?php echo $translator->trans('process.button.next'); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="start-box" style="display: none;">
        <div class="fade-box"></div>
        <div class="start-content">
            <div class="start-content-holder">
                <div class="center"></div>
                <h2><?php echo $translator->trans('welcome.header'); ?></h2>
                <h3><?php echo $translator->trans('welcome.content'); ?></h3>
                <div class="start-footer">
                    <button type="button" id="begin-install" class="btn btn-primary next" href="javascript: void(0);">
                        <span><?php echo $translator->trans('welcome.button'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
