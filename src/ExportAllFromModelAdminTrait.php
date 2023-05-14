<?php

namespace Sunnysideup\ExportAllFromModelAdmin;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

trait ExportAllFromModelAdminTrait
{
    protected $exportFields = [];

    protected $exportFieldLabels = [];

    protected $exportFieldLabelsExclude = [];

    private static $fields_to_exclude_from_export_always = [
        'BackLinks',
    ];

    private static $export_separator = '|||';

    private static $export_separator_replacer = '///';

    public function getExportFields(): array
    {
        if(Permission::check('ADMIN')) {
            //set to ten minutes
            Environment::setTimeLimitMax(600);
            $singleton = Injector::inst()->get($this->modelClass);
            if ($singleton) {
                $this->exportFieldLabelsExclude1 = Config::inst()->get($this->modelClass, 'fields_to_exclude_from_export') ?: [];
                $this->exportFieldLabelsExclude2 = Config::inst()->get(self::class, 'fields_to_exclude_from_export_always') ?: self::$fields_to_exclude_from_export_always;
                $this->exportFieldLabelsExclude = array_merge($this->exportFieldLabelsExclude1, $this->exportFieldLabelsExclude2);
                $this->generateExportFieldLabels($singleton);
                $this->exportFields = [];
                $this->generateDbExportFields();
                $this->generateCastingExportFields();
                $this->generateHasOneExportFields();
                $this->generateManyExportFields();

                if ($singleton->hasMethod('getFieldsToIncludeInExport')) {
                    $this->exportFields += $singleton->getFieldsToIncludeInExport();
                }

            // if(Director::isDev()) {
            //     foreach($this->exportFields as $fieldName => $title) {
            //         echo "\n'$fieldName',";
            //     }
            // }
            } else {
                $this->exportFields = parent::getExportFields();
            }

            ksort($this->exportFields);

            return $this->exportFields;
        } else {
            return parent::getExportFields();
        }
    }

    protected function generateDbExportFields()
    {
        $dbs = Config::inst()->get($this->modelClass, 'db');
        foreach (array_keys($dbs) as $fieldName) {
            if (! in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                $this->exportFields[$fieldName] = $this->exportFieldLabels[$fieldName] ?? $fieldName;
            }
        }
    }

    protected function generateCastingExportFields()
    {
        $casting = Config::inst()->get($this->modelClass, 'casting');
        foreach (array_keys($casting) as $fieldName) {
            if (! in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                $this->exportFields[$fieldName] = $this->exportFieldLabels[$fieldName] ?? $fieldName;
            }
        }
    }

    protected function generateHasOneExportFields()
    {
        $hasOne =
            (Config::inst()->get($this->modelClass, 'has_one') ?: []) +
            (Config::inst()->get($this->modelClass, 'belongs') ?: [])
        ;
        foreach ($hasOne as $fieldName => $type) {
            if (! in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                switch ($type) {
                    case Image::class:
                        $this->exportFields[$fieldName] = function ($rel) {
                            return Director::absoluteURL($rel->Link());
                        };

                        break;
                    case Member::class:
                        $this->exportFields[$fieldName] = function ($rel) {
                            return $rel->Email;
                        };

                        break;
                    default:
                        $this->exportFields[$fieldName] = function ($rel) {
                            return $rel->getTitle();
                        };
                }
            }
        }
    }

    protected function generateManyExportFields()
    {
        $rels =
            (Config::inst()->get($this->modelClass, 'has_many') ?: []) +
            (Config::inst()->get($this->modelClass, 'many_many') ?: []) +
            (Config::inst()->get($this->modelClass, 'belongs_many_many') ?: [])
        ;
        foreach (array_keys($rels) as $fieldName) {
            if (! in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                $this->exportFields[$fieldName] = function ($rels) {
                    $sep = Config::inst()->get(self::class, 'export_separator');
                    $sepReplacer = Config::inst()->get(self::class, 'export_separator_replacer');
                    $a = [];
                    foreach ($rels as $rel) {
                        $a[] = str_replace((string) $sep, (string) $sepReplacer, (string) $rel->getTitle());
                    }

                    return implode(' ' . $sep . ' ', $a);
                };
            }
        }
    }

    protected function generateExportFieldLabels($singleton)
    {
        $singleton->FieldLabels();
        foreach ($this->exportFieldLabels as $key => $name) {
            $this->exportFieldLabels[$key] = str_replace([',', '.'], '-', (string) $name);
        }
    }
}
