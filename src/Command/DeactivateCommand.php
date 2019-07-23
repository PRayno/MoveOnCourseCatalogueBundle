<?php

namespace PRayno\MoveOnCourseCatalogueBundle\Command;

use PRayno\MoveOnApi\MoveOn;
use PRayno\MoveOnApiBundle\MoveOnApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DeactivateCommand extends Command
{
    protected static $defaultName = 'moveon:course-catalog:deactivate';

    private $moveOnApi;
    public function __construct(MoveOnApi $moveOnApi)
    {
        $this->moveOnApi = $moveOnApi;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Deactivate courses in the course catalog')
            ->addArgument('query', InputArgument::OPTIONAL, 'Query to match courses')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $query = $input->getArgument('query');

        $attribute = "catalogue_course.id";

        $filter = '{"filters":"{\"groupOp\":\"AND\",\"rules\":['.$query.']}","visibleColumns":"'.$attribute.'","locale":"eng","sortName":"'.$attribute.'","sortOrder":"asc","_search":"true","page":"1","rows":"10000"}';

        $data = $this->moveOnApi->sendQuery("catalogue-course","list",$filter);

        $processed=["success"=>0,"failure"=>0];
        foreach ($data->rows as $course)
        {
            $id = $course->$attribute->__toString();
            try {
                $this->moveOnApi->save("catalogue-course",["id"=>$id,"is_active"=>0]);
                $processed["success"]++;
            }
            catch (\Exception $exception)
            {
                $io->error($exception->getMessage());
                $processed["failure"]++;
            }
        }

        $io->note($processed["success"]." courses deactivated, ".$processed["failure"]." failed to be deactivated");
    }
}
