<?php

namespace Sunnysideup\ExportAllFromModelAdmin;

use SilverStripe\Core\Config\Config;

use SilverStripe\Core\Injector\Injector;

use SilverStripe\Assets\Image;

use SilverStripe\Security\Member;

use SilverStripe\Control\Director;


trait ExportAllFromModelAdminTrait
{

    private static $separator = '|||';

    private static $separator_replacer = '///';

    public function getExportFields() : array
    {
        $className = $this->modelClass;
        $singleton = Injector::inst()->get($className);
        if($singleton) {
            $exclude = Config::inst()->get($className, 'fields_to_exclude_from_export') ?:[];
            $returnArray = [];
            $fieldLabels = $singleton->FieldLabels();
            $dbs = Config::inst()->get($className, 'db');
            foreach(array_keys($dbs) as $fieldName) {
                if(! in_array($fieldName, $exclude)) {
                    $returnArray[$fieldName] = $fieldLabels[$fieldName] ?? $fieldName;
                }
            }
            $casting = Config::inst()->get($className, 'casting');
            foreach(array_keys($casting) as $fieldName) {
                if(! in_array($fieldName, $exclude)) {
                    $returnArray[$fieldName] = $fieldLabels[$fieldName] ?? $fieldName;
                }
            }
            $hasOne =
                (Config::inst()->get($className, 'has_one') ? : []) +
                (Config::inst()->get($className, 'belongs') ? : [])
            ;
            foreach($hasOne as $fieldName => $type) {
                if(! in_array($fieldName, $exclude)) {
                    switch($type) {
                        case Image::class:
                            $returnArray[$fieldName] = function($rel) {return Director::absoluteURL($rel->Link());};
                            break;
                        case Member::class:
                            $returnArray[$fieldName] = function($rel) {return $rel->Email;};
                            break;
                        default:
                            $returnArray[$fieldName] = function($rel) {return $rel->getTitle();};
                    }
                }
            }
            $rels =
                (Config::inst()->get($className, 'has_many') ? : []) +
                (Config::inst()->get($className, 'many_many') ? : []) +
                (Config::inst()->get($className, 'belongs_many_many') ? : [])
            ;
            foreach(array_keys($rels) as $fieldName) {
                if(! in_array($fieldName, $exclude)) {
                    $returnArray[$fieldName] = function($rels) {
                        $sep = Config::inst()->get(self::class, 'separator');
                        $sepReplacer = Config::inst()->get(self::class, 'separator_replacer');
                        $a = [];
                        foreach($rels as $rel) {
                            $a[] = str_replace($sep, $sepReplacer, $rel->getTitle());
                        }
                        return implode(' '.$sep.' ', $a);
                    };
                }
            }
        } else {
            $returnArray = parent::getExportFields();
        }
        ksort($returnArray);

        return $returnArray;
    }
}
