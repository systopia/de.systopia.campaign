<?php

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
  $angularModules['campaign'] = array('ext' => 'de.systopia.campaign', 'js' => array('js/campaign.js'), 'partials' => array('partials'), 'css' => array('css/campaign.css'));
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
    }elseif ($action == CRM_Core_Action::UPDATE && !isset($_GET['qfKey'])) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(CRM_Utils_Array::value('parent_id', $form->get('values')), $form->get('_campaignId'));
      if (!empty($campaigns)) {
        $form->addElement('select', 'parent_id', ts('Parent ID'),
          array('' => ts('- select Parent -')) + $campaigns,
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
          'name' => 'View',
          'title' => 'View Campaign',
          'class' => 'no-popup',
          'url' => 'a/#/campaign/'. $objectId .'/view',
      );

      array_unshift($links, $viewLink);
    }
}
