<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM_CampaignTree_Page_AJAX
 * @copyright CiviCRM LLC (c) 2004-2017
 *
 */

/**
 * This class contains all campaign tree related functions that are called using AJAX (jQuery)
 */
class CRM_CampaignTree_Page_AJAX {

  /*
   * getCampaignList()
   * Retrieves AJAX data for list of campaigns
   *
   * @return: void
   */
  public static function getCampaignList() {
    $params = $_REQUEST;
    if (isset($params['parent_id'])) {
      // requesting child groups for a given parent
      $params['page'] = 1;
      $params['rp'] = 0;
      $campaigns = CRM_CampaignTree_BAO_Campaign::getCampaignListSelector($params);

      CRM_Utils_JSON::output($campaigns);
    }
    else {
      $sortMapper = array(
        0 => 'camp.title',
        1 => 'camp.description',
        2 => 'camp.start_date',
        3 => 'camp.end_date'
      );
      $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
      $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
      $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
      $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
      $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'MysqlOrderByDirection') : 'asc';

      if ($sort && $sortOrder) {
        $params['sortBy'] = $sort . ' ' . $sortOrder;
      }

      $params['page'] = ($offset / $rowCount) + 1;
      $params['rp'] = $rowCount;

      // get campaign list
      $campaigns = CRM_CampaignTree_BAO_Campaign::getCampaignListSelector($params);
      $iFilteredTotal = $iTotal = $params['total'];

      $selectorElements = array(
        'name',
        'description',
        'start_date',
        'end_date',
        'type',
        'status',
        'created_by',
        'external_id',
        'links',
        'is_active',
        'class', // This one MUST always be at the end, as the js code in search.tpl looks for the class in the last element
      );

      header('Content-Type: application/json');
      echo CRM_Utils_JSON::encodeDataTableSelector($campaigns, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
      CRM_Utils_System::civiExit();
    }
  }
}
