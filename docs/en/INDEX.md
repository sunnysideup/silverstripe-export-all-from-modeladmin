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
use Sunnysideup\ExportAllFromModelAdmin\ExportAllCustomButton;
//...
$gridField->getConfig()->addComponent(new ExportAllCustomButton('buttons-before-left'));
//...

```

You can set all sorts of export custom fields for any class.

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
