<?php

class CRM_CampaignManager_MenuReplacer {

  public static function replaceCampaignMenu(Civi\Core\Event\GenericHookEvent $event): void {
    if ($event->items['civicrm/campaign']['page_callback'] == 'CRM_Afform_Page_AfformBase') {
      // replace SearchKit-based campaign dashboard with extension-provided form
      $event->items['civicrm/campaign'] = [
        'title' => $event->items['civicrm/campaign']['title'],
        'page_callback' => 'CRM_CampaignManager_CampaignTree_Page_Dashboard',
        'component' => 'CiviCampaign',
        'access_arguments' => [
          ['administer CiviCampaign', 'manage campaign'],
          ['or']
        ],
      ];
    }
  }
}
