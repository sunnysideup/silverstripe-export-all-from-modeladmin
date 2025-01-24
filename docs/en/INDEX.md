# tl;dr


## Add to specific Model Admin
```php

use Sunnysideup\ExportAllFromModelAdmin\ExportAllFromModelAdminTrait;
class MyModelAdmin extends ModelAdmin
{
    use ExportAllFromModelAdminTrait;

    //...
}

```
### Excluding / adding fields to full set of data... 

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


## Adding Export Button to GridField

```php
use Sunnysideup\ExportAllFromModelAdmin\ExportAllCustomButton;
//...
$gridField->getConfig()->addComponent(new ExportAllCustomButton('buttons-before-left'));
//...

```

You can set all sorts of export custom fields for any class.

## Setting export all to specific classes

These will be added to any modeladmins or other gridfields where an export button is already present for the class. 

```yml

Sunnysideup\ExportAllFromModelAdmin\ExportAllCustomButton:
  custom_exports:
    Foo\Bar\Class1: '*'
    Foo\Bar\Class2: '*'
    SilverStripe\Security\Member:
      Created: 'Created'
      LastEdited: 'LastEdited'
      Name:
        - 'Salutation.Title'
        - 'FirstName'
        - 'Surname'

```
