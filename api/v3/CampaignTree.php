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

require_once('CRM/CampaignTree/Tree.php');

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


function civicrm_api3_campaign_tree_getcustominfo($params) {
  // Get the Custom Group ID for campaign_information
  try {
    $customGroupId = civicrm_api3('CustomGroup', 'getsingle', array(
      'return' => "id",
      'name' => "campaign_information",
    ));
  }
  catch (Exception $e) {
    CRM_Core_Error::debug_log_message("Cannot find id for 'campaign_information' custom field group!");
    return;
  }

  // Get list of custom fields in group
  $customGroupFields = civicrm_api3('CustomField', 'get', array(
    'custom_group_id' => $customGroupId['id'],
  ));

  $customValueFields = array(); // Selector Array for CustomValue_get
  $customValueData = array(); // Data array to collect fields for output
  // Create the selector array and store some values for use later
  $customValueFields['entity_id'] = $params['entity_id'];
  foreach($customGroupFields['values'] as $id => $fields) {
    $customValueFields['return.custom_'.$id] = 1;
    // These values are used later to build the output array
    $customValueData[$fields['id']]['name'] = $fields['name'];
    $customValueData[$fields['id']]['label'] = $fields['label'];
    $customValueData[$fields['id']]['data_type'] = $fields['data_type'];
    $customValueData[$fields['id']]['html_type'] = $fields['html_type'];
  }

  // Custom values
  $customValues = civicrm_api3('CustomValue', 'get', $customValueFields);
  if (!isset($customValues)) { return; }

  $customInfo = array(); // This is the output array
  // Merge together information from the $customValues array and the $customValueData array
  // to generate the $customInfo output array
  foreach ($customValues['values'] as $id => $values) {
    $key = strtolower($customValueData[$id]['name']);
    if (!isset($values[0])) { return array(); } // We assume that customvalue has a single value '0'.
    $value = $values[0];
    $customInfo[$key]['title'] = $customValueData[$id]['label'];
    $customInfo[$key]['value'] = ''; // Default to empty string if not defined
    // Get actual values for references
    if (!empty($value)) {
      switch ($customValueData[$id]['data_type']) {
        case 'ContactReference':
          // Return the contact name, not the ID
          $contactName = civicrm_api3('Contact', 'getvalue', array(
            'return' => "display_name",
            'id' => $value,
          ));
          $customInfo[$key]['value'] = $contactName;
          break;
        case 'String':
          if ($customValueData[$id]['html_type'] == 'Select') {
            try {
              // Return the label, not the OptionValue ID
              $optionGroupId = civicrm_api3('OptionGroup', 'getsingle', array(
                'return' => "id",
                'title' => $customValueData[$id]['label'],
              ));
              $optionLabel = civicrm_api3('OptionValue', 'getsingle', array(
                'return' => "label",
                'option_group_id' => $optionGroupId['id'],
                'value' => $value,
              ));
            } catch (Exception $e) {
              CRM_Core_Error::debug_log_message("Cannot find OptionGroup or OptionValue. " . print_r($e, true));
            }
            $customInfo[$key]['value'] = $optionLabel['label'];
          } else {
            $customInfo[$key]['value'] = $value;
          }
          break;
        default:
          $customInfo[$key]['value'] = $value;
      }
    }
  }
  return $customInfo;
}

function _civicrm_api3_campaign_tree_getcustominfo_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
}
