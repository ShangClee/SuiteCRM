<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

use SuiteCRM\Custom\Service\JjwgMapsGeocodeService;

#[\AllowDynamicProperties]
class LeadsJjwg_MapsLogicHook
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
}
