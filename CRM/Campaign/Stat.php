<?php

class CRM_Campaign_Stat {

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
    $query = "SELECT t1.activity_type_id, at1.name, t1.grouping, sum(t1.counter) counter FROM
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
                  value id, name
                FROM civicrm_option_value
                WHERE option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_type')) at1 ON at1.id = t1.activity_type_id
              GROUP BY t1.activity_type_id, at1.name, t1.grouping
              ORDER BY at1.name, ss.sequence";
    $params = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    return $dao->fetchAll();
  }

  public static function calculateActivityStats($kpi, $campaign_id, $children) {
    $stats = self::activityReport($campaign_id, $children);
    return $kpi;
  }

}
