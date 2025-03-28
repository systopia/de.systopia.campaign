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

// Using CRM_CampaignManager_ExtensionUtil namespace for translations (E::ts)
use CRM_CampaignManager_ExtensionUtil as E;

// Include the required class for campaign tree operations
require_once 'CRM/CampaignManager/CampaignTree/Tree.php';

/**
 * Define API parameters for the campaign stat activity counter function.
 * This function sets up the parameters expected by the API.
 *
 * @param array $params The parameters array
 */
function _civicrm_api3_campaign_stat_activity_counter(&$params) {
  // Define the 'id' parameter with metadata
  $params['id'] = array(
    'name' => 'id',
    'title' => E::ts('Campaign ID'),
    'description' => E::ts('ID of parent campaign'),
    'type' => CRM_Utils_Type::T_INT, // Integer type
    'api.required' => 1,              // Required parameter
    'api.default' => 0,               // Default value
  );
}

/**
 * Fetch and return activity statistics for a given campaign and its children.
 *
 * @param array $params The input parameters from the API request
 * @return array The result of the campaign activity counter
 */
function civicrm_api3_campaign_stat_activity_counter($params) {
  try {
    // Get the campaign ID from the parameters
    $campaignId = $params['id'];

    // Retrieve the IDs of the campaign and its child campaigns
    $campaigns = CRM_CampaignManager_CampaignTree_Tree::getCampaignIds($campaignId, 99);
    $children = $campaigns['children'];

    // Call the KPI activity counter function
    $stat = CRM_CampaignManager_KPIActivity::activityCounter($campaignId, $children);

    // Return the result in a successful API response
    return civicrm_api3_create_success($stat, $params);
  }
  catch (Exception $exception) {
    // Handle any exceptions and return an error response
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

/**
 * Define API parameters for the campaign stat activity report function.
 *
 * @param array $params The parameters array
 */
function _civicrm_api3_campaign_stat_activity_report(&$params) {
  // Define the 'id' parameter with metadata
  $params['id'] = array(
    'name' => 'id',
    'title' => E::ts('Campaign ID'),
    'description' => E::ts('ID of parent campaign'),
    'type' => CRM_Utils_Type::T_INT, // Integer type
    'api.required' => 1,              // Required parameter
    'api.default' => 0,               // Default value
  );
}

/**
 * Fetch and return an activity report for a given campaign and its children.
 *
 * @param array $params The input parameters from the API request
 * @return array The result of the campaign activity report
 */
function civicrm_api3_campaign_stat_activity_report($params) {
  try {
    // Get the campaign ID from the parameters
    $campaignId = $params['id'];

    // Retrieve the IDs of the campaign and its child campaigns
    $campaigns = CRM_CampaignManager_CampaignTree_Tree::getCampaignIds($campaignId, 99);
    $children = $campaigns['children'];

    // Call the KPI activity report function
    $stat = CRM_CampaignManager_KPIActivity::activityReport($campaignId, $children);

    // Return the result in a successful API response
    return civicrm_api3_create_success($stat, $params);
  }
  catch (Exception $exception) {
    // Handle any exceptions and return an error response
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

/**
 * Define API parameters for the campaign stat activity sequence function.
 * This function does not currently define any parameters.
 *
 * @param array $params The parameters array
 */
function _civicrm_api3_campaign_stat_activity_sequence(&$params) {
  // Currently no parameters are required for this API.
}

/**
 * Fetch and return the sequence of campaign activities.
 *
 * @param array $params The input parameters from the API request
 * @return array The result of the campaign activity sequence
 */
function civicrm_api3_campaign_stat_activity_sequence($params) {
  try {
    // Call the KPI activity sequence function and return the result
    return civicrm_api3_create_success(CRM_CampaignManager_KPIActivity::sequence(), $params);
  }
  catch (Exception $exception) {
    // Handle any exceptions and return an error response
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
