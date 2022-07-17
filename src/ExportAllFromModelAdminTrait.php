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
                        case File::class:
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
            foreach($rels as $fieldName => $type) {
                if(! in_array($fieldName, $exclude)) {
                    $returnArray[$fieldName] = function($rels) {
                        $sep = Config::inst()->get(self::class, 'separator');
                        $sepReplacer = Config::inst()->get(self::class, 'separator_replacer');
                        $a = [];
                        foreach($rels as $rel) {
                            switch(get_class($rel)) {
                                case File::class:
                                case Image::class:
                                    $a[] = str_replace($sep, $sepReplacer, Director::absoluteURL($rel->Link()));
                                    break;
                                case Member::class:
                                    $a[] = str_replace($sep, $sepReplacer, $rel->Email);
                                    break;
                                default:
                                    $a[] = str_replace($sep, $sepReplacer, $rel->getTitle());
                            }
                        }
                        return implode(' '.$sep.' ', $a);
                    };
                }
            }
            if($singleton->hasMethod('getFieldsToIncludeInExport')) {
                $returnArray += $singleton->getFieldsToIncludeInExport();
            }
            // if(Director::isDev()) {
            //     foreach($returnArray as $fieldName => $title) {
            //         echo "\n'$fieldName',";
            //     }
            // }
        } else {
            $returnArray = parent::getExportFields();
        }
        ksort($returnArray);

        return $returnArray;
    }
}
