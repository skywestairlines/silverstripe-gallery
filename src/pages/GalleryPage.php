<?php

namespace SkyWest\Gallery\Pages;

use SilverStripe\Assets\Image;
use SkyWest\Gallery\Fields\GalleryUploadField;

class GalleryPage extends \Page
{
     private static $many_many = array(
        'Images' => Image::class
    );

    private static $has_one = [

    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab("Root.Main", [
            new GalleryUploadField('Images', 'Images')
        ]);
        return $fields;
    }
 }
