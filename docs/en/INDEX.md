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
    private static $fields_to_exclude_in_export = [
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
If there are any fields you would like to add:

```php

class MyModelToExport extends DataObject
{
    //... some example fields listed here...
    private static $fields_to_include_in_export = [
        //...
        'MyDifficultRelation' => function($rel) {return $rel->getOtherStuff();},
        'MyDifficultRelations' => function($rels) {return implode(',', $rels->getOtherStuff()->columnUnique();},
        'MyDate.Long' => 'Nice date for you',
        //...
    ];
    //...
}
