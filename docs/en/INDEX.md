# tl;dr

There are a number of ways to increase the export capabilities from the CMS. 

## Add to specific Model Admin

You will need to add the trait to any modeladmin:

```php

use Sunnysideup\ExportAllFromModelAdmin\ExportAllFromModelAdminTrait;
class MyModelAdmin extends ModelAdmin
{
    use ExportAllFromModelAdminTrait;

    //...
}

```

### Excluding / adding fields

If there are any fields that you would like to add / exclude from the full set of data then you can
use the following code:

#### To exclude

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

#### To include

You can edit the specific class and add fields like this: 

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

The wildcard `*` means that all the fields are exported. 

Fields are defined as `db`, `casted`, `has_one`, `has_many`, `many_many` and `belongs_many_many`

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
