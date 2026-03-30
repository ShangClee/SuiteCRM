<?php


if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

use SuiteCRM\Custom\Service\JjwgMapsGeocodeService;

#[\AllowDynamicProperties]
class CasesJjwg_MapsLogicHook
{
    private $service;
    public function __construct()
    {
        $this->service = new JjwgMapsGeocodeService();
    }




    public function updateGeocodeInfo(&$bean, $event, $arguments)
    {
        // before_save
        $this->service->updateGeocodeInfo($bean);
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
        if (isset($arguments['module']) && isset($arguments['id'])) {
            $this->service->syncRelationship($arguments['module'], $arguments['id']);
        }
    }

    public function deleteRelationship(&$bean, $event, $arguments)
    {
        // after_relationship_delete
        // $arguments['module'], $arguments['related_module'], $arguments['id'] and $arguments['related_id']
        if (isset($arguments['module']) && isset($arguments['id'])) {
            $this->service->syncRelationship($arguments['module'], $arguments['id']);
        }
    }
}
