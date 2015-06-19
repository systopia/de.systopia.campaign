(function(angular, $, _) {
   var resourceUrl = CRM.resourceUrls['de.systopia.campaign'];
   var campaign = angular.module('campaign', ['ngRoute', 'crmUtil', 'crmUi', 'crmD3']);

   campaign.config(['$routeProvider',
     function($routeProvider) {
      $routeProvider.when('/campaign', {
         templateUrl: resourceUrl + '/partials/dashboard.html',
         controller: 'DashboardCtrl'
      });

      $routeProvider.when('/campaign/:id/view', {
         templateUrl: resourceUrl + '/partials/campaign_dashboard.html',
         controller: 'CampaignDashboardCtrl',
         resolve: {
          currentCampaign: function($route, crmApi) {
            return crmApi('Campaign', 'getsingle', {id: $route.current.params.id});
          },
          children: function($route, crmApi) {
             return crmApi('CampaignTree', 'getids', {id: $route.current.params.id, depth: 1});
          },
          parents: function($route, crmApi) {
             return crmApi('CampaignTree', 'getparentids', {id: $route.current.params.id});
          },
          kpi: function($route, crmApi) {
             return crmApi('CampaignKpi', 'get', {id: $route.current.params.id});
          },
        }
      });

      $routeProvider.when('/campaign/:id/tree', {
        templateUrl: resourceUrl + '/partials/campaign_tree.html',
        controller: 'CampaignTreeCtrl',
        resolve: {
          tree: function($route, crmApi) {
           return crmApi('CampaignTree', 'gettree', {id: $route.current.params.id, depth: 10});
         },
         currentCampaign: function($route, crmApi) {
           return crmApi('Campaign', 'getsingle', {id: $route.current.params.id});
        },
        parents: function($route, crmApi) {
          return crmApi('CampaignTree', 'getparentids', {id: $route.current.params.id});
        },
        }
      });

  }]);

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
     $scope.tree_link = CRM.url('civicrm/a/#/campaign/' + $scope.currentCampaign.id + '/tree', {});
     $scope.subcampaign_link = CRM.url('civicrm/campaign/add', {reset: 1, pid: $scope.currentCampaign.id});
     $scope.edit_link = CRM.url('civicrm/campaign/add', {reset: 1, id: $scope.currentCampaign.id, action: 'update'});
  }]);



  campaign.controller('CampaignTreeCtrl', ['$scope', '$routeParams',
   'tree',
   'currentCampaign',
   'parents',
   function($scope, $routeParams, tree, currentCampaign, parents) {
    $scope.ts = CRM.ts('de.systopia.campaign');

    $scope.current_campaign = currentCampaign;
    $scope.current_tree = JSON.parse(tree.result)[0];
    $scope.parents = parents;

    $scope.campaign_link = CRM.url('civicrm/a/#/campaign/' + $scope.current_campaign.id + '/view', {});
    $scope.parent_link = CRM.url('civicrm/a/#/campaign/' + $scope.current_campaign.parent_id + '/tree', {});
    $scope.root_link = CRM.url('civicrm/a/#/campaign/' + $scope.parents.root + '/tree', {});
  }]);

  campaign.directive("campaignTree", function($window) {
    return{
      restrict: "EA",
      template: "<svg width='850' height='200'></svg>",
      link: function(scope, elem, attrs){
        var treeData=scope[attrs.treeData];
        console.log(treeData);

        var margin = {top: 40, right: 120, bottom: 20, left: 120},
        	width = 960 - margin.right - margin.left,
        	height = 500 - margin.top - margin.bottom;

        var center = [width / 2, height / 2];

        var d3 = $window.d3;
        var rawSvg = elem.find("svg")[0];

        var svg = d3.select(rawSvg)
        .attr("width", width + margin.right + margin.left)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("class","drawarea")
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

         var x = d3.scale.linear()
             .domain([-width / 2, width / 2])
             .range([0, width]);

         var y = d3.scale.linear()
             .domain([-height / 2, height / 2])
             .range([height, 0]);

         var zoom = d3.behavior.zoom()
              .x(x)
              .y(y)
              .center(center)
              .scaleExtent([0.5, 5])
              .on("zoom", zoomed);

        d3.select("svg")
        .call(zoom);

        var resetBtn = d3.select("#tree_container #resetBtn");
        resetBtn.on("click", reset);
      //   var zoomInBtn = d3.select("#tree_container #zoomInBtn");
      //   zoomInBtn.on("click", zoomIn);

        var i = 0;

        var tree = d3.layout.tree()
        	.size([height, width]);

        var diagonal = d3.svg.diagonal()
        	.projection(function(d) { return [d.x, d.y]; });

        root = treeData;

        function zoomed() {
             var scale = d3.event.scale,
                 translation = d3.event.translate,
                 tbound = -height * scale,
                 bbound = height * scale,
                 lbound = (-width + margin.right) * scale,
                 rbound = (width - margin.left) * scale;

             translation = [
                 Math.max(Math.min(translation[0], rbound), lbound),
                 Math.max(Math.min(translation[1], bbound), tbound)
             ];
             d3.select(".drawarea")
                 .attr("transform", "translate(" + translation + ")" +
                       " scale(" + scale + ")");
        }

        function reset() {
          svg.call(zoom
              .x(x.domain([-width / 2, width / 2]))
              .y(y.domain([-height / 2, height / 2]))
              .event);
        }

        function createCampaignLink(d) { return CRM.url('civicrm/a/#/campaign/' + d.id + '/tree', {}); }

        function update(source) {

          var nodes = tree.nodes(root).reverse(),
        	  links = tree.links(nodes);

          nodes.forEach(function(d) { d.y = d.depth * 100; });

          var node = svg.selectAll("g.node")
        	  .data(nodes, function(d) { return d.id || (d.id = ++i); });

          var nodeEnter = node.enter().append("g")
        	  .attr("class", "node")
        	  .attr("transform", function(d) {
        		  return "translate(" + d.x + "," + d.y + ")"; });

          nodeEnter.append("a")
           .attr("xlink:href", createCampaignLink)
           .append("circle")
        	  .attr("r", 15)
        	  .style("fill", "#fff");

          nodeEnter.append("a")
          .attr("xlink:href", createCampaignLink)
          .append("text")
        	  .attr("y", function(d) {
        		  return d.children || d._children ? -23 : 23; })
        	  .attr("dy", ".40em")
        	  .attr("text-anchor", "middle")
        	  .text(function(d) { return d.name; })
        	  .style("fill-opacity", 1);

          var link = svg.selectAll("path.link")
        	  .data(links, function(d) { return d.target.id; });

          link.enter().insert("path", "g")
        	  .attr("class", "link")
        	  .attr("d", diagonal);
        }

        update(root);
      }
    };
  });

})(angular, CRM.$, CRM._);
