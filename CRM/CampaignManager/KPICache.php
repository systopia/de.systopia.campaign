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

require_once('CRM/CampaignManager/CampaignTree/Tree.php');

class CRM_CampaignManager_KPICache {

  /**
  * Check if caching is enabled
  */
  public static function isCacheEnabled() {
    $settings = CRM_CampaignManager_Config::getCMSettings();
    return !empty($settings['cache']);
  }

  /**
  * get a list of options for cache TTL
  */
  public static function getTTLOptions() {
    return array(
       ''         => E::ts("no caching"),
       '+1 hour'  => E::ts("cache for 1 hour"),
       '+1 day'   => E::ts("cache for 24 hours"),
       '+1 week'  => E::ts("cache for 1 week"),
    );
  }

  /**
  * Pre-cache all the given campaign IDs
  */
  public static function cacheCampaigns($campaign_ids) {
    self::clearCache();
    foreach ($campaign_ids as $campaign_id) {
      CRM_CampaignManager_KPI::getCampaignKPI($campaign_id);
    }
  }


  /**
  * clear ALL CampaignManager cache entries
  */
  public static function clearCache() {
    // PURGE stale entries
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache WHERE `group_name` = 'de.systopia.campaign'");
  }

  /**
  * fetch a valid record from the cache - according to the settings
  * @return valid KPI set or NULL if none found
  */
  public static function fetchFromCache($campaign_id) {
    if (!self::isCacheEnabled()) return;

    $path = "kpis_campaign_{$campaign_id}";

    // PURGE stale entries
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache
                                      WHERE `group_name` = 'de.systopia.campaign'
                                        AND `path` = %1
                                        AND `expired_date` < NOW()",
                                         array(1 => array($path, 'String')));

    // TRY AND FETCH A CURRENT ENTRY
    $data = CRM_Core_DAO::executeQuery('SELECT data, created_date
                                         FROM civicrm_cache
                                        WHERE `path` = %1
                                          AND `group_name` = "de.systopia.campaign"
                                          AND `expired_date` >= NOW()',
                                    array(1 => array($path, 'String')));
    if ($data->fetch()) {
       // error_log("CACHE HIT");
       $kpis = unserialize($data->data);
       $kpis["cache_info"] = array(
          "id"          => "cache_info",
          "title"       => ts("KPI Cache Timestamp", array('domain' => 'de.systopia.campaign')),
          "kpi_type"    => "date",
          "vis_type"    => "none",
          "description" => ts("Describes the exact time when this KPI data set was calculated. For more details have a look at the caching options.", array('domain' => 'de.systopia.campaign')),
          "value"       => date('Y-m-d H:i:s', strtotime($data->created_date)),
          "link"        => ""
       );
       return $kpis;
    } else {
       // error_log("CACHE MISS");
       return NULL;
    }
  }

  /**
  * Cache the given result in the cache
  */
  public static function pushToCache($campaign_id, $kpi) {
    if (!self::isCacheEnabled()) return;

    $path = "kpis_campaign_{$campaign_id}";
    $settings = CRM_CampaignManager_Config::getCMSettings();
    $expired_date = date('Y-m-d H:i:s', strtotime($settings['cache']));

    // INSERT a new record
    // error_log("CACHED UNTIL {$expired_date}");
    CRM_Core_DAO::executeQuery("INSERT IGNORE INTO civicrm_cache (`group_name`, `path`, `data`, `expired_date`, `created_date`)
                                            VALUES ('de.systopia.campaign', %1, %2, %3, NOW())",
                                         array(1 => array($path,           'String'),
                                               2 => array(serialize($kpi), 'String'),
                                               3 => array($expired_date,   'String')));
  }
}
