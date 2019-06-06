<?php


use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class Gallery_PageExtension extends DataExtension
{

    private static $many_many = array(
        'Images' => Image::class
    );

    public function updateCMSFields(FieldList $fields)
    {

        $fields->addFieldToTab('Root.Gallery', GalleryUploadField::create(
            'Images',
            '',
            $this->owner->OrderedImages()
        ));
    }

    public function OrderedImages()
    {

        list($parentClass, $componentClass, $parentField, $componentField, $table) = $this->owner->many_many('Images');

        return $this->owner->getManyManyComponents(
            'Images',
            '',
            "\"{$table}\".\"SortOrder\" ASC"
        );
    }
}
