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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
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
        private readonly SettingsManager $settingsManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('folder', InputArgument::REQUIRED, 'Path to folder with course files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
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

        $finder = new Finder();
        $finder->files()->in($folder);

        foreach ($finder as $file) {
            $basename = $file->getBasename();
            $courseCode = pathinfo($basename, PATHINFO_FILENAME);
            $filePath = $file->getRealPath();

            // Skip unsupported extensions
            $allowedExtensions = ['pdf', 'html', 'htm', 'mp4'];
            if (!in_array(strtolower($file->getExtension()), $allowedExtensions)) {
                $io->warning("Skipping unsupported file: $basename");
                continue;
            }

            $io->section("Creating course: $courseCode");

            // Step 1: Create the course
            $course = $this->courseService->createCourse([
                'title' => $courseCode,
                'wanted_code' => $courseCode,
                'add_user_as_teacher' => true,
                'course_language' => $this->settingsManager->getSetting('language.platform_language'),
                'visibility' => Course::OPEN_PLATFORM,
                'subscribe' => true,
                'unsubscribe' => true,
                'disk_quota' => $this->settingsManager->getSetting('document.default_document_quotum'),
                'expiration_date' => (new \DateTime('+1 year'))->format('Y-m-d H:i:s')
            ]);

            if (!$course) {
                throw new \RuntimeException('Error: Course could not be created.');
            }

            // Step 2: Create learning path (CLp)
            $lp = (new CLp())
                ->setLpType(1)
                ->setTitle($courseCode)
                ->setDescription('')
                ->setPublishedOn(null)
                ->setExpiredOn(null)
                ->setCategory(null)
                ->setParent($course)
                ->addCourseLink($course);
            $lp->setCreator($adminUser);

            $lpRepo = $this->em->getRepository(CLp::class);
            $lpRepo->createLp($lp);

            // Step 3: Create CDocument from uploaded file
            $document = new CDocument();
            $document->setFiletype('file')
                ->setTitle($basename)
                ->setComment(null)
                ->setReadonly(false)
                ->setCreator($adminUser)
                ->setParent($course)
                ->addCourseLink($course);

            $this->em->persist($document);
            $this->em->flush();

            $documentRepo = $this->em->getRepository(CDocument::class);
            $documentRepo->addFileFromPath($document, $basename, $filePath);

            // Step 4: Create LP item linked to the document
            // Ensure root item exists
            $lpItemRepo = $this->em->getRepository(CLpItem::class);
            $rootItem = $lpItemRepo->getRootItem((int) $lp->getIid());

            if (!$rootItem) {
                $rootItem = (new CLpItem())
                    ->setTitle('root')
                    ->setPath('root')
                    ->setLp($lp)
                    ->setItemType('root');
                $this->em->persist($rootItem);
                $this->em->flush();
            }

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

            $io->success("Course '$courseCode' created with LP and document item '$basename'");
        }

        return Command::SUCCESS;
    }

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
