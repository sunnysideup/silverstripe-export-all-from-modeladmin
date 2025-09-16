<?php

namespace Sunnysideup\ExportAllFromModelAdmin\Api;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use Sunnysideup\ExportAllFromModelAdmin\ExportAllFromModelAdminTraitSettings;

class AllFields
{
    use Injectable;
    use Configurable;
    use Extensible;
    protected string $modelClass = '';
    protected array $exportFields = [];

    protected array $exportFieldLabels = [];

    protected array $exportFieldLabelsExclude = [];

    private static array $db_defaults = [
        'ID' => 'Int',
        'Created' => 'DBDatetime',
        'LastEdited' => 'DBDatetime',
    ];

    public function __construct($modelClass, ?array $exportFieldLabelsExclude = [])
    {
        $this->modelClass = $modelClass;

        $this->exportFieldLabelsExclude = array_merge(
            $this->exportFieldLabelsExclude,
            $exportFieldLabelsExclude
        );
    }

    public function getExportFields(): array
    {
        $this->exportFields = [];
        $exclude1 = Config::inst()->get($this->modelClass, 'fields_to_exclude_from_export') ?: [];
        $exclude2 = Config::inst()->get(ExportAllFromModelAdminTraitSettings::class, 'fields_to_exclude_from_export_always') ?: [];
        $this->exportFieldLabelsExclude = array_merge($this->exportFieldLabelsExclude, $exclude1, $exclude2);
        $this->generateExportFieldLabels();
        $this->generateDbExportFields();
        $this->generateCastingExportFields();
        $this->generateHasOneExportFields();
        $this->generateManyExportFields();
        return $this->exportFields;
    }

    protected function generateDbExportFields()
    {
        $dbs = Config::inst()->get(static::class, 'db_defaults') +
            Config::inst()->get($this->modelClass, 'db');
        foreach (array_keys($dbs) as $fieldName) {
            if (!in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                $this->exportFields[$fieldName] = $this->exportFieldLabels[$fieldName] ?? $fieldName;
            }
        }
    }

    protected function generateCastingExportFields()
    {
        $casting = Config::inst()->get($this->modelClass, 'casting');
        foreach (array_keys($casting) as $fieldName) {
            if (!in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                $this->exportFields[$fieldName] = $this->exportFieldLabels[$fieldName] ?? $fieldName;
            }
        }
    }

    protected function generateHasOneExportFields()
    {
        $hasOne =
            (Config::inst()->get($this->modelClass, 'has_one') ?: []) +
            (Config::inst()->get($this->modelClass, 'belongs') ?: []);
        foreach ($hasOne as $methodName => $type) {
            if (!in_array($methodName, $this->exportFieldLabelsExclude, true)) {
                $fieldName = $methodName . 'ID';
                $this->exportFields[$fieldName] = $fieldName;
                switch ($type) {
                    case Image::class:
                        $this->exportFields[$methodName] = function ($rel) {
                            if ($rel && $rel->exists()) {
                                return Director::absoluteURL((string)$rel->Link());
                            }
                            return '(none)';
                        };

                        break;
                    case Member::class:
                        $this->exportFields[$methodName] = function ($rel) {
                            if ($rel && $rel->exists()) {
                                return $rel->Email;
                            }
                            return '(none)';
                        };

                        break;
                    default:
                        $this->exportFields[$methodName] = function ($rel) {
                            if ($rel && $rel->exists()) {
                                return $rel->getTitle();
                            }
                            return '(none)';
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
            (Config::inst()->get($this->modelClass, 'belongs_many_many') ?: []);
        $sep = Config::inst()->get(ExportAllFromModelAdminTraitSettings::class, 'export_separator');
        $sepReplacer = Config::inst()->get(ExportAllFromModelAdminTraitSettings::class, 'export_separator_replacer');
        foreach (array_keys($rels) as $fieldName) {
            if (!in_array($fieldName, $this->exportFieldLabelsExclude, true)) {
                $this->exportFields[$fieldName] =                function ($rels) use ($sep, $sepReplacer): string {
                    $a = [];
                    foreach ($rels as $rel) {
                        $a[] = str_replace((string) $sep, (string) $sepReplacer, (string) $rel->getTitle());
                    }

                    return implode(' ' . $sep . ' ', $a);
                };
            }
        }
    }

    protected function generateExportFieldLabels()
    {
        $singleton = Injector::inst()->get($this->modelClass);
        $singleton->FieldLabels();
        foreach ($this->exportFieldLabels as $key => $name) {
            $this->exportFieldLabels[$key] = str_replace([',', '.', "\t", ";"], '-', (string) $name);
        }
    }
}
