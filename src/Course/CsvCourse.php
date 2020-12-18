<?php
namespace PRayno\MoveOnCourseCatalogueBundle\Course;

use PRayno\MoveOnApiBundle\MoveOnApi;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

class CsvCourse
{
    private $csvParameters=[];
    private $subInstitutionParameters=[];
    private $moveonCourse;
    private $moveOnApi;
    
    
    public function __construct(MoveonCourseInterface $moveonCourse,MoveOnApi $moveOnApi,array $csvParameters,array $subInstitutionParameters)
    {
        $this->moveOnApi = $moveOnApi;
        $this->csvParameters = $csvParameters;
        $this->moveonCourse = $moveonCourse;
        $this->subInstitutionParameters = $subInstitutionParameters;
    }
    
    public function buildMoveOnCourseList(string $fileName, $fromDate)
    {
        $source = file_get_contents($fileName);
        $csvEncoder =new CsvEncoder((empty($this->csvParameters["delimiter"])?"\t":$this->csvParameters["delimiter"]));
        $serializer = new Serializer([],[$csvEncoder]);
        $rows = $serializer->decode($source,'csv');

        $return = ["in_file"=>[],"errors"=>[],"courses"=>[]];
        $identifier = $this->moveonCourse->getIdentifier();
        $subInstitutions = $this->getSubInstitutions();
        $line=0;
        foreach ($rows as $row) {
            $line++;
            
            $moveOnCourse = clone $this->moveonCourse;
            $moveOnCourse->subInstitutions = $subInstitutions;
            
            // Build identifier
            $moveOnCourse->__set($identifier, (array)$row);
            $attributes = $moveOnCourse->getAttributes();

            if (isset($attributes[$identifier]))
                $return["in_file"][$line] = $attributes[$identifier];

            if (false === $this->rowIsValid($row, $fromDate))
                continue;

            // If a course with the same identifier was already process, pass it to the course object
            if (isset($return["courses"][$attributes[$identifier]]))
            {
                $moveOnCourse->course = $return["courses"][$attributes[$identifier]]->getAttributes();
            }

            foreach ($this->moveOnApi->getEntity("catalogue-course", "write") as $attribute) {
                $moveOnCourse->__set($attribute, (array)$row);
            }

            $attributes = $moveOnCourse->getAttributes();
            
            if (!isset($attributes[$identifier])) {
                $return["errors"][] = date("Y-m-d H:i:s") . " - CSV line $line : The field " . $identifier . " cannot be null in a MoveOn catalog-course object";
                continue;
            }
            $return["courses"][$attributes[$identifier]] = $moveOnCourse;
        }

        return $return;
    }
    
    
    private function getSubInstitutions()
    {
        $subInstitutions = [];
        $codeField = $this->subInstitutionParameters["code_field"];
        $idField = $this->subInstitutionParameters["course_catalog_id_field"];
        foreach ($this->moveOnApi->findBy("institution",["parent"=>$this->subInstitutionParameters["main_institution_id"]],["id"=>"asc"],1000,1,[$codeField]) as $institution)
        {
            if (!$institution->$codeField->__toString())
                continue;

            $codes = explode(",",$institution->$codeField->__toString());
            foreach ($codes as $code)
            {
                $subInstitutions[$code] = $institution->$idField->__toString();
            }
        }
        
        return $subInstitutions;
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
            if ($row[$field] >= $fromDate)
                return true;
        }

        return true;
    }
}