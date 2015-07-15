<?php
/**
 * Meta-API for campaign expenses, based on financial items
 * (DRAFT)
 *
 * @author endres@systopia.de
 */

// TODO: handle expense_type (encode in description?)

/**
 * Get expenses
 *
 * function passed on to CRM_Financial_BAO_FinancialItem
 */
function civicrm_api3_campaign_expense_get($params) {
  $params['entity_table'] = 'civicrm_campaign';
  $params['entity_id'] = $params['campaign_id'];
  return _civicrm_api3_basic_get(CRM_Financial_BAO_FinancialItem, $params);
}



/**
 * Get expenses sum
 * will sum up the expenses for the given campaign_id and all
 * sub-campaigns (up to the given depth)
 *
 * function passed on to CRM_Financial_BAO_FinancialItem
 */
function civicrm_api3_campaign_expense_getsum($params) {
  $config = CRM_Core_Config::singleton();
  $sums = array($config->defaultCurrency => 0.0);

  $campaign_ids = array($params['campaign_id']);
  $query = array('id'    => $params['campaign_id'],
                 'depth' => $params['depth']);
  $campaign_tree = civicrm_api3('CampaignTree', 'getids', $query);
  foreach ($campaign_tree['children'] as $child_id => $child_label) {
    $campaign_ids[] = $child_id;
  }

  // TODO: optimise when the data structures are settled
  foreach ($campaign_ids as $campaign_id) {
    $entries = civicrm_api3_campaign_expense_get(array('campaign_id' => $campaign_id));
    foreach ($entries['values'] as $expense_id => $expense) {
      $sums[$expense['currency']] += (float) $expense['amount'];
    }
  }

  return civicrm_api3_create_success($sums);
}

function _civicrm_api3_campaign_expense_getsum_spec(&$params) {
  $params['campaign_id']['api.required'] = 1;
  $params['depth']['api.default'] = 9999;
}




/**
 * Create/edit CampaignExpense.
 *
 * function passed on to CRM_Financial_BAO_FinancialItem
 */
function civicrm_api3_campaign_expense_create($params) {
  $params['entity_table'] = 'civicrm_campaign';
  $params['entity_id'] = $params['campaign_id'];
  return _civicrm_api3_basic_create(CRM_Financial_BAO_FinancialItem, $params);
}

function _civicrm_api3_campaign_expense_create_spec(&$params) {
  $config = CRM_Core_Config::singleton();
  $params['contact_id'] = array(
    'title'        => 'Contact associated with this expense',
    'api.required' => 1);
  $params['transaction_date'] = array(
    'title'        => 'Date of the expense',
    'api.default'  => date('Ymdhis'));
  $params['description'] = array(
    'title'        => 'Description of the expense',
    'api.required' => 0);
  $params['amount'] = array(
    'title'        => 'Total amount of the expense',
    'api.required' => 1);
  $params['currency'] = array(
    'title'        => 'Currency of the expense',
    'api.required' => 0,
    'api.default'  => $config->defaultCurrency);
  $params['financial_account_id'] = array(
    'title'        => 'Financial account of the expense',
    'api.required' => 0);
  $params['status_id'] = array(
    'title'        => 'Status of the expense (see option group contribution_status)',
    'api.required' => 0,
    'api.default'  => 1);
  $params['expense_type_id'] = array(
    'title'        => 'Refers to option group civicrm_campaign_expense_types for categorisation',
    'api.required' => 0,
    'api.default'  => 1);
}




/**
 * Delete CampaignExpense.
 *
 * function passed on to CRM_Financial_BAO_FinancialItem
 */
function civicrm_api3_campaign_expense_delete($params) {
  // make sure, this is really a campaign expense
  $expense = civicrm_api3('CampaignExpense', 'getsingle', array('id' => $params['id']));
  if ($expense['entity_table'] == 'civicrm_campaign') {
    return _civicrm_api3_basic_delete(CRM_Financial_BAO_FinancialItem, $params);
  } else {
    return civicrm_api3_create_error("A CampaignExpense with ID '{$params['id']}' doesn't exist.");
  }
}

function _civicrm_api3_campaign_expense_delete_spec(&$params) {
  $params['id'] = array(
    'title'        => 'CampaignExpense ID',
    'api.required' => 1);
}
