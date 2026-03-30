<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

use SuiteCRM\Custom\Service\JjwgMapsGeocodeService;

#[\AllowDynamicProperties]
class MeetingsJjwg_MapsLogicHook
{
    private $service;

    public function __construct()
    {
        $this->service = new JjwgMapsGeocodeService();
    }




    public function updateMeetingGeocodeInfo(&$bean, $event, $arguments)
    {
        // after_save
        $this->service->updateMeetingGeocodeInfo($bean);
    }
}
