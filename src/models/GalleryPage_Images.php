<?php

namespace SkyWest\Gallery\Models;

use SilverStripe\ORM\DataObject;

class GalleryPage_Images extends DataObject
{

    private static $table_name = 'GalleryPage_Images';
    private static $db = array(
        'PageID' => 'Int',
        'ImageID' => 'Int',
        'Caption' => 'Text',
        'SortOrder' => 'Int'
    );
}
