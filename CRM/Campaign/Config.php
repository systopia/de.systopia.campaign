<?php
/*-------------------------------------------------------+
| Campaign Manager                                       |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Campaign_ExtensionUtil as E;

/**
 * General Campaing Manager Configuration
 *
 */
class CRM_Campaign_Config extends CRM_Core_Form {

  /**
   * get the list of KPIs keys enabled
   */
  public static function getActiveBuiltInKPIs() {
    $enabled = CRM_Core_BAO_Setting::getItem('CampaignManager', 'enabled_built_in_kpis');
    if ($enabled == NULL) {
      // i.e. first time
      $kpis = CRM_Campaign_KPI::builtInKPIs();
      $enabled = array_keys($kpis);
    }

    return $enabled;
  }

  /**
   * Set the list of KPIs keys enabled
   */
  public static function setActiveBuiltInKPIs($enabled) {
    // filter for actual KPIs
    $active_kpis = array();
    $all_kpis = CRM_Campaign_KPI::builtInKPIs();
    foreach ($all_kpis as $key => $label) {
      if (!empty($enabled[$key])) {
        $active_kpis[] = $key;
      }
    }
    CRM_Core_BAO_Setting::setItem($active_kpis, 'CampaignManager', 'enabled_built_in_kpis');
  }
}
