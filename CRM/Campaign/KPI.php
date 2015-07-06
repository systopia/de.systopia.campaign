<?php

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
         $campaigns = $campaigns['children'];
      }
      $ids_list = implode(',', $ids);


      // needed status ids
      $status = array();
      $status['completed'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
      $status['cancelled'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name');
      $status['failed'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Failed', 'name');

      // get total revenue
      $query = "
      SELECT    SUM(contrib.total_amount) as revenue
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ( $ids_list )
      AND   contrib.contribution_status_id NOT IN ({$status['cancelled']}, {$status['failed']})
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      $kpi = array();
      while ($contribution->fetch()) {
         $total_revenue = is_null($contribution->revenue) ? 0.00 : $contribution->revenue;
      }

      $kpi["total_revenue"] = array(
         "id" => "total_revenue",
         "title" => "Total Revenue",
         "type" => "money",
         "description" => "Total revenue",
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
         "title" => "Total Revenue Goal",
         "type" => "money",
         "description" => "Total revenue goal",
         "value" => isset($total_revenue_goal) ? $total_revenue_goal : 0.00,
         "link" => ""
      );

      // get all completed and average contribution amount
      $query = "
      SELECT   COUNT(contrib.id) as amount_completed,
               AVG(contrib.total_amount) as amount_average
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ($ids_list)
      AND   contrib.contribution_status_id = {$status['completed']};
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      while ($contribution->fetch()) {
         $amount_completed = $contribution->amount_completed;
         $amount_average = is_null($contribution->amount_average) ? 0.00 : $contribution->amount_average;
      }

      $kpi["amount_completed"] = array(
         "id" => "amount_completed",
         "title" => "Amount of Contributions (completed)",
         "type" => "number",
         "description" => "Amount of completed contributions",
         "value" => isset($amount_completed) ? $amount_completed : 0.00,
         "link" => ""
      );

      $kpi["amount_average"] = array(
         "id" => "amount_average",
         "title" => "Average Amount of Contributions",
         "type" => "money",
         "description" => "Average amount of completed contributions",
         "value" => isset($amount_average) ? $amount_average : 0.00,
         "link" => ""
      );

      // get all but cancelled and failed
      $query = "
      SELECT   COUNT(contrib.id) as amount_all
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ($ids_list)
      AND   contrib.contribution_status_id NOT IN ({$status['cancelled']}, {$status['failed']});
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      while ($contribution->fetch()) {
         $amount_all = $contribution->amount_all;
      }

      $kpi["amount_all"] = array(
         "id" => "amount_all",
         "title" => "Amount of Contributions (all but cancelled/failed)",
         "type" => "number",
         "description" => "Amount of Contributions (all but cancelled/failed)",
         "value" => isset($amount_all) ? $amount_all : 0.00,
         "link" => ""
      );

      // get all first
      // TODO
      //$kpi['amount_first'] = 'TODO';

      // get average cost per first contribution
      // TODO
      //$kpi['amount_average_first'] = 'TODO';

      // get all expenses
      $result = civicrm_api3('CampaignExpense', 'getsum', array(
           'campaign_id' => $id
         ));
      if($result['is_error'] == 0) {
            $total_costs = $result['values'][$result['id']];
      }
      $kpi["total_cost"] = array(
         "id" => "ttlcost",
         "title" => "Total Costs",
         "type" => "money",
         "description" => "Sum of (known) expenses to this campaign",
         "value" => isset($total_costs) ? $total_costs : 0.00,
         "link" => ""
      );

      // get ROI
      $kpi["roi"] = array(
         "id" => "roi",
         "title" => "ROI",
         "type" => "percentage",
         "description" => "Return on investment",
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
         "title" => "Total Revenue Reached",
         "type" => "percentage",
         "description" => "Total Revenue reached",
         "value" => $total_revenue_goal_pc,
         "link" => ""
      );

      return json_encode($kpi);
   }

}
