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

    $this->_tabs = array(
      'campaign' => ts('Campaigns'),
      'survey' => ts('Surveys'),
      'petition' => ts('Petitions'),
    );

    $subPageType = CRM_Utils_Request::retrieve('type', 'String', $this);
    if ($subPageType) {
      if (!isset($this->_tabs[$subPageType])) {
        CRM_Utils_System::permissionDenied();
      }
      //load the data in tabs.
      $this->{'browse' . ucfirst($subPageType)}();
      $this->assign('subPageType', ucfirst($subPageType));
    }
    else {
      //build the tabs.
      $this->buildTabs();
    }
    $res = CRM_Core_Resources::singleton();
    $res->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header');
    $res->addSetting(array(
        'tabSettings' => array(
          'active' => strtolower(CRM_Utils_Array::value('subPage', $_GET, 'campaign')),
        )));
    $res->addVars('campaigntree', array(
      'baseUrl' => $res->getUrl('de.systopia.campaign'),
    ));
  }

  /**
   * @return mixed
   */
  public function browseCampaign() {
    if (isset($this->action)) {
      if ($this->_action & (CRM_Core_Action::ADD |
          CRM_Core_Action::UPDATE |
          CRM_Core_Action::DELETE
        )
      ) {
        return;
      }
    }

    // ensure valid javascript (these must have a value set)
    $this->assign('searchParams', json_encode(NULL));
    $this->assign('campaignTypes', json_encode(NULL));
    $this->assign('campaignStatus', json_encode(NULL));

    $this->assign('addCampaignUrl', CRM_Utils_System::url('civicrm/campaign/add', 'reset=1&action=add'));
    $campaignCount = CRM_Campaign_BAO_Campaign::getCampaignCount();
    //don't load find interface when no campaigns in db.
    if (!$campaignCount) {
      $this->assign('hasCampaigns', FALSE);
      return;
    }
    $this->assign('hasCampaigns', TRUE);

    //build the ajaxify campaign search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_CampaignTree_Form_Search', ts('Search Campaigns'), CRM_Core_Action::ADD);
    $controller->set('searchTab', 'campaign');
    $controller->setEmbedded(TRUE);
    $controller->setParent($this);
    $controller->process();
    return $controller->run();
  }

  // Copy of function from CRM/Campaign/Page/Dashboard
  /**
   * @return mixed
   */
  public function browseSurvey() {
    // ensure valid javascript - this must have a value set
    $this->assign('searchParams', json_encode(NULL));
    $this->assign('surveyTypes', json_encode(NULL));
    $this->assign('surveyCampaigns', json_encode(NULL));

    $this->assign('addSurveyUrl', CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add'));

    $surveyCount = CRM_Campaign_BAO_Survey::getSurveyCount();
    //don't load find interface when no survey in db.
    if (!$surveyCount) {
      $this->assign('hasSurveys', FALSE);
      return;
    }
    $this->assign('hasSurveys', TRUE);

    //build the ajaxify survey search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Search_Survey', ts('Search Survey'));
    $controller->set('searchTab', 'survey');
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  // Copy of function from browsePetition
  /**
   * Browse petitions.
   *
   * @return mixed|null
   */
  public function browsePetition() {
    // Ensure valid javascript - this must have a value set
    $this->assign('searchParams', json_encode(NULL));
    $this->assign('petitionCampaigns', json_encode(NULL));

    $this->assign('addPetitionUrl', CRM_Utils_System::url('civicrm/petition/add', 'reset=1&action=add'));

    $petitionCount = CRM_Campaign_BAO_Petition::getPetitionCount();
    //don't load find interface when no petition in db.
    if (!$petitionCount) {
      $this->assign('hasPetitions', FALSE);
      return NULL;
    }
    $this->assign('hasPetitions', TRUE);

    // Build the ajax petition search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Search_Petition', ts('Search Petition'));
    $controller->set('searchTab', 'petition');
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  public function buildTabs() {
    $allTabs = array();
    foreach ($this->_tabs as $name => $title) {
      $allTabs[$name] = array(
        'title' => $title,
        'valid' => TRUE,
        'active' => TRUE,
        'link' => CRM_Utils_System::url('civicrm/campaign/dashboard', "reset=1&type=$name"),
      );
    }
    $allTabs['campaign']['class'] = 'livePage';
    $this->assign('tabHeader', $allTabs);
  }
}
