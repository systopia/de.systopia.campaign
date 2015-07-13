<?php

class CRM_Utils_CampaignCustomisationHooks {
  static $null = NULL;

  static function campaign_kpis($campaign_id, &$kpi_list, $level) {
    return CRM_Utils_Hook::singleton()->invoke(3, $campaign_id, $kpi_list, $level, self::$null, self::$null, self::$null, 'civicrm_campaign_kpis');
  }

}
