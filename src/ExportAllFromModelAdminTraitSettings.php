<?php

declare(strict_types=1);

namespace Sunnysideup\ExportAllFromModelAdmin;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

class ExportAllFromModelAdminTraitSettings
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $fields_to_exclude_from_export_always = [
        'BackLinks',
        'UUID',
        'Password',
        'PasswordSalt',
        'Salt',
        'PasswordEncryption',
        'PasswordExpiryDays',
        'PasswordExpiry',
        'IPAddress',
        'LinkTracking',
        'FileTracking',
    ];

    private static $export_separator = '|||';

    private static $export_separator_replacer = '///';
}
