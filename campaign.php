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

require_once 'campaign.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function campaign_civicrm_config(&$config) {
  _campaign_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function campaign_civicrm_xmlMenu(&$files) {
  _campaign_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function campaign_civicrm_install() {
  _campaign_civix_civicrm_install();
  CRM_Campaign_Config::installScheduledJob();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function campaign_civicrm_uninstall() {
  _campaign_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function campaign_civicrm_enable() {
  _campaign_civix_civicrm_enable();

  //add/check the required option groups
  campaign_civicrm_install_options(campaign_civicrm_options());
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function campaign_civicrm_disable() {
  _campaign_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function campaign_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _campaign_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function campaign_civicrm_managed(&$entities) {
  _campaign_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function campaign_civicrm_caseTypes(&$caseTypes) {
  _campaign_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function campaign_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _campaign_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_angularModules
 */
function campaign_civicrm_angularModules(&$angularModules) {
  $angularModules['crmD3'] = array('ext' => 'civicrm', 'js' => array('ang/crmD3.js', 'bower_components/d3/d3.min.js'));
  $angularModules['campaign'] = array('ext' => 'de.systopia.campaign', 'js' => array('js/lib/d3-context-menu.js', 'js/campaign.js'), 'partials' => array('partials'), 'css' => array('css/lib/d3-context-menu.css', 'css/campaign.css'));
}

/**
 * Implementation of hook_civicrm_buildForm:
 */
function campaign_civicrm_buildForm($formName, &$form) {
	if ($formName == 'CRM_Campaign_Form_Campaign') {
    $action = $form->getAction();
    if($action == CRM_Core_Action::NONE && !isset($_GET['qfKey'])) {
      // pre-select element
      if(isset($_GET['pid'])){
        $select = $form->getElement('parent_id');
        $select->setSelected($_GET['pid']);
      }
      CRM_Core_Region::instance('form-body')->add(array(
        		'template' => 'CRM/Campaign/Form/ExtendedCampaign.tpl',
      	));
    }elseif (($action == CRM_Core_Action::UPDATE || $action == CRM_Core_Action::ADD) && !isset($_GET['qfKey'])) {
      $cid = $form->get('id');
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(CRM_Utils_Array::value('parent_id', $form->get('values')), $cid);
      if (!empty($campaigns)) {
        $form->addElement('select', 'parent_id', ts('Parent ID', array('domain' => 'de.systopia.campaign')),
          array('' => ts('- select Parent -', array('domain' => 'de.systopia.campaign'))) + $campaigns,
          array('class' => 'crm-select2')
        );
      }
      CRM_Core_Region::instance('form-body')->add(array(
        		'template' => 'CRM/Campaign/Form/ExtendedCampaign.tpl',
      	));
    }
	}
}

function campaign_civicrm_links( $op, $objectName, $objectId, &$links, &$mask, &$values ) {
    if($objectName == 'Campaign' && $op == 'campaign.dashboard.row') {
      $viewLink = array(
          'name' => ts('View', array('domain' => 'de.systopia.campaign')),
          'title' => ts('View Campaign', array('domain' => 'de.systopia.campaign')),
          'class' => 'no-popup',
          'url' => CRM_Utils_System::url("civicrm/a/#/campaign/{$objectId}/view"),
      );

      array_unshift($links, $viewLink);
    }
}


function campaign_civicrm_install_options($data) {
  foreach ($data as $groupName => $group) {
    // check group existence
    $result = civicrm_api('option_group', 'getsingle', array('version' => 3, 'name' => $groupName));
    if (isset($result['is_error']) && $result['is_error']) {
      $params = array(
          'version' => 3,
          'sequential' => 1,
          'name' => $groupName,
          'is_reserved' => 1,
          'is_active' => 1,
          'title' => $group['title'],
          'description' => $group['description'],
      );
      $result = civicrm_api('option_group', 'create', $params);
      $group_id = $result['values'][0]['id'];
    } else {
      $group_id = $result['id'];
    }
    if (is_array($group['values'])) {
      $groupValues = $group['values'];
      $weight = 1;
      foreach ($groupValues as $valueName => $value) {
        $result = civicrm_api('option_value', 'getsingle', array('version' => 3, 'name' => $valueName));
        if (isset($result['is_error']) && $result['is_error']) {
          $params = array(
              'version' => 3,
              'sequential' => 1,
              'option_group_id' => $group_id,
              'name' => $valueName,
              'label' => $value['label'],
              'weight' => isset($value['weight']) ? $value['weight'] : $weight,
              'is_default' => $value['is_default'],
              'is_active' => 1,
          );
          if (isset($value['value'])) {
            $params['value'] = $value['value'];
          }
          $result = civicrm_api('option_value', 'create', $params);
        } else {
          $weight = $result['weight'] + 1;
        }
      }
    }
  }
}

function campaign_civicrm_options() {
  return array(
      'campaign_expense_types' => array(
          'title' => ts('Campaign Expense Types', array('domain' => 'de.systopia.campaign')),
          'description' => '',
          'is_reserved' => 1,
          'is_active' => 1,
          'values' => array(
            'Default' => array(
              'label' => ts('Default', array('domain' => 'de.systopia.campaign')),
              'is_default' => 1,
              'is_reserved' => 1,
              'value' => 1,
            ),
          ),
        ),
    );
}

/**
 * alterAPIPermissions() hook allows you to change the permissions checked when doing API 3 calls.
 */
function campaign_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions)
{
  // Mend Campaign API
  $permissions['campaign']['getsingle'] = array('manage campaign');
  $permissions['campaign']['create'] = array('manage campaign');
  $permissions['campaign']['update'] = array('manage campaign');
  $permissions['campaign']['get'] = array('manage campaign');
  $permissions['campaign']['delete'] = array('manage campaign');

  // CampaignExpense API
  $permissions['campaign_expense']['get'] = array('manage campaign');
  $permissions['campaign_expense']['getsingle'] = array('manage campaign');
  $permissions['campaign_expense']['getsum'] = array('manage campaign');
  $permissions['campaign_expense']['create'] = array('manage campaign');
  $permissions['campaign_expense']['update'] = array('manage campaign');
  $permissions['campaign_expense']['delete'] = array('manage campaign');

  // CampaignKPI API
  $permissions['campaign_kpi']['get'] = array('manage campaign');

  // CampaignTree API
  $permissions['campaign_tree']['getids'] = array('manage campaign');
  $permissions['campaign_tree']['getparentids'] = array('manage campaign');
  $permissions['campaign_tree']['gettree'] = array('manage campaign');
  $permissions['campaign_tree']['setnodeparent'] = array('manage campaign');
  $permissions['campaign_tree']['clone'] = array('manage campaign');
  $permissions['campaign_tree']['getcustominfo'] = array('manage campaign');
}

/**
 * Implements hook_coreResourceList
 *
 * @param array $list
 * @param string $region
 */
function campaign_civicrm_coreResourceList(&$list, $region) {
  Civi::resources()
    ->addStyleFile('de.systopia.campaign', 'css/campaign.css', 0, $region);
}

