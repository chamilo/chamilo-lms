<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class DoctrineMigrationsMigrateCommandDecorator extends Command
{
    public const SKIP_ATTENDANCES_FLAG = 'CHAMILO_MIGRATION_SKIP_ATTENDANCES';

    public function __construct(
        private readonly Command $inner
    ) {
        parent::__construct($inner->getName() ?: 'doctrine:migrations:migrate');
    }

    protected function configure(): void
    {
        $this->setName($this->inner->getName() ?: 'doctrine:migrations:migrate');
        $this->setDescription((string) $this->inner->getDescription());
        $this->setHelp((string) $this->inner->getHelp());

        $definition = clone $this->inner->getDefinition();
        $this->setDefinition($definition);

        $this->addOption(
            'skip-attendances',
            null,
            InputOption::VALUE_NONE,
            'When enabled, only migrate attendances linked to gradebook (type=7). Others will be skipped.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $skipAttendances = (bool) $input->getOption('skip-attendances');

        if ($skipAttendances) {
            $_SERVER[self::SKIP_ATTENDANCES_FLAG] = '1';
            $_ENV[self::SKIP_ATTENDANCES_FLAG] = '1';
            putenv(self::SKIP_ATTENDANCES_FLAG.'=1');
        }

        try {
            $innerDefinition = $this->inner->getDefinition();
            $data = [];

            foreach ($innerDefinition->getArguments() as $name => $argument) {
                $value = $input->getArgument($name);
                if (null !== $value) {
                    $data[$name] = $value;
                }
            }

            foreach ($innerDefinition->getOptions() as $name => $option) {
                $value = $input->getOption($name);

                if ($option->acceptValue()) {
                    if (null !== $value) {
                        $data['--'.$name] = $value;
                    }
                } else {
                    if (true === $value) {
                        $data['--'.$name] = true;
                    }
                }
            }

            $innerInput = new ArrayInput($data, $innerDefinition);
            $innerInput->setInteractive($input->isInteractive());

            return $this->inner->run($innerInput, $output);
        } finally {
            unset($_SERVER[self::SKIP_ATTENDANCES_FLAG], $_ENV[self::SKIP_ATTENDANCES_FLAG]);
            putenv(self::SKIP_ATTENDANCES_FLAG);
        }
    }
}
