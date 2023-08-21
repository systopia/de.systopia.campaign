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

require_once('CRM/CampaignManager/CampaignTree/Tree.php');

function civicrm_api3_campaign_tree_getids($params) {
   return CRM_CampaignManager_CampaignTree_Tree::getCampaignIds($params['id'], $params['depth']);
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
  return CRM_CampaignManager_CampaignTree_Tree::getCampaignParentIds($params['id']);
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
  return CRM_CampaignManager_CampaignTree_Tree::getCampaignTree($params['id'], $params['depth']);
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
  return CRM_CampaignManager_CampaignTree_Tree::setNodeParent($params['id'], $params['parentid']);
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
  return CRM_CampaignManager_CampaignTree_Tree::cloneCampaign($params['id'], 0, $params['depth'], $params);
}

function _civicrm_api3_campaign_tree_clone_spec(&$params) {
 $params['id']['api.required'] = 1;
 $params['depth']['api.default'] = 999;
}


function civicrm_api3_campaign_tree_getcustominfo($params) {
  // Get custom group IDs extending the current campaign.
  try {
    $custom_groups = civicrm_api3('CustomGroup', 'get', array(
      'extends' => "Campaign",
      'return' => "id,extends_entity_column_value",
      'option.limit' => 0,
    ));
  }
  catch (Exception $e) {
    CRM_Core_Error::debug_log_message("Cannot find id for 'campaign_information' custom field group!");
    return array();
  }

  // Abort when there are no custom groups for the current campaign.
  if (!$custom_groups['values']) {
    return array();
  }

  $customInfo = array();
  foreach ($custom_groups['values'] as $group_id => $custom_group) {
    // Filter for custom group sub-types.
    if (!empty($custom_group['extends_entity_column_value'])) {
      static $campaign;
      if (!isset($campaign)) {
        $campaign = civicrm_api3('Campaign', 'getsingle', array(
          'id' => $params['entity_id'],
          'return' => 'campaign_type_id',
        ));
      }
      if (!in_array($campaign['campaign_type_id'], $custom_group['extends_entity_column_value'])) {
        continue;
      }
    }

    // Retrieve custom field data.
    // TODO: Add support for multi-value custom groups.
    $groupTree = CRM_Core_BAO_CustomGroup::getTree(
      'Campaign',
      NULL,
      $params['entity_id'],
      $custom_group['id'],
      (isset($custom_group['extends_entity_column_value']) ? $custom_group['extends_entity_column_value'] : array())
    );
    $cd_details = CRM_Core_BAO_CustomGroup::buildCustomDataView(
      $dummy_page = new CRM_Core_Page(),
      $groupTree,
      FALSE,
      NULL,
      NULL,
      NULL,
      $params['entity_id']
    );
    $customRecId = key($cd_details[$custom_group['id']]);
    $customInfo[$custom_group['id']]['title'] = $cd_details[$custom_group['id']][$customRecId]['title'];
    foreach ($cd_details[$custom_group['id']][$customRecId]['fields'] as $field_id => $field) {
      // Set field data.
      $customInfo[$custom_group['id']]['fields'][$field_id]['title'] = $field['field_title'];
      $customInfo[$custom_group['id']]['fields'][$field_id]['value'] = $field['field_value'];

      // Enhance displayed values depending on field type.
      if (!empty($field['field_value'])) {
        switch ($field['field_data_type']) {
          case 'ContactReference':
            // If possible, return a link to the contact instead of just the display name.
            if ($field['contact_ref_links']) {
              $contact_value = $field['contact_ref_links'][0];
            } elseif ($field['contact_ref_id']) {
              $contact_value = '<a href="' . CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $field['contact_ref_id']) . '" title="' . ts('View contact') . '">' . $field['field_value'] . '</a>';
            } else {
              $contact_value = $field['field_value'];
            }
            $customInfo[$custom_group['id']]['fields'][$field_id]['value'] = $contact_value;
            break;
        }
      }
    }
  }

  return $customInfo;
}

function _civicrm_api3_campaign_tree_getcustominfo_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
}




/**
 * Get all actions links for a given campaign id
 *
 * @param integer $id campaign id
 *
 * @return array
 */
function civicrm_api3_campaign_tree_getlinks($params) {
  // simply call the Hook to catch custom actions
  $links = array();
  CRM_Utils_Hook::links('campaign.selector.row', 'Campaign', $params['id'], $links);

  // postprocess
  foreach ($links as &$link) {
    if (isset($link['url'])) {
      $link['url'] = str_replace('&amp;', '&', $link['url']);
    }
    if (!isset($link['icon'])) {
      $link['icon'] = 'ui-icon-gear'; // ui-icon-link?
    }
  }

  return json_encode($links);
}

function _civicrm_api3_campaign_tree_getlinks_spec(&$params) {
 $params['id']['api.required'] = 1;
}
