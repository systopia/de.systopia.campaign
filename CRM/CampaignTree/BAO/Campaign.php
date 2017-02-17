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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 *
 */
class CRM_CampaignTree_BAO_Campaign extends CRM_Campaign_DAO_Campaign
{

  /**
   * wrapper for ajax campaign selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of campaign list
   *   -rp = rowcount
   *   -page= offset
   * @todo there seems little reason for the small number of functions that call this to pass in
   * params that then need to be translated in this function since they are coding them when calling
   */
  static public function getCampaignListSelector(&$params)
  {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);

    $campaignList = array();
    $campaigns = self::getCampaignList($params);
    // Permission check, return empty array
    if (!$campaigns['isCampaignEnabled'] || !$campaigns['hasAccessCampaign']) {
      $params['total'] = 0;
      return $campaignList;
    }
    unset($campaigns['isCampaignEnabled']);
    unset($campaigns['hasAccessCampaign']);

    $params['total'] = 0;
    // format params and add links
    $campaignList = array();
    if (!empty($campaigns)) {
      foreach ($campaigns as $id => $value) {
        $params['total']++;
        $campaignList[$id]['id'] = $value['id'];
        $campaignList[$id]['name'] = $value['title'];

        // append parent names if in search mode
        if (empty($params['parent_id']) && !empty($value['parents'])) {
          $campaignIds = explode(',', $value['parents']);
          $title = array();
          foreach ($campaignIds as $cId) {
            $title[] = self::getCampaign($cId)['title'];
          }
          $campaignList[$id]['name'] .= '<div class="crm-row-parent-name"><em>' . ts('Child of') . '</em>: ' . implode(', ', $title) . '</div>';
          $value['class'] = array_diff($value['class'], array('crm-row-parent'));
        }
        $value['class'][] = 'crm-entity';
        $campaignList[$id]['class'] = $value['id'] . ',' . implode(' ', $value['class']);

        $campaignList[$id]['description'] = CRM_Utils_Array::value('description', $value);
        if (!empty($value['type'])) {
          $campaignList[$id]['type'] = $value['type'];
        } else {
          $campaignList[$id]['type'] = '';
        }
        $campaignList[$id]['start_date'] = $value['start_date'];
        if ($campaignList[$id]['start_date'] == '') {
          // Makes we don't display "null" on datatable
          $campaignList[$id]['start_date'] = '';
        }
        $campaignList[$id]['end_date'] = $value['end_date'];
        if ($campaignList[$id]['end_date'] == '') {
          // Makes we don't display "null" on datatable
          $campaignList[$id]['end_date'] = '';
        }
        $campaignList[$id]['status'] = $value['status'];
        $campaignList[$id]['links'] = $value['action'];
        $campaignList[$id]['created_by'] = CRM_Utils_Array::value('created_by', $value);
        if ((boolean)$value['is_active']) {
          $campaignList[$id]['is_active'] = 'Yes';
        }
        else {
          $campaignList[$id]['is_active'] = 'No';
        }

        $campaignList[$id]['is_parent'] = $value['is_parent'];
      }
      return $campaignList;
    }
  }

  public static function getCampaignList(&$params)
  {
    $values = array(
      'hasAccessCampaign' => FALSE,
      'isCampaignEnabled' => FALSE,
    );

    //do check for component.
    $values['isCampaignEnabled'] = $isValid = CRM_Campaign_BAO_Campaign::isCampaignEnable();
    //do check for permissions.
    $values['hasAccessCampaign'] = $isValid = CRM_Campaign_BAO_Campaign::accessCampaign();
    if (!$values['isCampaignEnabled'] || !$values['hasAccessCampaign']) {
      return $values;
    }

    $config = CRM_Core_Config::singleton(); // Need this for dateformat

    $whereClause = self::whereClause($params, FALSE);

    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    }

    $orderBy = ' ORDER BY camp.title asc';
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');

      // CRM-16905 - Sort by count cannot be done with sql
      if (strpos($params['sort'], 'count') === 0) {
        $orderBy = $limit = '';
      }
    }

    $query = "
            SELECT  camp.*, createdBy.sort_name as created_by
            FROM  civicrm_campaign camp
            LEFT JOIN civicrm_contact createdBy
            ON createdBy.id = camp.created_id
            WHERE $whereClause
            {$orderBy}
            {$limit}";

    $object = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Campaign_DAO_Campaign');
    $params['total'] = self::getCampaignCount();

    $campaignPermissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check(array('administer CiviCampaign', 'manage campaign'))) {
      $campaignPermissions[] = CRM_Core_Permission::EDIT;
      $campaignPermissions[] = CRM_Core_Permission::DELETE;
    }

    $campaignTypes = CRM_Core_OptionGroup::values('campaign_type');
    $campaignStatus = CRM_Core_OptionGroup::values('campaign_status');

    $count = 0;
    while ($object->fetch()) {
      $values[$object->id] = array(
        'class' => array(),
        'count' => '0',
      );
      CRM_Core_DAO::storeValues($object, $values[$object->id]);

      if (in_array(CRM_Core_Permission::EDIT, $campaignPermissions)) {
        $values[$object->id]['title'] = '<span class="crm-editable crmf-title">' . $values[$object->id]['title'] . '</span>';
        $values[$object->id]['description'] = '<div class="crm-editable crmf-description" data-type="textarea">' . $values[$object->id]['description'] . '</div>';
      }

      $links = self::actionLinks($object->id);
      $action = array_sum(array_keys($links));

      if (array_key_exists('is_active', $object)) {
        if ($object->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        } else {
          $values[$object->id]['class'][] = 'disabled';
          $action -= CRM_Core_Action::VIEW;
          $action -= CRM_Core_Action::DISABLE;
        }
      }

      $action = $action & CRM_Core_Action::mask($campaignPermissions);

      if ($object->campaign_type_id) {
        $values[$object->id]['type'] = $campaignTypes[$object->campaign_type_id];
      }

      // Created_by
      if ($object->created_id) {
        $contactUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$object->created_id}");
        $values[$object->id]['created_by'] = "<a href='{$contactUrl}'>{$object->created_by}</a>";
      }
      if ($object->status_id) {
        $values[$object->id]['status'] = $campaignStatus[$object->status_id];
      }

      // start_date / end_date
      foreach (array('start_date', 'end_date') as $date) {
        if ($object->$date) {
          $values[$object->id][$date] = CRM_Utils_Date::customFormat($object->$date, $config->dateformatFull);
        }
      }

      $values[$object->id]['action'] = CRM_Core_Action::formLink($links,
        $action,
        array(
          'id' => $object->id,
        ),
        ts('more'),
        FALSE,
        'campaign.selector.row',
        'Campaign',
        $object->id
      );

      // If group has children, add class for link to view children
      $values[$object->id]['is_parent'] = FALSE;
      if (self::isParentCampaign($object->id, self::getCampaignAllParentIds())) {
        $values[$object->id]['class'][] = "crm-campaign-parent";
        $values[$object->id]['is_parent'] = TRUE;
      }

      // If group is a child, add child class
      if (array_key_exists('parent_id', $values[$object->id])) {
        $values[$object->id]['class'][] = "crm-campaign-child";
      }

    }

    return $values;
  }

  /**
   * Get the campaign count.
   *
   * @param bool $rootCount return root campaign count (default: false)
   * @param bool $parentCount return parent campaign count (default: false)
   * @param bool $childCount return parent campaign count (default: false)
   *
   * @return int count
   */
  public static function getCampaignCount($rootCount = false, $parentCount = false, $childCount = false)
  {
    $count = 0;
    if (!$rootCount && !$parentCount && !$childCount) {
      $count += (int)CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_campaign camp');
    } else {
      if ($rootCount) {
        // root has no parent ID
        $count += (int)CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_campaign camp WHERE camp.parent_id IS NULL');
      }
      if ($parentCount) {
        // parent has 1 or more children which specify it as parent_id
        $count += (int)CRM_Core_DAO::singleValueQuery('SELECT COUNT(distinct(parent_id)) FROM `civicrm_campaign` camp WHERE camp.parent_id IS NOT NULL');
      }
      if ($childCount) {
        // child is not a parent
        $count += (int)CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM `civicrm_campaign` camp1 WHERE NOT EXISTS ( SELECT * FROM `civicrm_campaign` camp2 WHERE camp2.parent_id = camp1.id )');
      }
    }
    return $count;
  }

  /**
   * Get all parent campaign IDs
   *
   * @return array
   */
  public static function getCampaignAllParentIds()
  {
    // get all parent campaigns
    $query = '
      SELECT    camp.id,
                camp.title,
                camp.parent_id
      FROM  civicrm_campaign camp
      Order By  camp.id;';

    $queryCount = '
      SELECT count(camp.parent_id)
      FROM   civicrm_campaign camp
      WHERE  camp.parent_id = %1';

    $parents = array();

    $campaign = CRM_Core_DAO::executeQuery($query);
    while ($campaign->fetch()) {
      $count = CRM_Core_DAO::singleValueQuery($queryCount, array(1 => array($campaign->id, 'String')));
      if ($count > 0) {
        $parents[] = array('id' => $campaign->id, 'title' => $campaign->title, 'parent_id' => $campaign->title);
      }
    }

    return $parents;
  }

  /*
   * Get a campaign
   *
   * @param int id of campaign
   * @return array All values for campaign with id
   */
  public static function getCampaign($id) {
    $query = "
            SELECT  camp.*, createdBy.sort_name as created_by
            FROM  civicrm_campaign camp
            LEFT JOIN civicrm_contact createdBy
            ON createdBy.id = camp.created_id
            WHERE camp.id = %1";

    $values = array();
    $object = CRM_Core_DAO::executeQuery($query, array(1=> $id), TRUE, 'CRM_Campaign_DAO_Campaign');
    // We only expect one result as id is unique
    $object->fetch();
    CRM_Core_DAO::storeValues($object, $values);
    return $values;
  }

  public static function isParentCampaign($id, $parents)
  {
    foreach ($parents as $p) {
      if (isset($p['id']) && $p['id'] == $id) {
        return true;
      }
    }
    return false;
  }

  /**
   * Generate permissioned where clause for group search.
   * @param array $params
   * @param bool $sortBy
   * @param bool $excludeHidden
   *
   * @return string
   */
  public static function whereClause(&$params, $sortBy = TRUE, $excludeHidden = TRUE) {
    $title = CRM_Utils_Array::value('title', $params);
    if ($title) {
      $clauses[] = "camp.title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array($title, 'String', FALSE);
      }
      else {
        $params[1] = array($title, 'String', TRUE);
      }
    }

    $description = CRM_Utils_Array::value('description', $params);
    if ($description) {
      $clauses[] = "camp.description LIKE %2";
      if (strpos($description, '%') !== FALSE) {
        $params[2] = array($description, 'String', FALSE);
      }
      else {
        $params[2] = array($description, 'String', TRUE);
      }
    }

    $start_date = CRM_Utils_Date::processDate($params['start_date']);
    if ($start_date) {
      $clauses[] = "( camp.start_date >= %3 OR camp.start_date IS NULL )";
      $params[3] = array($start_date, 'String');
    }

    $end_date = CRM_Utils_Date::processDate($params['end_date'],'235959');
    if ($end_date) {
      $clauses[] = "( camp.end_date <= %4 OR camp.end_date IS NULL )";
      $params[4] = array($end_date, 'String');
    }

    $campaign_type = CRM_Utils_Array::value('campaign_type', $params);
    if ($campaign_type) {
      if (is_array($campaign_type)) {
        $campaign_type = implode(' , ', $campaign_type);
      }
      $clauses[] = "( camp.campaign_type_id IN ( {$campaign_type} ) )";
    }

    $campaign_status = CRM_Utils_Array::value('campaign_status', $params);
    if ($campaign_status) {
      if (is_array($campaign_status)) {
        $campaign_status = implode(' , ', $campaign_status);
      }
      $clauses[] = "( camp.status_id IN ( {$campaign_status} ) )";
    }

    $external_id = CRM_Utils_Array::value('external_id', $params);
    if ($external_id) {
      $clauses[] = "camp.external_identifier LIKE %5";
      if (strpos($external_id, '%') !== FALSE) {
        $params[5] = array($external_id, 'String', FALSE);
      }
      else {
        $params[5] = array($external_id, 'String', TRUE);
      }
    }

    if (!empty($params['status_id'])) {
      $statusId = $params['status_id'];
      if (is_array($params['status_id'])) {
        $statusId = implode(' , ', $params['status_id']);
      }
      $where[] = "( campaign.status_id IN ( {$statusId} ) )";
    }

    $groupType = CRM_Utils_Array::value('group_type', $params);
    if ($groupType) {
      $types = explode(',', $groupType);
      if (!empty($types)) {
        $clauses[] = 'groups.group_type LIKE %6';
        $typeString = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $types) . CRM_Core_DAO::VALUE_SEPARATOR;
        $params[6] = array($typeString, 'String', TRUE);
      }
    }

    $showActive = CRM_Utils_Array::value('showActive', $params);
    if ($showActive) {
      switch ($showActive) {
        case 1:
          $clauses[] = 'camp.is_active = 1';
          $params[7] = array($showActive, 'Integer');
          break;

        case 2:
          $clauses[] = 'camp.is_active = 0';
          $params[7] = array($showActive, 'Integer');
          break;

        case 3:
          $clauses[] = '(camp.is_active = 0 OR camp.is_active = 1 )';
          break;
      }
    }

    // this is a bitmask: 1=root; 2=parents; 4=children
    $show = CRM_Utils_Array::value('show', $params);
    if ($show & 1) {
      // show root campaigns
      $showClauses[] = "(camp.parent_id IS NULL)";
    }
    if ($show & 2) {
      // show parent campaigns
      $showClauses[] = "(EXISTS (SELECT parent_id FROM `civicrm_campaign` camp2 WHERE  (camp2.parent_id = camp.id) AND camp.parent_id IS NOT NULL))";

    }
    if ($show & 4) {
      // show child campaigns
      $showClauses[] = "(NOT EXISTS (SELECT parent_id FROM `civicrm_campaign` camp2 WHERE camp2.parent_id = camp.id))";
    }
    if (isset($showClauses)) {
      $clauses[] = implode(' OR ', $showClauses);
    }

    $parentsOnly = CRM_Utils_Array::value('parentsOnly', $params);
    if ($parentsOnly) {
      $clauses[] = "(camp.parent_id IS NULL)";
    }

    // only show child groups of a specific parent group
    $parent_id = CRM_Utils_Array::value('parent_id', $params);
    if ($parent_id) {
      $clauses[] = 'camp.id IN (SELECT id FROM civicrm_campaign WHERE parent_id = %7)';
      $params[7] = array($parent_id, 'Integer');
    }

    if ($createdBy = CRM_Utils_Array::value('created_by', $params)) {
      $clauses[] = "createdBy.sort_name LIKE %8";
      if (strpos($createdBy, '%') !== FALSE) {
        $params[8] = array($createdBy, 'String', FALSE);
      }
      else {
        $params[8] = array($createdBy, 'String', TRUE);
      }
    }

    if (empty($clauses)) {
      $clauses[] = '(camp.is_active = 0 OR camp.is_active = 1 )';
    }
    //FIXME Do we need a permission clause?

    return implode(' AND ', $clauses);
  }

  /**
   * Define action links
   *
   * @param $objectId int id of campaign
   *
   * @return array
   *   array of action links
   */
  public function actionLinks($objectId) {
    $links = array(
      CRM_Core_Action::VIEW => array(
        'name' => ts('View'),
        'url' => CRM_Utils_System::url('civicrm/a/#/campaign/'. $objectId .'/view'),
        'qs' => '',
        'class' => 'no-popup',
        'title' => ts('View Campaign'),
      ),
      CRM_Core_Action::UPDATE => array(
        'name' => ts('Edit'),
        'url' => CRM_Utils_System::url('civicrm/campaign/add'),
        'qs' => 'reset=1&action=update&id=%%id%%',
        'title' => ts('Update Campaign'),
      ),
      CRM_Core_Action::DISABLE => array(
        'name' => ts('Disable'),
        'title' => ts('Disable Campaign'),
        'ref' => 'crm-enable-disable',
      ),
      CRM_Core_Action::ENABLE => array(
        'name' => ts('Enable'),
        'title' => ts('Enable Campaign'),
        'ref' => 'crm-enable-disable',
      ),
      CRM_Core_Action::DELETE => array(
        'name' => ts('Delete'),
        'url' => CRM_Utils_System::url('civicrm/campaign/add'),
        'qs' => 'action=delete&reset=1&id=%%id%%',
        'title' => ts('Delete Campaign'),
      ),
    );
    return $links;
  }
}