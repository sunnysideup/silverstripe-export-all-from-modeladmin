<?php

namespace Sunnysideup\ExportAllFromModelAdmin;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\ExportAllFromModelAdmin\Api\AllFields;

trait ExportAllFromModelAdminTrait
{

    private array $exportFields = [];

    public function getExportFields(): array
    {
        if (Permission::check('ADMIN')) {
            //set to ten minutes
            Environment::setTimeLimitMax(600);
            $singleton = Injector::inst()->get($this->modelClass);
            if ($singleton) {
                $allFieldsProvider = AllFields::create($this->modelClass);
                $this->exportFields = $allFieldsProvider->getExportFields();
                if ($singleton->hasMethod('getFieldsToIncludeInExport')) {
                    $this->exportFields += $singleton->getFieldsToIncludeInExport();
                }
            } else {
                $this->exportFields = parent::getExportFields();
            }
        } else {
            $this->exportFields = parent::getExportFields();
        }

        return $this->exportFields;
    }
}
