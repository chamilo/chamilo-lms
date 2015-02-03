<?php
/* For licensing terms, see /license.txt */

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
$translator->addResource(
    'yml',
    __DIR__ . '/../src/Chamilo/InstallerBundle/Resources/translations/messages.' . $locale . '.yml',
    $locale
);

function iterateRequirements(array $collection, $translator) {
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
                    echo '<h4><span class="label label-success"><i class="fa fa-check-circle"></i>  '.$translator->trans('process.step.check.requirement_status.ok').'</span></h4>';
                } else {
                    if (!$requirement->isOptional()) {
                        echo '<h4><span class="label label-danger"><i class="fa fa-exclamation-triangle"> </i> '.$translator->trans('process.step.check.requirement_status.danger').'</span></h4>';
                    } else {
                        echo '<h4><span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> '.$translator->trans('process.step.check.requirement_status.warning').'</span></h4>';
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
    <link rel="stylesheet" type="text/css" href="bundles/chamilotheme/components/bootstrap/dist/css/bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="bundles/chamilotheme/components/fontawesome/css/font-awesome.css" />
    <link rel="stylesheet" type="text/css" href="bundles/chamiloinstaller/css/install.css" />
    <script type="text/javascript" src="bundles/chamilotheme/components/jquery/dist/jquery.min.js"></script>

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
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="logo"><?php echo $translator->trans('title'); ?></h1>
        </div>
        <div class="content">
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
                    'chamilo'   => $collection->getChamiloRequirements(),
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
                                <th><?php echo $translator->trans('process.step.check.table.status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php iterateRequirements($requirement, $translator); ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>

            <hr />
            <br />

            <div class="install-form-actions">
                <?php if (count($majorProblems) || count($minorProblems)): ?>
                <a href="install.php" class="btn btn-default btn-lg">
                    <i class="fa fa-refresh"></i> <?php echo $translator->trans('process.button.refresh'); ?>
                </a>
                <?php endif; ?>
                <a href="<?php echo count($majorProblems) ? 'javascript: void(0);' : 'app_dev.php/installer/flow/chamilo_installer/welcome'; ?>" class="btn btn-lg btn-primary <?php echo count($majorProblems) ? 'disabled' : 'primary'; ?>">
                    <i class="fa fa-chevron-right"></i> <?php echo $translator->trans('process.button.continue'); ?>
                </a>
            </div>
        </div>
    </div>

    <hr/ >
    <br />

    <footer class="footer">
        <div class="container">
            <p class="text-muted">
                Chamilo
            </p>
        </div>
    </footer>
</body>
</html>
