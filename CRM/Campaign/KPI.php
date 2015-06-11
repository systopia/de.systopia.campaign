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
      if(count($campaigns['children']) > 0) {
         $campaigns = $campaigns['children'];
         $ids = array();
         $ids[] = $id;

         // get kpi for current campaign only (for now)
         // foreach($campaigns as $id => $title) {
         //    $ids[] = $id;
         // }

         $ids_list = implode(',', $ids);
      }else{
         $ids_list = "-1";
      }


      // needed status ids
      $status = array();
      $status['completed'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
      $status['cancelled'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name');
      $status['failed'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Failed', 'name');

      // get total revenue
      // TODO: use only completed?
      $query = "
      SELECT    SUM(contrib.net_amount) as revenue
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ( $ids_list );
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      $kpi = array();
      while ($contribution->fetch()) {
         $kpi['total_revenue'] = is_null($contribution->revenue) ? 0.00 : $contribution->revenue;
      }

      // get total revenue goal
      $query = "
      SELECT camp.goal_revenue
      FROM  civicrm_campaign camp
      WHERE camp.id = $id;
      ";

      $campaign = CRM_Core_DAO::executeQuery($query);
      while ($campaign->fetch()) {
         $kpi['total_revenue_goal'] = is_null($campaign->goal_revenue) ? 0.00 : $campaign->goal_revenue;
      }

      // get all completed and average contribution amount
      $query = "
      SELECT   COUNT(contrib.id) as amount_completed,
               AVG(contrib.net_amount) as amount_average
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ($ids_list)
      AND   contrib.contribution_status_id = {$status['completed']};
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      while ($contribution->fetch()) {
         $kpi['amount_completed'] = $contribution->amount_completed;
         $kpi['amount_average'] = is_null($contribution->amount_average) ? 0.00 : $contribution->amount_average;
      }

      // get all but cancelled and failed
      $query = "
      SELECT   COUNT(contrib.id) as amount_all
      FROM  civicrm_contribution contrib
      WHERE contrib.campaign_id IN ($ids_list)
      AND   contrib.contribution_status_id NOT IN ({$status['cancelled']}, {$status['failed']});
      ";

      $contribution = CRM_Core_DAO::executeQuery($query);
      while ($contribution->fetch()) {
         $kpi['amount_all'] = $contribution->amount_all;
      }

      // get all first
      // TODO
      $kpi['amount_first'] = 'TODO';

      // get average cost per first contribution
      // TODO
      $kpi['amount_average_first'] = 'TODO';

      // get all expenses
      // TODO: use api
      $kpi['total_costs'] = 0.00;

      // get ROI
      $kpi['roi'] = $kpi['total_revenue'] / (($kpi['total_costs'] == 0.00) ? 1.00 : $kpi['total_costs']);

      // get revenue goal reached percent
      if($kpi['total_revenue_goal']) {
         $kpi['revenue_goal_reached'] = ($kpi['total_revenue'] / $kpi['total_revenue_goal']);
      }else{
         $kpi['revenue_goal_reached'] = -1;
      }

      //TODO: return the following format
      /*
      [
        {
          "id": "ttlcost",
          "title": "Total Costs",
          "type": "money",
          "description": "Sum of (known) expenses to this campaign",
          "value": "12318.23",
          "link": "http://i.don.t.know.maybe/we/need/this/at/some/point"
        },
        {
          "id": "roi",
          "title": "ROI",
          "type": "percentage",
          "description": "Return on investment",
          "value": "12318.23",
          "link": "https://en.wikipedia.org/wiki/Return_on_investment"
        },
        {
          "id": "newctcts",
          "title": "New Contacts",
          "type": "number",
          "description": "Number of new contacts this campaign has yielded",
          "value": "123"
        }
      ]

      */

      return array('kpi' => $kpi);
   }

}
