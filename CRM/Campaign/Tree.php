<?php

class CRM_Campaign_Tree {

   /**
   * Get position related information of a campaign in the campaign tree
   *
   * This includes:
   * - number of child nodes (sub campaigns)
   * - number of parent nodes (campaign level)
   * - id and title of all parent nodes, the root node and all first level children
   */


   /**
   * Get all child nodes of campaign
   *
   *
   * @param integer $id campaign id
   * @param integet $depth maximum depth
   *
   * @return array
   */

   public static function getCampaignIds($id, $depth) {
      // get all sub campaigns of current id
      $query = "
      SELECT    camp.id,
                camp.title
      FROM  civicrm_campaign camp
      WHERE camp.parent_id = %1
      Order By  camp.id;
      ";

      $children = array();
      $new_nodes = array();
      $new_nodes[] = $root = $id;
      $current_depth = 0;

      while(!empty($new_nodes) && $current_depth <= $depth) {
         $current_id = array_shift($new_nodes);
         $campaign = CRM_Core_DAO::executeQuery($query, array(1 => array($current_id, 'Integer')));

         while ($campaign->fetch()) {
            if($children[$campaign->id] || $campaign->id == $root) {
               throw new CRM_Core_Exception("de.systopia.campaign: cycle detected! id: " . $campaign->id );
            }
            $new_nodes[] = $campaign->id;
            $children[$campaign->id] = $campaign->title;
         }
         $current_depth++;
      }


      $result = array('children' => $children);
      return $result;
   }

   public static function getCampaignParentIds($id) {
      // get all parent campaigns of current id
      $query = "
      SELECT    camp.id,
                camp.title,
                camp.parent_id
      FROM  civicrm_campaign camp
      WHERE camp.id = %1
      Order By  camp.id;
      ";

      $parents = array();
      $current_id = $base = $id;

      while($current_id != NULL) {
         $campaign = CRM_Core_DAO::executeQuery($query, array(1 => array($current_id, 'Integer')));
         while($campaign->fetch()) {
            if($parents[$campaign->id]) {
               break 2;
            } elseif ($campaign->id == $base) {
               continue;
            } else {
               //TODO: do not discard the order of campaign by using a nested array
               $parents[$campaign->id] = $campaign->title;
               $root = $campaign->id;
            }
         }
         $current_id = $campaign->parent_id;
      }

      $result = array('parents' => $parents, 'root' => $root);
      return $result;
   }

}
