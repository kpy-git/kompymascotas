<?php

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;

class CategoryController extends CategoryControllerCore
{
    protected function getImage($object, $id_image)
    {
        $retriever = new ImageRetriever(
            $this->context->link
        );

        return $object->has_image_fixed ? $retriever->getImage($object, $id_image) : $object->image_link;
    }
}