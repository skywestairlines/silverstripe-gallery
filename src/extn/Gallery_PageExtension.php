<?php

namespace SkyWest\Gallery\Extn;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SkyWest\Gallery\Fields\GalleryUploadField;

class Gallery_PageExtension extends DataExtension
{

    private static $many_many = array(
        'Images' => Image::class
    );

    private static $has_one = [

    ];

    // public function updateCMSFields(FieldList $fields)
    // {

    //     $fields->addFieldToTab('Root.Gallery', GalleryUploadField::create(
    //         'Images',
    //         '',
    //         $this->owner->OrderedImages()
    //     ));
    // }

    // public function OrderedImages()
    // {
    //     // dump($this->owner->manyMany());
    //     $Images = array_pad($this->owner->manyMany('Images'), 5, null);
    //     list($parentClass, $componentClass, $parentField, $componentField, $table) = $Images;

    //     return $this->owner->manyMany('Images') ?: null;

    //     // return $this->owner->getManyManyComponents(
    //     //     'Images',
    //     //     '',
    //     //     "\"{$table}\".\"SortOrder\" ASC"
    //     // );
    // }
}
