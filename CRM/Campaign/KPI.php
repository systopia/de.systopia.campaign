<?php
/*-------------------------------------------------------+
| de.systopia.campaign                                   |
| Copyright (C) 2015 SYSTOPIA                            |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once('CRM/CampaignTree/Tree.php');

class CRM_Campaign_KPI {

   /**
   * Get Key Performance Indicators (KPIs) for a specific campaign (-subtree):
   *
   * Total Costs (sum of expenses)
   * Total revenue (sum of contributions connected with campaign)
   * Total revenue Goal (field in campaign)
   * Number/amount of Contributions (all but cancelled & failed)
   * Number/amount of Contributions (only completed)
   * Number of first contributions
   * ROI (Total revenue divided by total costs)
   * average contribution amount
   * average cost per first contribution
   * Revenue goal reached in percent (total revenue divided by total revenue goal *100)
   */

   public static function getCampaignKPI($id) {

      // get all sub-campaigns
      $campaigns = CRM_Campaign_Tree::getCampaignIds($id, 99);
      $ids = array();
      $ids[] = $id;

      if(count($campaigns['children']) > 0) {
         $campaigns_ids = $campaigns;
         $campaigns = $campaigns['children'];
      }
      $ids_list = implode(',', $ids);


      // needed status ids
      $status = array();
      $status['completed'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
      $status['refunded']  = CRM_Core_OptionGroup::getValue('contribution_status', 'Refunded', 'name');
      $status['cancelled'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name');
      $status['failed']    = CRM_Core_OptionGroup::getValue('contribution_status', 'Failed', 'name');
      $negative_statuses = array();
      if (!empty($status['refunded'])) $negative_statuses[] = $status['refunded'];
      if (!empty($status['cancelled'])) $negative_statuses[] = $status['cancelled'];
      if (!empty($status['failed'])) $negative_statuses[] = $status['failed'];
      $negative_status_list = implode(',', $negative_statuses);
      if (empty($status['completed']) || empty($negative_status_list)) {
         error_log("de.systopia.campaign: KPIs couldn't be calculated, something's wrong with the contributoin statuses.");
         return;
      }


      // get total revenue
      if(count($campaigns_ids['children']) > 0) {
         $ids_list_tr = implode(',', array_merge(array($id), array_keys($campaigns)));
      }else{
        $ids_list_tr = $ids_list;
      }

      $query = "
      SELECT    SUM(contrib.total_amount) as revenue
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ( $ids_list_tr )
      AND   contrib.contribution_status_id = {$status['completed']};
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      $kpi = array();
      $total_revenue = 0.00;
      while ($contribution->fetch()) {
         $total_revenue = is_null($contribution->revenue) ? 0.00 : $contribution->revenue;
      }

      $kpi["total_revenue"] = array(
         "id" => "total_revenue",
         "title" => ts("Total Revenue", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "money",
         "vis_type" => "none",
         "description" => ts("Total Revenue", array('domain' => 'de.systopia.campaign')),
         "value" => isset($total_revenue) ? $total_revenue : 0.00,
         "link" => ""
      );

      // get total revenue goal
      $query = "
      SELECT camp.goal_revenue
      FROM  civicrm_campaign camp
      WHERE camp.id = $id;
      ";

      $campaign = CRM_Core_DAO::executeQuery($query);
      while ($campaign->fetch()) {
         $total_revenue_goal = is_null($campaign->goal_revenue) ? 0.00 : $campaign->goal_revenue;
      }

      $kpi["total_revenue_goal"] = array(
         "id" => "total_revenue_goal",
         "title" => ts("Total Revenue Goal", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "money",
         "vis_type" => "none",
         "description" => ts("Total Revenue Goal", array('domain' => 'de.systopia.campaign')),
         "value" => isset($total_revenue_goal) ? $total_revenue_goal : 0.00,
         "link" => ""
      );

      // get all completed and average contribution amount
      $query = "
      SELECT   COUNT(contrib.id) as amount_completed,
               AVG(contrib.total_amount) as amount_average
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ($ids_list_tr)
      AND   contrib.contribution_status_id = {$status['completed']};
      ";

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

      // get all but cancelled and failed
      $query = "
      SELECT   COUNT(contrib.id) as amount_all
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ($ids_list_tr)
      AND   contrib.contribution_status_id NOT IN ({$negative_status_list});
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      while ($contribution->fetch()) {
         $amount_all = $contribution->amount_all;
      }

      $kpi["amount_all"] = array(
         "id" => "amount_all",
         "title" => ts("Number of Contributions (all but cancelled/refunded/failed)", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "number",
         "vis_type" => "none",
         "description" => ts("Number of Contributions (all but cancelled/failed)", array('domain' => 'de.systopia.campaign')),
         "value" => isset($amount_all) ? $amount_all : 0.00,
         "link" => ""
      );

      // get all expenses
      $result = civicrm_api3('CampaignExpense', 'getsum', array(
           'campaign_id' => $id
         ));
      if($result['is_error'] == 0) {
            $total_costs = $result['values'][$result['id']];
      }
      $kpi["total_cost"] = array(
         "id" => "ttlcost",
         "title" => ts("Total Costs", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "money",
         "vis_type" => "none",
         "description" => ts("Sum of (known) expenses to this campaign", array('domain' => 'de.systopia.campaign')),
         "value" => isset($total_costs) ? $total_costs : 0.00,
         "link" => ""
      );


      $query = "
      SELECT COUNT(id)
      FROM civicrm_contribution first_contribution
      WHERE first_contribution.campaign_id IN ($ids_list_tr)
        AND NOT EXISTS (SELECT id
                        FROM civicrm_contribution other_contribution
                        WHERE other_contribution.contact_id = first_contribution.contact_id
                          AND other_contribution.receive_date < first_contribution.receive_date);";

      $first_contributions = CRM_Core_DAO::singleValueQuery($query);

      // get all first
      $kpi["amount_first"] = array(
         "id" => "amount_first",
         "title" => ts("Number of First Contributions", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "number",
         "vis_type" => "none",
         "description" => ts("Number of first contributions associated with this campaign", array('domain' => 'de.systopia.campaign')),
         "value" => $first_contributions,
         "link" => ""
      );

      // get average cost per first contribution
      $kpi['amount_average_first'] = array(
         "id" => "amount_average_first",
         "title" => ts("Average Cost per First Contribution", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "money",
         "vis_type" => "none",
         "description" => ts("Average Cost per first contribution associated with this campaign", array('domain' => 'de.systopia.campaign')),
         "value" => $total_costs / $first_contributions,
         "link" => ""
      );

      if ($amount_all - $first_contributions > 0) {
         $avg_cost_per_second_or_later = $total_costs / ($amount_all - $first_contributions);
      } else {
         $avg_cost_per_second_or_later = 0;
      }

      // get average cost per second or later contribution
      $kpi['amount_average_second_or_later'] = array(
         "id" => "amount_average_second",
         "title" => ts("Average Cost per Second or Later Contribution", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "money",
         "vis_type" => "none",
         "description" => ts("Average Cost per second or later contribution associated with this campaign", array('domain' => 'de.systopia.campaign')),
         "value" => $avg_cost_per_second_or_later,
         "link" => ""
      );


      // get ROI
      $kpi["roi"] = array(
         "id" => "roi",
         "title" => ts("ROI", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "number",
         "vis_type" => "none",
         "description" => ts("Return on investment", array('domain' => 'de.systopia.campaign')),
         "value" => $total_revenue / (($total_costs == 0.00) ? 1.00 : $total_costs),
         "link" => "https://en.wikipedia.org/wiki/Return_on_investment"
      );

      // get revenue goal reached percent
      if($total_revenue_goal) {
         $total_revenue_goal_pc = ($total_revenue / $total_revenue_goal);
      }else{
         $total_revenue_goal_pc = -1;
      }

      $kpi["total_revenue_goal_pc"] = array(
         "id" => "total_revenue_goal_pc",
         "title" => ts("Total Revenue Reached", array('domain' => 'de.systopia.campaign')),
         "kpi_type" => "percentage",
         "vis_type" => "none",
         "description" => ts("Total Revenue Reached", array('domain' => 'de.systopia.campaign')),
         "value" => $total_revenue_goal_pc,
         "link" => ""
      );

      // get revenue breakdown
      $query = "
      SELECT    SUM(contrib.total_amount) as revenue,
                camp.title as label
      FROM  civicrm_contribution contrib,
            civicrm_campaign camp
      WHERE contrib.campaign_id IN ( %s )
      AND   contrib.campaign_id = camp.id
      AND   contrib.contribution_status_id NOT IN ({$negative_status_list})
      ";

      $e_query = sprintf($query, $id);
      $contribution = CRM_Core_DAO::executeQuery($e_query);
      while ($contribution->fetch()) {
         $revenue_current = array("label" => $contribution->label, "value" => (is_null($contribution->revenue) ? 0.00 : $contribution->revenue) / $total_revenue);
      }

      $revenue_subcampaigns = array();
      $tmp_idslist = array();
      $children = CRM_Campaign_Tree::getCampaignIds($id, 0);

      if(count($children['children']) > 0) {
         $children = $children['children'];

         foreach ($children as $c_id => $label) {
           $subcampaigns = CRM_Campaign_Tree::getCampaignIds($c_id, 99);

           $tmp_idslist[] = $c_id;
           if(count($subcampaigns['children']) > 0) {
              $subcampaigns = $subcampaigns['children'];
              foreach ($subcampaigns as $key => $value) {
                $tmp_idslist[] = $key;
              }

           }
           $id_string = implode(',', $tmp_idslist);
           $e_query = sprintf($query, $id_string);

           $curr_contrib = CRM_Core_DAO::executeQuery($e_query);
           while ($curr_contrib->fetch()) {
             if (is_null($curr_contrib->revenue)) {
               continue;
             }
             $revenue_subcampaigns[] = array("label" => $label, "value" => $curr_contrib->revenue / $total_revenue);
           }

           $tmp_idslist = array();
         }

      }

      $revenue_combined = array();
      $revenue_combined[] = $revenue_current;
      $revenue_combined = array_merge($revenue_combined, $revenue_subcampaigns);
      if ($revenue_current['value'] || !empty($revenue_subcampaigns)) {
         $kpi["revenue_breakdown"] = array(
            "id" => "revenue_breakdown",
            "title" => ts("Revenue Breakdown", array('domain' => 'de.systopia.campaign')),
            "kpi_type" => "hidden",
            "vis_type" => "pie_chart",
            "description" => ts("Revenue Breakdown", array('domain' => 'de.systopia.campaign')),
            "value" => $revenue_combined,
            "link" => ""
         );
      }

      // get donation heartbeat
      if(count($campaigns_ids['children']) > 0) {
        $ids_list_hb = implode(',', array_merge(array($id), array_keys($campaigns)));
      }else{
        $ids_list_hb = $ids_list;
      }

      $query_contribs = "
      SELECT `receive_date` as date,
              COUNT(*) as value
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ( $ids_list_hb )
      AND contrib.contribution_status_id NOT IN ({$negative_status_list})
      GROUP BY DATE(`receive_date`)
      ;";
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

      CRM_Utils_CampaignCustomisationHooks::campaign_kpis($id, $kpi, 99);

      return json_encode($kpi);
   }

}
