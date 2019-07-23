<?php
namespace PRayno\MoveOnCourseCatalogueBundle\Course;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class MoveonCourse implements MoveonCourseInterface
{
    private $identifier="external_id";
    private $attributes=[];

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param $name
     * @param $row
     */
    public function __set($name, $row)
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $methodName = $nameConverter->denormalize("set_$name");

        if (method_exists($this,$methodName))
            $this->attributes[$name] = str_replace('"',"",$this->$methodName($row));
    }

    private function setName(array $row)
    {
        $year = explode(" ",$row["LIB_ETP"])[0];
        return utf8_encode($row["COD_ANU"]." - ".$row["LIB_CMP_EC"]." (".$year.") : ".$row["LIB_ELP_EC"]);
    }

    private function setPeriod(array $row)
    {
        if (!is_null($row["COD_PEL_SEM"]))
            return (substr($row["COD_PEL_SEM"],-1) == 2 ? "2ème":"1er")." semestre ".$row["COD_ANU"]."/".substr($row["COD_ANU"]+1,-2);
    }

    private function setEctsCredits(array $row)
    {
        return (empty($row["NBR_CRD_ELP_EC"]) ? "0":$row["NBR_CRD_ELP_EC"]);
    }

    private function setCode(array $row)
    {
        return $row["COD_ELP_EC"];
    }

    private function setExternalId(array $row)
    {
        return $row["COD_ANU"]."-".$row["COD_ELP_EC"];
    }

    private function setRemarks(array $row)
    {
        return utf8_encode("
            Diplome : ".$row["COD_DIP"]." : ".$row["LIB_DIP"]."
            Etape :  ".$row["COD_ETP"]." : ".$row["LIB_ETP"]."
            Semestre :  ".$row["COD_ELP_SEM"]." : ".$row["LIB_ELP_SEM"]."
            UE :  ".$row["COD_ELP_UE"]." : ".$row["LIB_ELP_UE"]."
            Matiere : ".$row["COD_ELP_EC"]." : ".$row["LIB_ELP_EC"]);
    }
}