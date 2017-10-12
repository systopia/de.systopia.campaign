<?php
/*-------------------------------------------------------+
| CAMPAIGN MANAGER                                       |
| Copyright (C) 2015-2017 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

class CRM_Utils_CampaignCustomisationHooks {
  static $null = NULL;

  static function campaign_kpis($campaign_id, &$kpi_list, $level) {
    return CRM_Utils_Hook::singleton()->invoke(3, $campaign_id, $kpi_list, $level, self::$null, self::$null, self::$null, 'civicrm_campaignKpis');
  }

}
