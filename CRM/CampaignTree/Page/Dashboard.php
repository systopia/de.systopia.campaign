<?php

class CRM_CampaignTree_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    // Set the page title
    CRM_Utils_System::setTitle(ts('Campaign Dashboard'));

    $this->browse();

    parent::run();
  }

  public function userContext($mode = NULL) {
    return 'civicrm/campaign/dashboard';
  }

  /**
   * Return user context uri params.
   *
   * @param null $mode
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * We need to do slightly different things for groups vs saved search groups, hence we
   * reimplement browse from Page_Basic
   *
   * @param int $action
   *
   * @return void
   */
  public function browse($action = NULL) {
    $campaignPermission = CRM_Core_Permission::check('manage campaigns') ? CRM_Core_Permission::EDIT : CRM_Core_Permission::VIEW;
    $this->assign('campaignPermission', $campaignPermission);

    $this->search();
  }

  public function search() {
    if (isset($this->action)) {
      if ($this->_action & (CRM_Core_Action::ADD |
          CRM_Core_Action::UPDATE |
          CRM_Core_Action::DELETE
        )
      ) {
        return;
      }
    }

    $form = new CRM_Core_Controller_Simple('CRM_CampaignTree_Form_Search', ts('Search Campaigns'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

}
