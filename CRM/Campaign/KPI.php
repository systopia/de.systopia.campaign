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

require_once('CRM/CampaignTree/Tree.php');

class CRM_Campaign_KPI {

   protected static $cache = array();

   public static function builtInKPIs() {
      return array(
         'contribution_count'   => E::ts("Generic stats on associated contributions"),
         'revenue'              => E::ts("Revenue based on associated contributions"),
         'first_contributions'  => E::ts("First contributions (per contact) associated with this campaign"),
         'costs'                => E::ts("Expenses and ROI"),
         'revenue_breakdown'    => E::ts("Revenue breakdown for subcampaigns"),
         'donation_heartbeat'   => E::ts("Plots donations over the course of the campaign"),
         'activities'           => E::ts("Statistics on associated activities"),
      );
   }

   /**
   * Get Key Performance Indicators (KPIs) for a specific campaign (+subtree):
   */
   public static function getCampaignKPI($campaign_id) {
      $kpi = CRM_Campaign_KPICache::fetchFromCache($campaign_id);
      if ($kpi !== NULL) {
         return json_encode($kpi);
      } else {
         // cache miss
         $kpi = array();
      }

      // get all sub-campaigns
      $campaigns = CRM_Campaign_Tree::getCampaignIds($campaign_id, 99);
      $children = $campaigns['children'];


      // NOW CALCULATE all the (enabled) KPIs
      $enabled_kpis = CRM_Campaign_Config::getActiveBuiltInKPIs();

      if (in_array('contribution_count', $enabled_kpis)) {
         self::calculateGenericContributionStats($kpi, $campaign_id, $children);
      }
      if (in_array('revenue_breakdown', $enabled_kpis)) {
         self::calculateRevenueBreakdown($kpi, $campaign_id, $children);
      }
      if (in_array('donation_heartbeat', $enabled_kpis)) {
         self::calculateDonationHeartbeat($kpi, $campaign_id, $children);
      }
      if (in_array('revenue', $enabled_kpis)) {
         self::calculateRevenue($kpi, $campaign_id, $children);
      }
      if (in_array('first_contributions', $enabled_kpis)) {
         self::calculateFirstContributions($kpi, $campaign_id, $children);
      }
      if (in_array('costs', $enabled_kpis)) {
         self::calculateCosts($kpi, $campaign_id, $children);
      }
      if (in_array('activities', $enabled_kpis)) {
          CRM_Campaign_Stat::calculateActivityStats($kpi, $campaign_id, $children);
      }

      // finally: run the hook
      CRM_Utils_CampaignCustomisationHooks::campaign_kpis($campaign_id, $kpi, 99);

      // cach result
      CRM_Campaign_KPICache::pushToCache($campaign_id, $kpi);

      return json_encode($kpi);
   }



   /*************************************************************
    **           (BUILT-IN)  KPI Calculation                   **
    ************************************************************/

   /**
    * Sum of all (completed) contributions with this campaing
    *
    * @author N. Bochan
    */
   public static function calculateCosts(&$kpi, $campaign_id, $children) {
      // get total revenue
      $total_revenue = self::getTotalRevenue($campaign_id, $children);

      // get total revenue goal
      $query = "  SELECT camp.goal_revenue
                  FROM  civicrm_campaign camp
                  WHERE camp.id = {$campaign_id};";
      $total_revenue_goal = (double) CRM_Core_DAO::singleValueQuery($query);

      $kpi["total_revenue_goal"] = array(
         "id"          => "total_revenue_goal",
         "title"       => ts("Total Revenue Goal", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "money",
         "vis_type"    => "none",
         "description" => ts("Total Revenue Goal", array('domain' => 'de.systopia.campaign')),
         "value"       => isset($total_revenue_goal) ? $total_revenue_goal : 0.00,
         "link"        => ""
      );

      // get all expenses
      $total_costs = self::getTotalCosts($campaign_id, $children);

      $kpi["total_cost"] = array(
         "id"          => "ttlcost",
         "title"       => ts("Total Costs", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "money",
         "vis_type"    => "none",
         "description" => ts("Sum of (known) expenses to this campaign", array('domain' => 'de.systopia.campaign')),
         "value"       => isset($total_costs) ? $total_costs : 0.00,
         "link"        => ""
      );

      // get ROI
      $kpi["roi"] = array(
         "id"          => "roi",
         "title"       => ts("ROI", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "number",
         "vis_type"    => "none",
         "description" => ts("Return on investment", array('domain' => 'de.systopia.campaign')),
         "value"       => $total_revenue / (($total_costs == 0.00) ? 1.00 : $total_costs),
         "link"        => "https://en.wikipedia.org/wiki/Return_on_investment"
      );

      // get revenue goal reached percent
      if ($total_revenue_goal) {
         $total_revenue_goal_pc = ($total_revenue / $total_revenue_goal);
      } else {
         $total_revenue_goal_pc = -1;
      }

      $kpi["total_revenue_goal_pc"] = array(
         "id"          => "total_revenue_goal_pc",
         "title"       => ts("Total Revenue Reached", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "percentage",
         "vis_type"    => "none",
         "description" => ts("Total Revenue Reached", array('domain' => 'de.systopia.campaign')),
         "value"       => $total_revenue_goal_pc,
         "link"        => ""
      );
   }


   /**
    * Sum of all (completed) contributions with this campaing
    *
    * @author N. Bochan
    */
   public static function calculateFirstContributions(&$kpi, $campaign_id, $children) {
      $all_ids = array_keys($children);
      $all_ids[] = $campaign_id;
      $all_ids_list = implode(',', $all_ids);

      $total_costs = self::getTotalCosts($campaign_id, $children);
      $contribution_count = self::getTotalContributionCount($campaign_id, $children);

      $query = "
      SELECT COUNT(id)
      FROM civicrm_contribution first_contribution
      WHERE first_contribution.campaign_id IN ($all_ids_list)
        AND NOT EXISTS (SELECT id
                        FROM civicrm_contribution other_contribution
                        WHERE other_contribution.contact_id = first_contribution.contact_id
                          AND other_contribution.receive_date < first_contribution.receive_date);";
      $first_contribution_count = CRM_Core_DAO::singleValueQuery($query);

      // get all first
      $kpi["amount_first"] = array(
         "id"          => "amount_first",
         "title"       => ts("Number of First Contributions", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "number",
         "vis_type"    => "none",
         "description" => ts("Number of first contributions associated with this campaign", array('domain' => 'de.systopia.campaign')),
         "value"       => $first_contribution_count,
         "link"        => ""
      );

      // get average cost per first contribution
      $kpi['amount_average_first'] = array(
         "id"          => "amount_average_first",
         "title"       => ts("Average Cost per First Contribution", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "money",
         "vis_type"    => "none",
         "description" => ts("Average Cost per first contribution associated with this campaign", array('domain' => 'de.systopia.campaign')),
         "value"       => $total_costs / $first_contribution_count,
         "link"        => ""
      );


      if ($contribution_count - $first_contribution_count > 0) {
         $avg_cost_per_second_or_later = $total_costs / ($contribution_count - $first_contribution_count);
      } else {
         $avg_cost_per_second_or_later = 0;
      }

      // get average cost per second or later contribution
      $kpi['amount_average_second_or_later'] = array(
         "id"          => "amount_average_second",
         "title"       => ts("Average Cost per Second or Later Contribution", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "money",
         "vis_type"    => "none",
         "description" => ts("Average Cost per second or later contribution associated with this campaign", array('domain' => 'de.systopia.campaign')),
         "value"       => $avg_cost_per_second_or_later,
         "link"        => ""
      );
   }

   /**
    * Calculate some generic stats on contributions
    *
    * @author N. Bochan
    */
   public static function calculateRevenue(&$kpi, $campaign_id, $children) {
      $total_revenue  = self::getTotalRevenue($campaign_id, $children);

      $kpi["total_revenue"] = array(
         "id"          => "total_revenue",
         "title"       => ts("Total Revenue", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "money",
         "vis_type"    => "none",
         "description" => ts("Total Revenue", array('domain' => 'de.systopia.campaign')),
         "value"       => isset($total_revenue) ? $total_revenue : 0.00,
         "link"        => ""
      );
   }

   /**
    * Calculate some generic stats on contributions
    *
    * @author N. Bochan
    */
   public static function calculateGenericContributionStats(&$kpi, $campaign_id, $children) {
      $all_ids = array_keys($children);
      $all_ids[] = $campaign_id;
      $all_ids_list = implode(',', $all_ids);

      // get the status IDs
      $status = self::getContributionStatusList();
      $negative_status_list = self::getNegativeContributionStatusIDs();

      // CALCULATE COMPLETED CONTRIBUTIONS
      $query = "
      SELECT   COUNT(contrib.id) as amount_completed,
               AVG(contrib.total_amount) as amount_average
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ({$all_ids_list})
      AND   contrib.contribution_status_id = {$status['completed']};";

      $contribution = CRM_Core_DAO::executeQuery($query);
      while ($contribution->fetch()) {
         $amount_completed = $contribution->amount_completed;
         $amount_average = is_null($contribution->amount_average) ? 0.00 : $contribution->amount_average;
      }

      $kpi["amount_completed"] = array(
         "id" => "amount_completed",
         "title" => ts("Number of Contributions (completed)", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "number",
         "vis_type" => "none",
         "description" => ts("Number of completed contributions", array('domain' => 'de.systopia.campaign')),
         "value" => isset($amount_completed) ? $amount_completed : 0.00,
         "link" => ""
      );
      $kpi["amount_average"] = array(
         "id" => "amount_average",
         "title" => ts("Average Amount of Contributions", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "money",
         "vis_type" => "none",
         "description" => ts("Average amount of completed contributions", array('domain' => 'de.systopia.campaign')),
         "value" => isset($amount_average) ? $amount_average : 0.00,
         "link" => ""
      );

      // CALCULATE NOT-NEGATIVE CONTRIBUTIONS
      $total_contribution_count = self::getTotalContributionCount($campaign_id, $children);

      // TODO: rename (amount -> count)
      $kpi["amount_all"] = array(
         "id"          => "amount_all",
         "title"       => ts("Number of Contributions (all but cancelled/refunded/failed)", array('domain' => 'de.systopia.campaign')),
         "kpi_type"    => "number",
         "vis_type"    => "none",
         "description" => ts("Number of Contributions (all but cancelled/failed)", array('domain' => 'de.systopia.campaign')),
         "value"       => $total_contribution_count,
         "link"        => ""
      );
   }

   /**
    * Calculate the revenue breakdown by subcampaign
    *
    * @author N. Bochan
    */
   public static function calculateRevenueBreakdown(&$kpi, $campaign_id, $children) {
      $all_ids = array_keys($children);
      $all_ids[] = $campaign_id;
      $all_ids_list = implode(',', $all_ids);

      // get the status IDs
      $negative_status_list = self::getNegativeContributionStatusIDs();
      $total_revenue = self::getTotalRevenue($campaign_id, $children);
      $revenue_breakdown = array();

      // create a generic query
      $query = "
      SELECT    SUM(contrib.total_amount) AS revenue,
                camp.title                AS label
      FROM  civicrm_contribution contrib,
            civicrm_campaign camp
      WHERE contrib.campaign_id IN (%s)
      AND   contrib.campaign_id = camp.id
      AND   contrib.contribution_status_id NOT IN ({$negative_status_list})";

      // RUN QUERY for the campaign itself
      $contribution = CRM_Core_DAO::executeQuery(sprintf($query, $campaign_id));
      while ($contribution->fetch()) {
         if ($contribution->revenue) {
            $revenue_breakdown[] = array("label" => $contribution->label, "value" => (double) ($contribution->revenue / $total_revenue));
         }
      }

      // RUN QUERY FOR EACH SUBCAMPAIGN
      $revenue_subcampaigns = array();
      $subcampaign_child_ids = array();
      $children = CRM_Campaign_Tree::getCampaignIds($campaign_id, 0);

      if(count($children['children']) > 0) {
         $children = $children['children'];

         foreach ($children as $child_id => $label) {
           $subcampaigns = CRM_Campaign_Tree::getCampaignIds($child_id, 99);
           $subcampaign_child_ids = array($child_id);
           if(count($subcampaigns['children']) > 0) {
              $subcampaigns = $subcampaigns['children'];
              foreach ($subcampaigns as $key => $value) {
                $subcampaign_child_ids[] = $key;
              }
           }
           $id_string = implode(',', $subcampaign_child_ids);
           $curr_contrib = CRM_Core_DAO::executeQuery(sprintf($query, $id_string));
           while ($curr_contrib->fetch()) {
             if ($curr_contrib->revenue) {
               $revenue_breakdown[] = array("label" => $label, "value" => (double) $curr_contrib->revenue / $total_revenue);
             }
           }
         }
      }

      if (!empty($revenue_breakdown)) {
         $kpi["revenue_breakdown"] = array(
            "id"          => "revenue_breakdown",
            "title"       => ts("Revenue Breakdown", array('domain' => 'de.systopia.campaign')),
            "kpi_type"    => "hidden",
            "vis_type"    => "pie_chart",
            "description" => ts("Revenue Breakdown", array('domain' => 'de.systopia.campaign')),
            "value"       => $revenue_breakdown,
            "link"        => ""
         );
      }
   }

   /**
    * Calculate revenue breakdown by date
    *
    * @author N. Bochan
    */
   public static function calculateDonationHeartbeat(&$kpi, $campaign_id, $children) {
      $all_ids = array_keys($children);
      $all_ids[] = $campaign_id;
      $all_ids_list = implode(',', $all_ids);

      // get the status IDs
      $negative_status_list = self::getNegativeContributionStatusIDs();

      $query_contribs = "
      SELECT `receive_date` AS date,
              COUNT(*)      AS value
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ( {$all_ids_list} )
      AND contrib.contribution_status_id NOT IN ({$negative_status_list})
      GROUP BY DATE(`receive_date`);";
      $all_contribs = array();

      $contribution = CRM_Core_DAO::executeQuery($query_contribs);
      while ($contribution->fetch()) {
        $date = new DateTime($contribution->date);
        $date = $date->format('Y-m-d 00:00:00');
        $all_contribs[] = array("date" => $date, "value" => $contribution->value);
      }

      if (!empty($all_contribs)) {
         $kpi["donation_heartbeat"] = array(
            "id" => "donation_heartbeat",
            "title" => ts("Donation Heartbeat", array('domain' => 'de.systopia.campaign')),
            "kpi_type" => "hidden",
            "vis_type" => "line_graph",
            "description" => ts("Donation Heartbeat", array('domain' => 'de.systopia.campaign')),
            "value" => $all_contribs,
            "link" => ""
         );
      }
   }





   /************************************************************
    **                HELPERS                                 **
    ************************************************************/
   /**
    * get a count of all associated contributoins
    */
   protected static function getTotalContributionCount($campaign_id, $children) {
      $all_ids = array_keys($children);
      $all_ids[] = $campaign_id;
      $all_ids_list = implode(',', $all_ids);

      // get the status IDs
      $negative_status_list = self::getNegativeContributionStatusIDs();

      $query = "
      SELECT COUNT(contrib.id)
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ({$all_ids_list})
      AND   contrib.contribution_status_id NOT IN ({$negative_status_list});";

      $value = CRM_Core_DAO::singleValueQuery($query);
      if ($value) {
         return $value;
      } else {
         return '0.00';
      }
   }

   /**
    * get the list of contribution statuses
    */
   protected static function getContributionStatusList() {
      if (!isset(self::$cache['contribution_status_list'])) {
         $status_list = array();
         $status_list['completed'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
         $status_list['refunded']  = CRM_Core_OptionGroup::getValue('contribution_status', 'Refunded',  'name');
         $status_list['cancelled'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name');
         $status_list['failed']    = CRM_Core_OptionGroup::getValue('contribution_status', 'Failed',    'name');
         self::$cache['contribution_status_list'] = $status_list;
      }
      return self::$cache['contribution_status_list'];
   }


   /**
    * get the list of contribution statuses
    */
   protected static function getNegativeContributionStatusIDs() {
      $status_list = self::getContributionStatusList();

      $negative_statuses = array();
      if (!empty($status_list['refunded']))  $negative_statuses[] = $status_list['refunded'];
      if (!empty($status_list['cancelled'])) $negative_statuses[] = $status_list['cancelled'];
      if (!empty($status_list['failed']))    $negative_statuses[] = $status_list['failed'];

      if (empty($negative_statuses)) {
         error_log("de.systopia.campaign: KPIs couldn't be calculated, something's wrong with the contributoin statuses.");
         $negative_statuses[] = 999; // prevent SQL errors
      }

      return implode(',', $negative_statuses);
   }

   /**
    * Get total (recursive) costs of the campaign
    */
   protected static function getTotalCosts($campaign_id, $children) {
      if (!isset(self::$cache[$campaign_id]['total_costs'])) {
         $result = civicrm_api3('CampaignExpense', 'getsum', array('campaign_id' => $campaign_id));
         self::$cache[$campaign_id]['total_costs'] = (double) $result['values'][$result['id']];
      }
      return self::$cache[$campaign_id]['total_costs'];
   }

   /**
    * Get total (recursive) revenue of the campaing
    */
   protected static function getTotalRevenue($campaign_id, $children) {
      if (!isset(self::$cache[$campaign_id]['total_revenue'])) {
         $all_ids = array_keys($children);
         $all_ids[] = $campaign_id;
         $all_ids_list = implode(',', $all_ids);

         // get the status IDs
         $status = self::getContributionStatusList();

         $query = "
         SELECT    SUM(contrib.total_amount)
         FROM  civicrm_contribution contrib
         WHERE contrib.campaign_id IN ( {$all_ids_list} )
         AND   contrib.contribution_status_id = {$status['completed']};";
         self::$cache[$campaign_id]['total_revenue'] = (double) CRM_Core_DAO::singleValueQuery($query);
      }

      return self::$cache[$campaign_id]['total_revenue'];
   }
}
