<?php
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
