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

/**
 * File for all campaign KPI methods
 */

 /**
* Get all KPIs of a campaign
*
* @param integer $id campaign id
*
* @return array
*/
function civicrm_api3_campaign_kpi_get($params) {
   return CRM_Campaign_KPI::getCampaignKPI($params['id']);
}

function _civicrm_api3_campaign_kpi_get_spec(&$params) {
  $params['id']['api.required'] = 1;
}


/**
 * Fill the KPI cache for the selected campaigns
 */
function civicrm_api3_campaign_kpi_cache($params) {
  // check if caching is enabled
  if (!CRM_Campaign_KPICache::isCacheEnabled()) {
    return civicrm_api3_create_success("KPI Caching disabled");
  }

  // pass query through to Campaign.get
  $campaign_ids = array();
  $params['option.limit'] = 0;
  $params['return'] = 'id';
  error_log(json_encode($params));
  $campaign_query = civicrm_api3('Campaign', 'get', $params);
  error_log(json_encode($campaign_query));
  foreach ($campaign_query['values'] as $campaign) {
    $campaign_ids[] = $campaign['id'];
  }

  // run the cache
  $timestamp = microtime(TRUE);
  CRM_Campaign_KPICache::cacheCampaigns($campaign_ids);

  // stats and return
  $runtime = number_format(microtime(TRUE) - $timestamp, 3);
  $campaign_count = count($campaign_ids);

  return civicrm_api3_create_success("Cached KPIs for {$campaign_count} campaigns in {$runtime}s");
}


/**
 * SPECS: Fill the KPI cache for the selected campaigns
 */
function _civicrm_api3_campaign_kpi_cache_spec(&$params) {
  $params['id'] = array(
    'name'         => 'id',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Campaign ID',
    'description'  => 'Unique Campaign ID',
    );
  $params['start_date'] = array(
    'name'         => 'start_date',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_DATE, //12
    'title'        => 'Campaign Start Date',
    'description'  => 'Date and time that Campaign starts.',
    );
  $params['end_date'] = array(
    'name'         => 'end_date',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_DATE, //12
    'title'        => 'Campaign End Date',
    'description'  => 'Date and time that Campaign ends.',
    );
  $params['campaign_type_id'] = array(
    'name'         => 'campaign_type_id',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Campaign Type',
    'description'  => 'Campaign Type ID.Implicit FK to civicrm_option_value where option_group = campaign_type',
    );
  $params['status_id'] = array(
    'name'         => 'status_id',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Campaign Status',
    'description'  => 'Campaign status ID.Implicit FK to civicrm_option_value where option_group = campaign_status',
    );
  $params['external_identifier'] = array(
    'name'         => 'external_identifier',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_STRING,
    'title'        => 'Campaign External ID',
    'description'  => 'Unique trusted external ID (generally from a legacy app/datasource). Particularly useful for deduping operations.',
    );
  $params['is_active'] = array(
    'name'         => 'is_active',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'Is Campaign Active?',
    'description'  => 'Is this Campaign enabled or disabled/cancelled?',
    );
  $params['last_modified_date'] = array(
    'name'         => 'last_modified_date',
    'api.required' => 0,
    'type'         => CRM_Utils_Type::T_DATE, //12
    'title'        => 'Campaign Modified Date',
    'description'  => 'Date and time that Campaign was edited last time.',
    );
}
