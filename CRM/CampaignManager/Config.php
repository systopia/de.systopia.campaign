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

use CRM_CampaignManager_ExtensionUtil as E;

/**
 * General Campaing Manager Configuration
 *
 */
class CRM_CampaignManager_Config extends CRM_Core_Form {

  /**
   * Get the generat CampaignManager Settings
   */
  public static function getCMSettings() {
    $settings = Civi::settings()->get('campaign_mgr_settings');
    if ($settings == NULL) {
      $settings = [];
    }

    return $settings;
  }

  /**
   * Set the generat CampaignManager Settings
   */
  public static function setCMSettings($settings) {
    CRM_Core_BAO_Setting::setItem($settings, 'CampaignManager', 'campaign_mgr_settings');
  }

  /**
   * get the list of KPIs keys enabled
   */
  public static function getActiveBuiltInKPIs() {
    $enabled = Civi::settings()->get('enabled_built_in_kpis');
    if ($enabled == NULL) {
      // i.e. first time: enable some KPIs.
      $enabled = ['contribution_count', 'revenue', 'revenue_breakdown', 'donation_heartbeat'];
    }

    return $enabled;
  }

  /**
   * Set the list of KPIs keys enabled
   */
  public static function setActiveBuiltInKPIs($enabled) {
    // filter for actual KPIs
    $active_kpis = ['dummy'];
    $all_kpis = CRM_CampaignManager_KPI::builtInKPIs();
    foreach ($all_kpis as $key => $label) {
      if (!empty($enabled[$key])) {
        $active_kpis[] = $key;
      }
    }
    CRM_Core_BAO_Setting::setItem($active_kpis, 'CampaignManager', 'enabled_built_in_kpis');
  }

  /**
   * Install a scheduled job if there isn't one already
   */
  public static function installScheduledJob() {
    // find all scheduled jobs calling CampaignKpi.cache
    $query = civicrm_api3('Job', 'get', [
      'api_entity'   => 'CampaignKpi',
      'api_action'   => 'cache',
      'option.limit' => 0]);
    $jobs = $query['values'];

    if (empty($jobs)) {
      // none found? create a new one
      civicrm_api3('Job', 'create', [
        'api_entity'    => 'CampaignKpi',
        'api_action'    => 'cache',
        'run_frequency' => 'Daily',
        'name'          => E::ts('Fill CM KPI Cache'),
        'description'   => E::ts("Caches the CampaignManager's KPI cache, if caching is enabled."),
        'is_active'     => '0']);
    }
  }
}
