<?php
namespace PRayno\MoveOnCourseCatalogueBundle\Logic;

use PRayno\MoveOnApiBundle\MoveOnApi;
use PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourseInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MoveOnProcess
{
    private $moveonCourse;
    private $moveOnApi;
    private $updateCoursesModifiedBy=[];
    private $courseIdentifierRegex="";

    public function __construct(MoveonCourseInterface $moveonCourse,MoveOnApi $moveOnApi, array $updateCoursesModifiedBy,string $courseIdentifierRegex)
    {
        $this->moveOnApi = $moveOnApi;
        $this->moveonCourse = $moveonCourse;
        $this->updateCoursesModifiedBy = $updateCoursesModifiedBy;
        $this->courseIdentifierRegex = $courseIdentifierRegex;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function retrieveMoveOnCourses($filter=[])
    {
        $courses = $this->moveOnApi->findBy("catalogue-course",$filter,["id"=>"asc"],100000,1,["id",$this->moveonCourse->getIdentifier(),"last_modified_by"],"eng","true","queue",60);
        $moveonCourses=[];
        $attributeId="catalogue_course.id";
        $attributeLastModifyBy = "catalogue_course.last_modified_by";
        $attributeIdentifier = "catalogue_course.".$this->moveonCourse->getIdentifier();
        foreach ($courses->rows as $course)
        {
            $identifier = $course->$attributeIdentifier->__toString();

            if (empty($identifier))
                continue;

            $moveonCourses[$identifier]=["id"=>$course->$attributeId->__toString(),"last_modified_by"=>$course->$attributeLastModifyBy->__toString()];
        }

        return $moveonCourses;
    }

    /**
     * @param array $csvCourses
     * @param array $moveonCourses
     * @param SymfonyStyle $io
     * @param bool $dump
     * @return array
     */
    public function synchronize(array $csvCourses, array $moveonCourses,SymfonyStyle $io,$dump=false)
    {
        $stats = ["create"=>0,"update"=>0];
        foreach ($csvCourses as $line=>$csvCourse)
        {
            $attributes = $csvCourse->getAttributes();
            $identifier = $csvCourse->getIdentifier();

            // Try to see if entry already exists
            if (isset($moveonCourses[$attributes[$identifier]]))
            {
                // Do not process courses that were modified by another user
                if (!empty($this->updateCoursesModifiedBy) && !in_array($moveonCourses[$attributes[$identifier]]["last_modified_by"],$this->updateCoursesModifiedBy))
                    continue;

                $attributes["id"] = $moveonCourses[$attributes[$identifier]]["id"];
            }

            if (isset($attributes["id"]))
                $stats["update"]++;
            else
                $stats["create"]++;

            if ($dump === true)
                continue;

            // Publish to MoveON
            try {
                $this->moveOnApi->save("catalogue-course",$attributes);
                $io->success(date("Y-m-d H:i:s")." - CSV line $line : Course ".(isset($attributes["id"])?"updated":"created")." - ".$attributes[$identifier]);
            }
            catch (\Exception $exception)
            {
                $io->error(date("Y-m-d H:i:s")." - CSV line $line : ".$exception->getMessage());
            }
        }

        return $stats;
    }

    public function deactivateDeletedCourses(array $csvCourses,array $moveonCourses, SymfonyStyle $io)
    {
        foreach ($moveonCourses as $identifier=>$moveonCourse)
        {
            if (!empty($this->updateCoursesModifiedBy) && !in_array($moveonCourse["last_modified_by"],$this->updateCoursesModifiedBy))
                continue;

            if (!in_array($identifier,$csvCourses))
            {
                if (preg_match($this->courseIdentifierRegex,$identifier)===1)
                {
                    try {
                        $this->moveOnApi->save("catalogue-course",["id"=>$moveonCourse["id"],"is_active"=>0]);
                        $io->success(date("Y-m-d H:i:s")." - Course ".$moveonCourse["id"]." ($identifier) deactivated.");
                    }
                    catch (\Exception $exception)
                    {
                        $io->error(date("Y-m-d H:i:s")." Could not deactivate course".$moveonCourse["id"]." ($identifier)");
                    }
                }
            }
        }
    }
}