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
 * File for all campaign relationship methods
 */

 /**
* Get all subnodes of a campaign
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
* @param array   adjustments array of adjustments
* @return array
*/

function civicrm_api3_campaign_tree_clone($params) {
  return CRM_Campaign_Tree::cloneCampaign($params['id'], 0, $params['depth'], $params);
}

function _civicrm_api3_campaign_tree_clone_spec(&$params) {
 $params['id']['api.required'] = 1;
 $params['depth']['api.default'] = 999;
}
