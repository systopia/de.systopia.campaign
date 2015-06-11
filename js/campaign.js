(function(angular, $, _) {
   var resourceUrl = CRM.resourceUrls['de.systopia.campaign'];
   var campaign = angular.module('campaign', ['ngRoute', 'crmUtil', 'crmUi']);

   campaign.config(['$routeProvider',
     function($routeProvider) {
      $routeProvider.when('/campaign', {
         templateUrl: resourceUrl + '/partials/dashboard.html',
         controller: 'DashboardCtrl',
         resolve: {
          test: function($route, crmApi) {
            var test = crmApi('CampaignTree', 'getid', {id: 1});
            console.log(test);
            return test;
          }
         }
      });

      $routeProvider.when('/campaign/:id/view', {
         templateUrl: resourceUrl + '/partials/campaign_dashboard.html',
         controller: 'CampaignDashboardCtrl',
         resolve: {
          currentCampaign: function($route, crmApi) {
            return crmApi('Campaign', 'getsingle', {id: $route.current.params.id});
          },
          children: function($route, crmApi) {
             return crmApi('CampaignTree', 'getids', {id: $route.current.params.id, depth: 20});
          },
          parents: function($route, crmApi) {
             return crmApi('CampaignTree', 'getparentids', {id: $route.current.params.id});
          },
          kpi: function($route, crmApi) {
             return crmApi('CampaignKpi', 'get', {id: $route.current.params.id});
          },
        }
      });

     }
   ]);

   campaign.controller('DashboardCtrl', ['$scope', '$routeParams', function($scope, $routeParams) {
    $scope.ts = CRM.ts('de.systopia.campaign');

  }]);

  campaign.controller('CampaignDashboardCtrl', ['$scope', '$routeParams',
  '$sce',
  'currentCampaign',
  'children',
  'parents',
  'kpi', function($scope, $routeParams, $sce, currentCampaign, children, parents, kpi) {
     $scope.ts = CRM.ts('de.systopia.campaign');
     $scope.currentCampaign = currentCampaign;
     $scope.currentCampaign.goal_general_htmlSafe = $sce.trustAsHtml($scope.currentCampaign.goal_general);
     $scope.currentCampaign.start_date_date = $.datepicker.formatDate(CRM.config.dateInputFormat, new Date($scope.currentCampaign.start_date));
     $scope.currentCampaign.end_date_date = $.datepicker.formatDate(CRM.config.dateInputFormat, new Date($scope.currentCampaign.end_date));
     $scope.children = children.children;
     $scope.kpi = kpi.kpi;
     console.log($scope.kpi);
     console.log($scope.currentCampaign);
     $scope.parents = parents.parents.reverse();
     $scope.numberof = {
        parents: Object.keys($scope.parents).length,
        children: Object.keys($scope.children).length,
        };
     $scope.subcampaign_link = CRM.url('civicrm/campaign/add', {reset: 1, pid: $scope.currentCampaign.id});
  }]);

})(angular, CRM.$, CRM._);
