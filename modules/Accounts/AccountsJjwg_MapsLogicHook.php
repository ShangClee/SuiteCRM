<?php

// custom/modules/Accounts/AccountsJjwg_MapsLogicHook.php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

use SuiteCRM\Custom\Service\AccountGeocodeService;

#[\AllowDynamicProperties]
class AccountsJjwg_MapsLogicHook
{
    private $service;

    public function __construct()
    {
        $this->service = new AccountGeocodeService();
    }

    public function updateGeocodeInfo(&$bean, $event, $arguments)
    {
        // before_save
        $this->service->updateGeocodeInfo($bean);
    }

    public function updateRelatedProjectGeocodeInfo(&$bean, $event, $arguments)
    {
        // after_save
        $this->service->syncRelatedProjectGeocodes($bean);
    }

    public function updateRelatedOpportunitiesGeocodeInfo(&$bean, $event, $arguments)
    {
        // after_save
        $this->service->syncRelatedOpportunitiesGeocodes($bean);
    }

    public function updateRelatedCasesGeocodeInfo(&$bean, $event, $arguments)
    {
        // after_save
        $this->service->syncRelatedCasesGeocodes($bean);
    }

    public function updateRelatedMeetingsGeocodeInfo(&$bean, $event, $arguments)
    {
        // after_save
        $this->service->updateRelatedMeetingsGeocodeInfo($bean);
    }

    public function addRelationship(&$bean, $event, $arguments)
    {
        // after_relationship_add
        // $arguments['module'], $arguments['related_module'], $arguments['id'] and $arguments['related_id']
        // The original code was pulling $arguments['module'] + $arguments['id'] instead of related, so we pass module and id
        if (isset($arguments['module']) && isset($arguments['id'])) {
            $this->service->syncRelationship($arguments['module'], $arguments['id']);
        }
    }

    public function deleteRelationship(&$bean, $event, $arguments)
    {
        // after_relationship_delete
        if (isset($arguments['module']) && isset($arguments['id'])) {
            $this->service->syncRelationship($arguments['module'], $arguments['id']);
        }
    }
}
