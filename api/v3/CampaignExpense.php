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
  $reply = _civicrm_api3_basic_get(CRM_Financial_BAO_FinancialItem, $params);

  // extract the encoded expense_type_id from description
  if (isset($reply['values'])) {
    $values = $reply['values']; // copy array so we can modify while iterating
    foreach ($values as $expense_id => $expense) {
      if (!empty($expense['description'])) {
        $parts = explode(":", $expense['description'], 2);
        if (count($parts)>1) {
          $reply['values'][$expense_id]['expense_type_id'] = $parts[0];
          $reply['values'][$expense_id]['description']     = $parts[1];
        } else {
          $reply['values'][$expense_id]['expense_type_id'] = 1;  // TODO: use default?
          $reply['values'][$expense_id]['description']     = $expense['description'];
        }
      } else {
        $reply['values'][$expense_id]['expense_type_id'] = 1;  // TODO: use default?
        $reply['values'][$expense_id]['description']     = '';
      }
      $reply['values'][$expense_id]['expense_type'] =
      CRM_Core_OptionGroup::getLabel('campaign_expense_types', $reply['values'][$expense_id]['expense_type_id']);
    }
  }
  return $reply;
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

  // if no contact_id is given, set the current user
  if (empty($params['contact_id'])) {
    $params['contact_id'] = CRM_Core_Session::singleton()->get('userID');
  }

  // encode the expense_type_id in the description (prefix)
  if (!empty($params['description'])) {
    $params['description'] = $params['expense_type_id'] . ':' . $params['description'];
  } else {
    $params['description'] = $params['expense_type_id'] . ':';
  }
  unset($params['expense_type_id']);

  // encode the tx date
  if (!empty($params['transaction_date'])) {
    $params['transaction_date'] = date('YmdHis', strtotime($params['transaction_date']));
  }

  return _civicrm_api3_basic_create(CRM_Financial_BAO_FinancialItem, $params);
}

function _civicrm_api3_campaign_expense_create_spec(&$params) {
  $config = CRM_Core_Config::singleton();
  $params['contact_id'] = array(
    'title'        => ts('Contact associated with this expense', array('domain' => 'de.systopia.campaign')),
    'api.required' => 0);
  $params['transaction_date'] = array(
    'title'        => ts('Date of the expense', array('domain' => 'de.systopia.campaign')),
    'api.default'  => date('YmdHis'));
  $params['description'] = array(
    'title'        => ts('Description of the expense', array('domain' => 'de.systopia.campaign')),
    'api.required' => 0);
  $params['amount'] = array(
    'title'        => ts('Total amount of the expense', array('domain' => 'de.systopia.campaign')),
    'api.required' => 1);
  $params['currency'] = array(
    'title'        => ts('Currency of the expense', array('domain' => 'de.systopia.campaign')),
    'api.required' => 0,
    'api.default'  => $config->defaultCurrency);
  $params['financial_account_id'] = array(
    'title'        => ts('Financial account of the expense', array('domain' => 'de.systopia.campaign')),
    'api.required' => 0);
  $params['status_id'] = array(
    'title'        => ts('Status of the expense (see option group contribution_status)', array('domain' => 'de.systopia.campaign')),
    'api.required' => 0,
    'api.default'  => 1);
  $params['expense_type_id'] = array(
    'title'        => ts('Refers to option group civicrm_campaign_expense_types for categorisation', array('domain' => 'de.systopia.campaign')),
    'api.required' => 1,
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
    return civicrm_api3_create_error(ts("A CampaignExpense with ID '%1' doesn't exist.", array(1 => $params['id'], 'domain' => 'de.systopia.campaign')));
  }
}

function _civicrm_api3_campaign_expense_delete_spec(&$params) {
  $params['id'] = array(
    'title'        => ts('CampaignExpense ID', array('domain' => 'de.systopia.campaign')),
    'api.required' => 1);
}
