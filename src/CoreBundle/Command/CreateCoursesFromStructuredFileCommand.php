<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\CourseService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:create-courses-from-structured-file',
    description: 'Create courses and learning paths from a folder containing files',
)]
class CreateCoursesFromStructuredFileCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CourseService $courseService,
        private readonly SettingsManager $settingsManager,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'folder',
            InputArgument::REQUIRED,
            'Absolute path to the folder that contains course files'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $adminUser = $this->getFirstAdmin();
        if (!$adminUser) {
            $io->error('No admin user found in the system.');
            return Command::FAILURE;
        }

        $folder = $input->getArgument('folder');
        if (!is_dir($folder)) {
            $io->error("Invalid folder: $folder");
            return Command::FAILURE;
        }

        // Retrieve Unix permissions from platform settings
        $dirPermOct  = octdec(
            $this->settingsManager->getSetting('document.permissions_for_new_directories') ?? '0777'
        );
        $filePermOct = octdec(
            $this->settingsManager->getSetting('document.permissions_for_new_files') ?? '0666'
        );

        // Absolute base to /var/upload/resource
        $uploadBase = $this->parameterBag->get('kernel.project_dir') . '/var/upload/resource';

        $finder = new Finder();
        $finder->files()->in($folder);

        foreach ($finder as $file) {
            $basename   = $file->getBasename();
            $courseCode = pathinfo($basename, PATHINFO_FILENAME);
            $filePath   = $file->getRealPath();

            // 1. Skip unsupported file extensions
            $allowedExtensions = ['pdf', 'html', 'htm', 'mp4'];
            if (!in_array(strtolower($file->getExtension()), $allowedExtensions, true)) {
                $io->warning("Skipping unsupported file: $basename");
                continue;
            }

            $io->section("Creating course: $courseCode");

            // 2. Create course
            $course = $this->courseService->createCourse([
                'title'               => $courseCode,
                'wanted_code'         => $courseCode,
                'add_user_as_teacher' => true,
                'course_language'     => $this->settingsManager->getSetting('language.platform_language'),
                'visibility'          => Course::OPEN_PLATFORM,
                'subscribe'           => true,
                'unsubscribe'         => true,
                'disk_quota'          => $this->settingsManager->getSetting('document.default_document_quotum'),
                'expiration_date'     => (new \DateTime('+1 year'))->format('Y-m-d H:i:s'),
            ]);

            if (!$course) {
                throw new \RuntimeException("Course '$courseCode' could not be created.");
            }

            // 3. Create learning path
            $lp = (new CLp())
                ->setLpType(1)
                ->setTitle($courseCode)
                ->setDescription('')
                ->setPublishedOn(null)
                ->setExpiredOn(null)
                ->setCategory(null)
                ->setParent($course)
                ->addCourseLink($course)
                ->setCreator($adminUser);

            $this->em->getRepository(CLp::class)->createLp($lp);

            // 4. Create document
            $document = (new CDocument())
                ->setFiletype('file')
                ->setTitle($basename)
                ->setComment(null)
                ->setReadonly(false)
                ->setCreator($adminUser)
                ->setParent($course)
                ->addCourseLink($course);

            $this->em->persist($document);
            $this->em->flush();

            $documentRepo = $this->em->getRepository(CDocument::class);
            $resourceFile = $documentRepo->addFileFromPath($document, $basename, $filePath);

            // 4.1  Apply permissions to the real file & its directory
            if ($resourceFile) {
                $resourceNodeRepo = $this->em->getRepository(\Chamilo\CoreBundle\Entity\ResourceNode::class);
                $relativePath = $resourceNodeRepo->getFilename($resourceFile); // e.g. /2025/06/16/abc.pdf
                $fullPath     = realpath($uploadBase . $relativePath);

                if ($fullPath && is_file($fullPath)) {
                    @chmod($fullPath, $filePermOct);
                }
                $fullDir = dirname($fullPath ?: '');
                if ($fullDir && is_dir($fullDir)) {
                    @chmod($fullDir, $dirPermOct);
                }
            }

            // 5. Ensure learning path root item exists
            $lpItemRepo = $this->em->getRepository(CLpItem::class);
            $rootItem   = $lpItemRepo->getRootItem((int) $lp->getIid());

            if (!$rootItem) {
                $rootItem = (new CLpItem())
                    ->setTitle('root')
                    ->setPath('root')
                    ->setLp($lp)
                    ->setItemType('root');
                $this->em->persist($rootItem);
                $this->em->flush();
            }

            // 6. Create LP item linked to the document
            $lpItem = (new CLpItem())
                ->setLp($lp)
                ->setTitle($basename)
                ->setItemType('document')
                ->setRef((string) $document->getIid())
                ->setPath((string) $document->getIid())
                ->setDisplayOrder(1)
                ->setLaunchData('')
                ->setMinScore(0)
                ->setMaxScore(100)
                ->setParent($rootItem)
                ->setLvl(1)
                ->setRoot($rootItem);

            $this->em->persist($lpItem);
            $this->em->flush();

            $io->success("Course '$courseCode' created with LP and document '$basename'");
        }

        return Command::SUCCESS;
    }

    /**
     * Return the first user that has ROLE_ADMIN.
     */
    private function getFirstAdmin(): ?User
    {
        return $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
