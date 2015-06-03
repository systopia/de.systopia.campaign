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
          }
        }
      });

     }
   ]);

   campaign.controller('DashboardCtrl', ['$scope', '$routeParams', function($scope, $routeParams) {
    $scope.ts = CRM.ts('de.systopia.campaign');

  }]);

  campaign.controller('CampaignDashboardCtrl', ['$scope', '$routeParams', 'currentCampaign', function($scope, $routeParams, currentCampaign) {
     $scope.ts = CRM.ts('de.systopia.campaign');
     $scope.currentCampaign = currentCampaign;
     console.log($scope.currentCampaign); // TODO: remove

  }]);

})(angular, CRM.$, CRM._);
