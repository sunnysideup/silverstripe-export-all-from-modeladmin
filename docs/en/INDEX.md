# tl;dr

```php

use Sunnysideup\ExportAllFromModelAdmin\ExportAllFromModelAdminTrait;
class MyModelAdmin extends ModelAdmin
{
    use ExportAllFromModelAdminTrait;

    //...
}

```

If there are any fields that you would like to exclude from the export then you can
add

```php

class MyModelToExport extends DataObject
{
    //... some example fields listed here...
    private static $fields_to_exclude_from_export = [
        //...
        'Priority',
        'MenuTitle',
        'MetaTitle',
        'MetaDescription',
        'CanViewType',
        'CanEditType',
        'Version',
        'ExtraMeta',
        'ShowInMenus',
        'ShowInSearch',
        'Sort',
        'HasBrokenFile',
        'HasBrokenLink',
        'ReportClass',
        //...
    ];
    //...
}
```

To add fields:

```php

class MyModelToExport extends DataObject
{
    //... some example fields listed here...
    public function getFieldsToIncludeInExport(): array
    {
        return [
            'MyDBField1' => 'Better Name',
            'MyDBField2' => 'Something else',
            'MyHasOneRelation' => function($rel) {return $rel->AnotherTitle();},
            'MyManyRelation' => function($rels) {return implode(',', $rels->columnUnique('Foo'));},
        ]
    }
    //...
}
```


## second option

```php
<?php

namespace Nedc\App\ModelAdmin;

use Nedc\App\Dataextension\GroupExtension;
use Nedc\App\Dataobjects\PrimaryHealthNetwork;
use Nedc\App\Forms\GridFieldDeleteActionInNewColumn;
use Nedc\App\Forms\GridFieldEditButtonInNewColumn;
use Nedc\App\Forms\MemberEmailExportButton;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use Sunnysideup\ExportAllFromModelAdmin\ExportAllCustomButton;
use Sunnysideup\Moodle\DoMoodleThings;
use Sunnysideup\Moodle\Model\MoodleLog;

/**
 * Class \Nedc\App\ModelAdmin\QuickMemberManagement
 *
 */
class MyE extends ModelAdmin
{
    $gridField->getConfig()->addComponent(new ExportAllCustomButton('buttons-before-left'));
}


```

```yml

Sunnysideup\ExportAllFromModelAdmin\ExportAllCustomButton:
  custom_exports:
    SilverStripe\Security\Member:
      Created: 'Created'
      LastEdited: 'LastEdited'
      Name:
        - 'Salutation.Title'
        - 'FirstName'
        - 'Surname'

```
