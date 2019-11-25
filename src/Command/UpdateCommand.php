<?php

namespace PRayno\MoveOnCourseCatalogueBundle\Command;

use PRayno\MoveOnCourseCatalogueBundle\Course\CsvCourse;
use PRayno\MoveOnCourseCatalogueBundle\Logic\MoveOnProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends Command
{
    protected static $defaultName = 'moveon:course-catalog:update';
    private $moveonCourse;
    private $moveOnApi;
    private $csvCourse;
    private $moveOnProcess;

    public function __construct(CsvCourse $csvCourse, MoveOnProcess $moveOnProcess)
    {
        $this->csvCourse = $csvCourse;
        $this->moveOnProcess = $moveOnProcess;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Update MoveON course catalog from CSV file')
            ->addArgument('csv-file', InputArgument::REQUIRED, 'CSV file location')
            ->addArgument('from-date', InputArgument::OPTIONAL, 'Update only elements modified from a given date - YYY-MM-DD',date_format(new \DateTime('yesterday'),"Y-m-d"))
            ->addOption("dump", "d",InputOption::VALUE_NONE, 'Dump the stats (created/updated) without publishing to MoveOn')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $csvCourses = $this->csvCourse->buildMoveOnCourseList($input->getArgument('csv-file'), $input->getArgument('from-date'));

        if (!empty($csvCourses["errors"])) {
            foreach ($csvCourses["errors"] as $error)
                $io->error($error);
        }

        $moveonCourses = $this->moveOnProcess->retrieveMoveOnCourses();

        $stats = $this->moveOnProcess->synchronize($csvCourses["courses"], $moveonCourses, $io, $input->getOption("dump"));

        if ($input->getOption("dump") === true)
            $io->text("This will create " . $stats["create"] . " course(s) and update " . $stats["update"]);
        else
        {
            $io->text($stats["create"] . " course(s) created and " . $stats["update"] . " updated");
            $this->moveOnProcess->deactivateDeletedCourses($csvCourses["in_file"], $moveonCourses, $io);
        }
    }
}