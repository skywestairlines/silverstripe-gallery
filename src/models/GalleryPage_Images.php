<?php

class GalleryPage_Images extends DataObject
{

    private static $db = array(
        'PageID' => 'Int',
        'ImageID' => 'Int',
        'Caption' => 'Text',
        'SortOrder' => 'Int'
    );
}
