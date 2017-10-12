<?php
/*-------------------------------------------------------+
| CAMPAIGN MANAGER                                       |
| Copyright (C) 2015-2017                                |
| Author: T. Pietrzkowski                                |
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

class CRM_Campaign_Stat {

  /**
   * Create or drop configuration tables for statistics.
   *
   * @param int $currentActivitiesParam
   * @param int $newActivitiesParam
   */
  public static function set($currentActivitiesParam, $newActivitiesParam) {
    if ($currentActivitiesParam != $newActivitiesParam) {
      if ($newActivitiesParam) {
        self::create();
      }
      else {
        self::drop();
      }
    }
  }

  /**
   * Create default configuration for statistics.
   */
  private static function create() {
    $config = CRM_Core_Config::singleton();
    $sqlfile = dirname(__FILE__) . '/../../sql/activity-kpi-install.sql';
    CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, FALSE);
  }

  /**
   * Drop configuration for statistics.
   */
  private static function drop() {
    $config = CRM_Core_Config::singleton();
    $sqlfile = dirname(__FILE__) . '/../../sql/activity-kpi-uninstall.sql';
    CRM_Utils_File::sourceSQLFile($config->dsn, $sqlfile, NULL, FALSE);
  }

  /**
   * Calculate counts of activities.
   *
   * @param int $campaignId
   * @param array $children
   *
   * @return array
   */
  public static function activityCounter($campaignId, $children) {
    $ids = array_merge(array($campaignId), array_keys($children));
    $query = "SELECT
                a.activity_type_id, a.status_id, count(a.id) counter
              FROM civicrm_activity a
                JOIN civicrm_campaign_config_activity_type ca ON ca.activity_type_id = a.activity_type_id
                JOIN civicrm_campaign_config_activity_status s ON s.activity_type_id = a.activity_type_id AND s.status_id = a.status_id
                WHERE a.is_test = 0 AND a.campaign_id IN (" . implode(' ,', $ids) . ")
              GROUP BY a.activity_type_id, a.status_id";
    $params = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    return $dao->fetchAll();
  }

  /**
   * Calculate counts of activities and prepared as a report.
   *
   * @param int $campaignId
   * @param array $children
   *
   * @return array
   */
  public static function activityReport($campaignId, $children) {
    $ids = array_merge(array($campaignId), array_keys($children));
    $query = "SELECT t1.activity_type_id, at1.label, t1.grouping, sum(t1.counter) counter FROM
                (SELECT
                  a.activity_type_id, a.status_id, s.grouping, count(a.id) counter
                FROM civicrm_activity a
                  JOIN civicrm_campaign_config_activity_type ca ON ca.activity_type_id = a.activity_type_id
                  JOIN civicrm_campaign_config_activity_status s ON s.activity_type_id = a.activity_type_id AND s.status_id = a.status_id
                  WHERE a.is_test = 0 AND a.campaign_id IN (" . implode(' ,', $ids) . ")
                GROUP BY a.activity_type_id, a.status_id, s.grouping
                UNION
                SELECT at2.activity_type_id, as2.status_id, as2.grouping, 0 AS counter
                FROM civicrm_campaign_config_activity_type at2
                  JOIN civicrm_campaign_config_activity_status as2 ON as2.activity_type_id = at2.activity_type_id
                WHERE at2.is_fixed = 1
                ) t1
                JOIN civicrm_campaign_config_status_sequence ss ON t1.grouping = ss.grouping
                JOIN (SELECT
                  value id, label
                FROM civicrm_option_value
                WHERE option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_type')) at1 ON at1.id = t1.activity_type_id
              GROUP BY t1.activity_type_id, at1.label, t1.grouping
              ORDER BY at1.label, ss.sequence";
    $params = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    return $dao->fetchAll();
  }

  public static function sequence() {
    $query = "SELECT grouping FROM civicrm_campaign_config_status_sequence ORDER BY sequence";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = array();
    while ($dao->fetch()) {
      $result[] = $dao->grouping;
    }
    return $result;
  }

  public static function calculateActivityStats(&$kpi, $campaign_id, $children) {
    $stats = self::activityReport($campaign_id, $children);
    $sequence = CRM_Campaign_Stat::sequence();
    $report = array();
    $columns = array();
    $activityTypes = array();
    $totalPerRow = array();
    $totalPerColumn = array();
    $existingGrouping = array();
    foreach ($stats as $stat) {
      $report[$stat['label']][$stat['grouping']] = $stat['counter'];
      $activityTypes[$stat['label']] = $stat['label'];
      $columns[$stat['grouping']] = $stat['grouping'];
      $totalPerRow[$stat['label']] += $stat['counter'];
      $totalPerColumn[$stat['grouping']] += $stat['counter'];
      $existingGrouping[$stat['grouping']] = $stat['grouping'];
    }
    $header = array(E::ts("Activity"));
    $body = array();
    $footer = array();
    $total = 0;
    foreach ($sequence as $i => $grouping) {
      if (!in_array($grouping, $existingGrouping)) {
        unset($sequence[$i]);
      }
    }
    foreach ($sequence as $i => $grouping) {
      $header[] = $grouping;
    }
    $header[] = E::ts('Total');
    foreach ($activityTypes as $type) {
      $body[$type] = array($type);
      foreach ($sequence as $i => $grouping) {
        $body[$type][$grouping] = $report[$type][$grouping];
      }
      $body[$type]['total'] = $totalPerRow[$type];
      $total += $totalPerRow[$type];
    }
    $footer[] = E::ts('Total');
    foreach ($sequence as $i => $grouping) {
      $footer[] = $totalPerColumn[$grouping];
    }
    $footer[] = $total;

    $kpi["actiontable"] = array(
      "id" => "actiontable",
      "description" => E::ts('Statistics on associated activities'),
      "kpi_type" => "hidden",
      "link" => "",
      "title" => E::ts('Statistics on associated activities'),
      "vis_type" => "table",
      "value" => array(
        "header" => array(
          "comment" => "This is a header :-)",
          "cells" => array(
            "comment" => "This is a comment to cells ;-)",
            "value" => $header,
          ),
        ),
        "body" => array(
          "comment" => "List of counters",
          "cells" => array(
            "value" => $body,
          ),
        ),
        "footer" => array(
          "cells" => array(
            "value" => $footer,
          ),
        ),
      ),
    );
  }

}
