<?php
/*-------------------------------------------------------+
| CAMPAIGN MANAGER                                       |
| Copyright (C) 2015-2017 SYSTOPIA                       |
| Author: N. Bochan                                      |
|         B. Endres (endres@systopia.de)                 |
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

require_once 'CRM/CampaignTree/Tree.php';

function _civicrm_api3_campaign_stat_activity_counter(&$params) {
  $params['id'] = array(
    'name' => 'id',
    'title' => E::ts('Campaign ID'),
    'description' => E::ts('ID of parent campaign'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => 0,
  );
}

function civicrm_api3_campaign_stat_activity_counter($params) {
  try {
    $campaignId = $params['id'];
    $campaigns = CRM_CampaignManager_CampaignTree_Tree::getCampaignIds($campaignId, 99);
    $children = $campaigns['children'];
    $stat = CRM_CampaignManager_KPIActivity::activityCounter($campaignId, $children);
    return civicrm_api3_create_success($stat, $params);
  }
  catch (Exception $exception) {
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
  $params['id'] = array(
    'name' => 'id',
    'title' => E::ts('Campaign ID'),
    'description' => E::ts('ID of parent campaign'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => 0,
  );
}

function civicrm_api3_campaign_stat_activity_report($params) {
  try {
    $campaignId = $params['id'];
    $campaigns = CRM_CampaignManager_CampaignTree_Tree::getCampaignIds($campaignId, 99);
    $children = $campaigns['children'];
    $stat = CRM_CampaignManager_KPIActivity::activityReport($campaignId, $children);
    return civicrm_api3_create_success($stat, $params);
  }
  catch (Exception $exception) {
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

function _civicrm_api3_campaign_stat_activity_sequence(&$params) {
}

function civicrm_api3_campaign_stat_activity_sequence($params) {
  try {
    return civicrm_api3_create_success(CRM_CampaignManager_KPIActivity::sequence(), $params);
  }
  catch (Exception $exception) {
    $data = array(
      'params' => $params,
      'exception' => array(
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'object' => $exception,
      ),
    );
    return civicrm_api3_create_error('Problem with getting sequence', $data);
  }
}
