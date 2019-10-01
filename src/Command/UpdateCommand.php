<?php

namespace PRayno\MoveOnCourseCatalogueBundle\Command;

use PRayno\MoveOnApiBundle\MoveOnApi;
use PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourseInterface;
use PRayno\MoveOnApi\MoveOn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

class UpdateCommand extends Command
{
    protected static $defaultName = 'moveon:course-catalog:update';
    private $csvParameters=[];
    private $moveonCourse;
    private $moveOnApi;

    public function __construct(MoveonCourseInterface $moveonCourse,MoveOnApi $moveOnApi,array $csvParameters)
    {
        $this->moveOnApi = $moveOnApi;
        $this->csvParameters = $csvParameters;
        $this->moveonCourse = $moveonCourse;
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
        // Search for current active courses
        $courses = $this->moveOnApi->findBy("catalogue-course",["is_active"=>1],["id"=>"asc"],50000,1,["id",$this->moveonCourse->getIdentifier()],"eng","true","queue",60);
        $moveonCourses=[];
        $attributeId="catalogue_course.id";
        $attributeIdentifier = "catalogue_course.".$this->moveonCourse->getIdentifier();
        foreach ($courses->rows as $course)
        {
            $identifier = $course->$attributeIdentifier->__toString();

            if (empty($identifier))
                continue;

            $moveonCourses[$identifier]=$course->$attributeId->__toString();
        }

        $io = new SymfonyStyle($input, $output);

        $source = file_get_contents($input->getArgument('csv-file'));
        $csvEncoder =new CsvEncoder((empty($this->csvParameters["delimiter"])?"\t":$this->csvParameters["delimiter"]));
        $serializer = new Serializer([],[$csvEncoder]);
        $rows = $serializer->decode($source,'csv');

        $line=0;
        $stats = ["create"=>0,"update"=>0];
        foreach ($rows as $row)
        {
            $line++;
            if (false === $this->rowIsValid($row,$input->getArgument('from-date')))
                continue;

            $moveOnCourse = $this->moveonCourse;
            foreach($this->moveOnApi->getEntity("catalogue-course") as $attribute)
            {
                $moveOnCourse->__set($attribute,(array) $row);
            }

            $attributes = $moveOnCourse->getAttributes();

            $identifier = ["field"=>$moveOnCourse->getIdentifier(),"value"=>null];
            if (!isset($attributes[$identifier["field"]]))
            {
                $io->error(date("Y-m-d H:i:s")." - CSV line $line : The field ".$identifier["field"]." cannot be null in a MoveOn catalog-course object");
                continue;
            }
            $identifier["value"] = $attributes[$identifier["field"]];

            // Try to see if entry already exists
            if (isset($moveonCourses[$identifier["value"]]))
                $attributes["id"] = $moveonCourses[$identifier["value"]];

            if ($input->getOption("dump")===true)
            {
                if (isset($attributes["id"]))
                    $stats["update"]++;
                else
                    $stats["create"]++;

                continue;
            }

            // Publish to MoveON
            try {
                $this->moveOnApi->save("catalogue-course",$attributes);
                $io->success(date("Y-m-d H:i:s")." - CSV line $line : Course ".(isset($attributes["id"])?"updated":"created")." - ".$identifier["value"]);
            }
            catch (\Exception $exception)
            {
                $io->error(date("Y-m-d H:i:s")." - CSV line $line : ".$exception->getMessage());
            }
        }

        if ($input->getOption("dump")===true)
            $io->text("This will create ".$stats["create"]." course(s) and update ".$stats["update"]);
        else
            $io->text($line);
    }

    private function rowIsValid(array $row, string $fromDate)
    {
        foreach ($this->csvParameters["required_fields"] as $field)
        {
            if (empty($row[$field]))
                return false;
        }

        foreach ($this->csvParameters["latest_date_fields"] as $field)
        {
            if ($row[$field] < $fromDate)
                return false;
        }

        return true;
    }
}