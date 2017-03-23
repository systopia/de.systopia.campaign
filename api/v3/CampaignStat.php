<?php
/*-------------------------------------------------------+
| de.systopia.campaign                                   |
| Copyright (C) 2015 SYSTOPIA                            |
| Author: N. Bochan (bochan -at- systopia.de)            |
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

function _civicrm_api3_campaign_stat_activity_counter(&$params) {
  $params['id']['api.required'] = 1;
}

function civicrm_api3_campaign_stat_activity_counter($params) {
  try {
    $campaignId = $params['id'];
    $stat = CRM_Campaign_Stat::activityCounter($campaignId);
    return civicrm_api3_create_success($stat, $params);
  } catch (Exception $exception) {
    $data = array(
      'params' => $params,
      'exception' => array(
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'object' => $exception,
      ),
    );
    return civicrm_api3_create_error('Problem with generating stats for campaign', $data);
  }
}

function _civicrm_api3_campaign_stat_activity_report(&$params) {
  $params['id']['api.required'] = 1;
}

function civicrm_api3_campaign_stat_activity_report($params) {
  try {
    $campaignId = $params['id'];
    $stat = CRM_Campaign_Stat::activityReport($campaignId);
    return civicrm_api3_create_success($stat, $params);
  } catch (Exception $exception) {
    $data = array(
      'params' => $params,
      'exception' => array(
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'object' => $exception,
      ),
    );
    return civicrm_api3_create_error('Problem with generating stats for campaign', $data);
  }
}
