<?php

namespace Sunnysideup\ExportAllFromModelAdmin;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class ExportAllFromModelAdminTraitSettings
{
    use Configurable;
    use Extensible;
    use Injectable;
    private static $fields_to_exclude_from_export_always = [
        'BackLinks',
        'UUID',
        'Password',
    ];

    private static $export_separator = '|||';

    private static $export_separator_replacer = '///';

}
