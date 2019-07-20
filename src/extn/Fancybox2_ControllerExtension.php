<?php

namespace SkyWest\Gallery\Extn;

use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;

class Fancybox2_ControllerExtension extends DataExtension
{

    public function onAfterInit()
    {
        // Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');

        Requirements::combine_files('skywest/ss-gallery:client/fancebox2.js', array(
            'skywest/ss-gallery:client/javascript/fancybox2/jquery.fancybox.js',
            'skywest/ss-gallery:client/javascript/fancybox2/GalleryPage.js',
        ));
        Requirements::combine_files('fancybox2.css', array(
            'skywest/ss-gallery: client/css/fancybox2/jquery.fancybox.css',
        ));
    }
}
