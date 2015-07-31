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
