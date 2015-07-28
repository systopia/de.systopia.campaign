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

function civicrm_api3_campaign_tree_getids($params) {
   return CRM_Campaign_Tree::getCampaignIds($params['id'], $params['depth']);
}

function _civicrm_api3_campaign_tree_getids_spec(&$params) {
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

/**
* Get a subtree of a campaign
*
* @param integer $id campaign id
* @param integet $depth max search depth
*
* @return array
*/

function civicrm_api3_campaign_tree_gettree($params) {
  return CRM_Campaign_Tree::getCampaignTree($params['id'], $params['depth']);
}

function _civicrm_api3_campaign_tree_gettree_spec(&$params) {
 $params['id']['api.required'] = 1;
 $params['depth']['api.required'] = 1;
}

/**
* Set a parentid of a campaign node
*
* @param integer $id campaign id
* @param integer $parent new parent id
*
* @return array
*/

function civicrm_api3_campaign_tree_setnodeparent($params) {
  return CRM_Campaign_Tree::setNodeParent($params['id'], $params['parentid']);
}

function _civicrm_api3_campaign_tree_setnodeparent_spec(&$params) {
 $params['id']['api.required'] = 1;
 $params['parentid']['api.required'] = 1;
}

/**
* Copy a campaign sub tree
*
* @param integer id campaign id
* @param boolean onlyroot only copy root (given) id or whole sub tree
* @param string  titlesearch regex pattern to match the title
* @param string  titlereplace regex pattern to replace (parts of) the title
* @return array
*/

function civicrm_api3_campaign_tree_clone($params) {
  return CRM_Campaign_Tree::cloneCampaign($params['id'], $params['onlyroot'], $params['titlesearch'], $params['titlereplace'], $params['startdateoffset'], $params['enddateoffset']);
}

function _civicrm_api3_campaign_tree_clone_spec(&$params) {
 $params['id']['api.required'] = 1;
 $params['onlyroot']['api.required'] = 1;
 $params['titlesearch']['api.required'] = 1;
 $params['titlereplace']['api.required'] = 1;
}
