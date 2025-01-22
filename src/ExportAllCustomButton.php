<?php

namespace Sunnysideup\ExportAllFromModelAdmin;

use League\Csv\Writer;
use LogicException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\AbstractGridFieldComponent;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;

class ExportAllCustomButton extends GridFieldExportButton
{

    /**
     * Example:
     *
     * ```php
     * Member::class => [
     *     'Name' => [
     *         'FirstName',
     *         'Surname',
     *         'MyHasOneSalutation.Title',
     *     ],
     *     'Email' => 'Email',
     *     'MyHasOneRelation' => 'MyHasOneRelation.Title',
     *     'MyManyManyRelation' => 'MyManyManyRelation.Title',
     *     'MyManyManyRelation Nice Title' => 'MyManyManyRelation2.Title',
     * ],
     * ```
     * @var array
     */
    private static array $custom_exports = [];
    private static int $limit_to_lookups = 500;
    private static int $limit_to_join_tables = 100000;

    private static int $max_chars_per_cell = 200;
    private static $db_defaults = [
        'ID' => 'Int',
        'Created' => 'DBDatetime',
        'LastEdited' => 'DBDatetime',
    ];

    protected bool $hasCustomExport = false;
    protected array $dbCache = [];
    protected array $relCache = [];

    protected array $lookupTableCache = [];

    protected array $joinTableCache = [];
    protected string $exportSeparator = ' ||| ';


    /**
     * Generate export fields for CSV.
     *
     * @param GridField $gridField
     *
     * @return string
     */
    public function generateExportFileData($gridField): string
    {
        $modelClass = $gridField->getModelClass();
        $custom = Config::inst()->get(static::class, 'custom_exports');
        if (empty($custom[$modelClass])) {
            parent::generateExportFileData($gridField);
        }

        // set basic variables
        $this->hasCustomExport = true;
        $this->exportColumns = $custom[$modelClass];
        $this->exportSeparator = ' ' . Config::inst()->get(ExportAllFromModelAdminTraitSettings::class, 'export_separator') . ' ';
        $this->buildRelCache();

        // basics -- see parent::generateExportFileData
        $csvWriter = Writer::createFromFileObject(new \SplTempFileObject());
        $csvWriter->setDelimiter($this->getCsvSeparator());
        $csvWriter->setEnclosure($this->getCsvEnclosure());
        $csvWriter->setOutputBOM(Writer::BOM_UTF8);

        if (!Config::inst()->get(static::class, 'xls_export_disabled')) {
            $csvWriter->addFormatter(function (array $row) {
                foreach ($row as &$item) {
                    // [SS-2017-007] Sanitise XLS executable column values with a leading tab
                    if (preg_match('/^[-@=+].*/', $item ?? '')) {
                        $item = "\t" . $item;
                    }
                }
                return $row;
            });
        }


        //Remove GridFieldPaginator as we're going to export the entire list.
        $gridField->getConfig()->removeComponentsByType(GridFieldPaginator::class);
        $items = $gridField->getManipulatedList()->limit(null);

        // set header
        $columnData = array_keys($this->exportColumns);
        $csvWriter->insertOne($columnData);

        // add items
        foreach ($items as $item) {
            $columnData = $this->getDataRowForExport($item);
            $csvWriter->insertOne($columnData);
        }

        if (method_exists($csvWriter, 'toString')) {
            return $csvWriter->toString();
        }

        return (string)$csvWriter;
    }


    protected function getDataRowForExport($item)
    {
        $array = [];
        $maxCharsPerCell = Config::inst()->get(static::class, 'max_chars_per_cell');
        foreach ($this->exportColumns as $fieldOrFieldArray) {
            $v = $this->getDataRowForExportInner($item, $fieldOrFieldArray);
            $v = substr($v, 0, $maxCharsPerCell);
            $array[] = $v;
        }
        return $array;
    }

    protected function getDataRowForExportInner($item, $fieldOrFieldArray): string
    {
        if (!$fieldOrFieldArray) {
            return '';
        }
        if (is_array($fieldOrFieldArray)) {
            $array = [];
            foreach ($fieldOrFieldArray as $key => $field) {
                $v = '';
                if ($key !== intval($key)) {
                    $v .= $key . ': ';
                }
                $v .= $this->getDataRowForExportInner($item, $field);
                $array[] = $v;
            }
            return (string) implode($this->exportSeparator, array_filter($array));
        } elseif (strpos($fieldOrFieldArray, '.') !== false) {
            return (string) $this->fetchRelData($item, $fieldOrFieldArray);
        } else {
            $type = $this->fieldTypes($fieldOrFieldArray);
            if (strpos($type, 'Boolean') !== false) {
                return (string) ($item->$fieldOrFieldArray ? 'Yes' : 'No');
            }
            return (string) $item->$fieldOrFieldArray;
        }
    }


    protected function fetchRelData($item, string $hasOneString): string
    {
        $hasOneArray = explode('.', $hasOneString);
        $methodName = array_shift($hasOneArray);
        $foreignField = $hasOneArray[0];
        $relType = $this->getRelationshipType($methodName);
        $className = $this->getRelClassName($methodName);
        if (!isset($this->lookupTableCache[$className])) {
            $limit = Config::inst()->get(static::class, 'limit_to_lookups');
            $this->lookupTableCache[$className] = $className::get()->limit($limit)->map('ID', $foreignField)->toArray();
        }
        if ($relType === 'has_one') {
            // Check if data is already cached
            $fieldName = $methodName . 'ID';
            $id = (int) $item->$fieldName;
            if ($id === 0) {
                return '';
            }
            return (string) ($this->lookupTableCache[$className][$id] ?? 'error' . $className::get()->byID($id)?->$foreignField);
        } else {
            $result = [];
            // slow....
            if ($relType === 'has_many') {
                foreach ($item->$methodName()->column($foreignField) as $val) {
                    $result[] = $val;
                }
            } elseif ($relType === 'many_many') {
                if (!isset($this->joinTableCache[$className])) {
                    $rel = $item->$methodName();
                    $this->joinTableCache[$className] = [
                        'table' => $rel->getJoinTable(),
                        'local' => $rel->getLocalKey(),
                        'foreign' => $rel->getForeignKey(),
                    ];
                    $joinTable = $this->joinTableCache[$className]['table'];
                    $localIDField = $this->joinTableCache[$className]['local'];
                    $foreignIDField = $this->joinTableCache[$className]['foreign'];
                    $limit = Config::inst()->get(static::class, 'limit_to_join_tables');
                    $list = DB::query('SELECT "' . $localIDField . '", "' . $foreignIDField . '" FROM "' . $joinTable . '" LIMIT ' . $limit);
                    foreach ($list as $row) {
                        if (! isset($this->lookupTableCache[$joinTable][$row[$localIDField]])) {
                            $this->lookupTableCache[$joinTable][$row[$localIDField]] = [];
                        }
                        $this->lookupTableCache[$joinTable][$row[$localIDField]][] = $row[$foreignIDField];
                    }
                } else {
                    $joinTable = $this->joinTableCache[$className]['table'];
                    $foreignIDField = $this->joinTableCache[$className]['foreign'];
                }
                if (! empty($this->lookupTableCache[$joinTable][$item->ID])) {
                    foreach ($this->lookupTableCache[$joinTable][$item->ID] as $foreignID) {
                        $result[] = $this->lookupTableCache[$className][$foreignID] ?? '';
                    }
                }
            }
            return implode($this->exportSeparator, $result);
        }
    }

    protected function fieldTypes($fieldName)
    {

        if (count($this->dbCache) === 0) {
            $this->dbCache =
                Config::inst()->get(static::class, 'db_defaults') +
                Config::inst()->get(Member::class, 'db');
        }
        return $this->dbCache[$fieldName];
    }

    protected function getRelationshipType($methodName)
    {
        return $this->relCache[$methodName]['type'];
    }

    protected function getRelClassName($methodName)
    {
        return $this->relCache[$methodName]['class'];
    }

    protected function buildRelCache()
    {
        if (count($this->relCache) === 0) {

            foreach (['has_one', 'has_many', 'many_many'] as $relType) {
                foreach (Config::inst()->get(Member::class, $relType) as $methodName => $className) {
                    $this->relCache[$methodName] = [
                        'type' => $relType,
                        'class' => $className
                    ];
                }
            }
        }
    }
}
