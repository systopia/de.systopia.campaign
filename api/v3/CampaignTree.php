<?php
/**
 * File for all campaign relationship methods
 */

 /**
* Get all child nodes of a campaign
*
* @param integer $id campaign id
* @param integer $depth maximum depth
*
* @return array
*/

function civicrm_api3_campaign_tree_getid($params) {
   return CRM_Campaign_Tree::getCampaignIds($params['id'], $params['depth']);
}

function _civicrm_api3_campaign_tree_getid_spec(&$params) {
  $params['id']['api.required'] = 1;
  $params['depth']['api.required'] = 1;
}

/**
* Get all parent nodes of a campaign
*
* @param integer $id campaign id
*
* @return array
*/

function civicrm_api3_campaign_tree_getparentids($params) {
  return CRM_Campaign_Tree::getCampaignParentIds($params['id']);
}

function _civicrm_api3_campaign_tree_getparentids_spec(&$params) {
 $params['id']['api.required'] = 1;
}
