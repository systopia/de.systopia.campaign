<?php
/*-------------------------------------------------------+
| de.systopia.campaign                                   |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres                                      |
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

require_once('CRM/CampaignTree/Tree.php');

class CRM_Campaign_KPICache {

   /**
    * fetch a valid record from the cache - according to the settings
    * @return valid KPI set or NULL if none found
    */
   public static function fetchFromCache($campaign_id) {
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
      $path = "kpis_campaign_{$campaign_id}";
      $ttl = "+24 hours";
      $expired_date = date('Y-m-d H:i:s', strtotime($ttl));

      // INSERT a new record
      // error_log("CACHED UNTIL {$expired_date}");
      CRM_Core_DAO::executeQuery("INSERT IGNORE INTO civicrm_cache (`group_name`, `path`, `data`, `expired_date`, `created_date`)
                                              VALUES ('de.systopia.campaign', %1, %2, %3, NOW())",
                                           array(1 => array($path,           'String'),
                                                 2 => array(serialize($kpi), 'String'),
                                                 3 => array($expired_date,   'String')));
   }
}
