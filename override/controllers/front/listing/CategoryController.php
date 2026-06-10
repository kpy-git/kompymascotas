<?php
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
class CategoryController extends CategoryControllerCore
{
    /*
    * module: kpycategory
    * date: 2026-06-03 13:41:53
    * version: 1.0.0
    */
    protected function getImage($object, $id_image)
    {
        $retriever = new ImageRetriever(
            $this->context->link
        );
        return $object->has_image_fixed ? $retriever->getImage($object, $id_image) : $object->image_link;
    }
}