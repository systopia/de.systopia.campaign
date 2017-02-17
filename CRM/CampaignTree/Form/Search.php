<?php

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_CampaignTree_Form_Search extends CRM_Core_Form {

  public function preProcess() {
    parent::preProcess();

    CRM_Core_Resources::singleton()->addPermissions('manage campaigns');
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'title')
    );

    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'description')
    );

    $this->add('text', 'created_by', ts('Created By'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
    );

    $this->add('text', 'external_id', ts('External ID'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'external_id')
    );

    //campaign start date.
    $this->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'searchDate'));

    //campaign end date.
    $this->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'searchDate', 'name' => 'end_date'));

    $campaignShow = array(ts('Root') => 1, ts('Parent') => 2, ts('Child') => 3);
    $this->addCheckBox('show',
      ts('Show'),
      $campaignShow,
      NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
    );

    //Active
    $campaignActive = array(ts('Yes') => 1, ts('No') => 2);
    $this->addCheckBox('active',
      ts('Active?'),
      $campaignActive,
      NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
    );

    //campaign type.
    $campaignTypes = CRM_Campaign_PseudoConstant::campaignType();
    $this->add('select', 'type_id', ts('Campaign Type'),
      array(
        '' => ts('- any -'),
      ) + $campaignTypes
    );

    $this->set('campaignTypes', $campaignTypes);
    $this->assign('campaignTypes', json_encode($campaignTypes));

    //campaign status
    $campaignStatus = CRM_Campaign_PseudoConstant::campaignStatus();
    $this->addElement('select', 'status_id', ts('Campaign Status'),
      array(
        '' => ts('- any -'),
      ) + $campaignStatus
    );
    $this->set('campaignStatus', $campaignStatus);
    $this->assign('campaignStatus', json_encode($campaignStatus));

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
    $this->assign('suppressForm', TRUE);
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = array('title', 'created_by', 'campaign_type', 'visibility', 'active_status', 'inactive_status');
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
    parent::postProcess();
  }

}

