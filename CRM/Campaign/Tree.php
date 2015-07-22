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
            if(self::is_parent($campaign->id, $parents)) {
               break 2;
            } elseif ($campaign->id == $base) {
               continue;
            } else {
               $parents[] = array("id" => $campaign->id, "title" => $campaign->title);
               $root = $campaign->id;
            }
         }
         $current_id = $campaign->parent_id;
      }

      $result = array('parents' => $parents, 'root' => $root);
      return $result;
   }

   public static function is_parent($id, $parents) {
      foreach($parents as $p) {
         if(isset($p['id']) && $p['id'] == $id) {
            return true;
         }
      }
      return false;
   }

   /**
   * Get a tree of a campaign
   *
   *
   * @param integer $id campaign id
   * @param integet $depth maximum depth
   *
   * @return array
   */

   public static function getCampaignTree($id, $depth) {
     $children = array();

     // get current campaign
     $first_query = "
     SELECT    camp.id,
               camp.title,
               camp.parent_id
     FROM  civicrm_campaign camp
     WHERE camp.id = %1;
     ";

     $campaign = CRM_Core_DAO::executeQuery($first_query, array(1 => array($id, 'Integer')));
     while ($campaign->fetch()) {
       $children[] = array('id' => $campaign->id, 'name' => $campaign->title, 'parentid' => 0);
     }

     // get all sub campaigns of current id
     $query = "
     SELECT    camp.id,
               camp.title,
               camp.parent_id
     FROM  civicrm_campaign camp
     WHERE camp.parent_id = %1
     Order By  camp.id;
     ";


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
           $children[] = array('id' => $campaign->id, 'name' => $campaign->title, 'parentid' => $campaign->parent_id);
        }
        $current_depth++;
     }

    //  $arr = array(
    //    array('id'=>100, 'parentid'=>0, 'name'=>'a'),
    //    array('id'=>101, 'parentid'=>100, 'name'=>'a'),
    //   );

      $new = array();
      foreach ($children as $a){
         $new[$a['parentid']][] = $a;
      }

      $tree = self::createTree($new, $new[0]);
      return json_encode($tree);
   }

   public static function createTree(&$list, $parent){
      $tree = array();
      foreach ($parent as $k=>$v){
          if(isset($list[$v['id']])){
              $v['children'] = self::createTree($list, $list[$v['id']]);
          }
          $tree[] = $v;
      }
      return $tree;
   }

   /**
   * Set a parent of a campaign node
   *
   *
   * @param integer $id campaign id
   * @param integet $parentid new parent id
   *
   * @return empty
   */

   public static function setNodeParent($id, $parentid) {

     $query = "
     UPDATE    civicrm_campaign camp
     SET       camp.parent_id = %1
     WHERE camp.id = %2;
     ";

     CRM_Core_DAO::executeQuery($query, array(1 => array($parentid, 'Integer'), 2 => array($id, 'Integer')));
     return civicrm_api3_create_success();
  }

}
