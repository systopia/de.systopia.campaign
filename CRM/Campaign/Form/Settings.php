<?php
/*-------------------------------------------------------+
| CAMPAIGN MANAGER                                       |
| Copyright (C) 2015-2017 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Campaign_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Campaign_Form_Settings extends CRM_Core_Form {

  private $currentValues = array();

  public function buildQuickForm() {

    CRM_Utils_System::setTitle(E::ts('Campaign Manager Settings'));

    $kpis = CRM_Campaign_KPI::builtInKPIs();
    $this->assign('kpis', $kpis);

    // add switches for all
    $kpinames = [];
    foreach ($kpis as $key => $label) {
      $this->add('checkbox', $key, $label);
      $kpinames[$key] = str_replace('_', ' ', $key);
    }
    $this->assign('kpinames', $kpinames);

    // segment options
    $this->addElement('select',
                      'cache',
                      E::ts('KPI Caching (TTL)'),
                      CRM_Campaign_KPICache::getTTLOptions(),
                      array());

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * set the default (=current) values in the form
   */
  public function setDefaultValues() {
    $current_values = array();

    // add enabled built-in KPIs
    $enabled_kpis = CRM_Campaign_Config::getActiveBuiltInKPIs();
    foreach ($enabled_kpis as $key) {
      $current_values[$key] = 1;
    }

    // add general settings
    $settings = CRM_Campaign_Config::getCMSettings();
    foreach ($settings as $key => $value) {
      $current_values[$key] = $value;
    }

    $this->currentValues = $current_values;
    return $current_values;
  }

  public function postProcess() {
    $values = $this->exportValues();

    // the activity KPI needs some extra care when they are enabled/disabled
    $enabled     = (int) CRM_Utils_Array::value('activities', $values);
    $was_enabled = (int) CRM_Utils_Array::value('activities', $this->currentValues);
    CRM_Campaign_KPIActivity::setEnabled($enabled, $was_enabled);

    // store KPIs
    CRM_Campaign_Config::setActiveBuiltInKPIs($values);

    // store settings
    $settings = array(
      'cache' => CRM_Utils_Array::value('cache', $values),
    );
    CRM_Campaign_Config::setCMSettings($settings);

    // clear cache
    CRM_Campaign_KPICache::clearCache();

    parent::postProcess();
  }

}
