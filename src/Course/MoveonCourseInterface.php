<?php
namespace PRayno\MoveOnCourseCatalogueBundle\Course;


interface MoveonCourseInterface
{
    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param $name
     * @param $row
     */
    public function __set($name, $row);
}