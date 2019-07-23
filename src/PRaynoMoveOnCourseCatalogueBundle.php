<?php
namespace PRayno\MoveOnCourseCatalogueBundle;

use PRayno\MoveOnCourseCatalogueBundle\DependencyInjection\PRaynoMoveOnCourseCatalogueExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PRaynoMoveOnCourseCatalogueBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new PRaynoMoveOnCourseCatalogueExtension();
        }
        return $this->extension;
    }
}