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
             return crmApi('CampaignTree', 'getids', {id: $route.current.params.id, depth: 0});
          },
          parents: function($route, crmApi) {
             return crmApi('CampaignTree', 'getparentids', {id: $route.current.params.id});
          },
          kpi: function($route, crmApi) {
             return crmApi('CampaignKpi', 'get', {id: $route.current.params.id});
          },
          expenseSum: function($route, crmApi) {
             return crmApi('CampaignExpense', 'getsum', {campaign_id: $route.current.params.id});
          },
          expenses: function($route, crmApi) {
             return crmApi('CampaignExpense', 'get', {campaign_id: $route.current.params.id});
          },
          actions: function($route, crmApi) {
             return crmApi('CampaignTree', 'getlinks', {id: $route.current.params.id});
          },
          customInfo: function($route, crmApi) {
            return crmApi('CampaignTree', 'getcustominfo', {entity_id: $route.current.params.id});
          }
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

      $routeProvider.when('/campaign/:id/expense/add', {
        templateUrl: resourceUrl + '/partials/campaign_expense.html',
        controller: 'CampaignExpenseCtrl'
      });

      $routeProvider.when('/campaign/:id/clone', {
         templateUrl: resourceUrl + '/partials/campaign_copy.html',
         controller: 'CampaignCloneCtrl',
         resolve: {
          currentCampaign: function($route, crmApi) {
            return crmApi('Campaign', 'getsingle', {id: $route.current.params.id});
          }
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
  'kpi',
  'expenseSum',
  'expenses',
  'actions',
  'customInfo',
  'dialogService',
  'crmApi',
  '$interval',
   function($scope, $routeParams, $sce, currentCampaign, children, parents, kpi, expenseSum, expenses, actions, customInfo, dialogService, crmApi, $interval) {
     $scope.ts = CRM.ts('de.systopia.campaign');
     $scope.currentCampaign = currentCampaign;
     $scope.currentCampaign.goal_general_htmlSafe = $sce.trustAsHtml($scope.currentCampaign.goal_general);
     $scope.currentCampaign.start_date_date = $scope.currentCampaign.start_date ? CRM.$.datepicker.formatDate(CRM.config.dateInputFormat, new Date($scope.currentCampaign.start_date.replace(/-/g, ' '))) : 'none';
     $scope.currentCampaign.end_date_date = $scope.currentCampaign.end_date ? CRM.$.datepicker.formatDate(CRM.config.dateInputFormat, new Date($scope.currentCampaign.end_date.replace(/-/g, ' '))) : 'none';
     $scope.children = children.children;
     $scope.kpi = JSON.parse(kpi.result);
     $scope.parents = parents.parents.reverse();
     $scope.expenseSum = expenseSum.values;
     $scope.expenses = [];
     $scope.actions = JSON.parse(actions.result);
     $scope.customInfo = JSON.parse(customInfo);

     crmApi('OptionValue', 'get', {"option_group_id": "campaign_status", "return": "value,label"}).then(function (apiResult) {
       $scope.campaign_status = apiResult.values;

       angular.forEach($scope.campaign_status, function(item) {
          if(item.value == $scope.currentCampaign.status_id)
          $scope.currentCampaign.status_id_text = item.label;
       });
     });
     $scope.expense_sum = 0.00;
     angular.forEach(expenses.values, function(item) {
       $scope.expense_sum = $scope.expense_sum + parseFloat(item.amount);
       $scope.expenses.push(item);
     });

     $scope.numberof = {
        parents: Object.keys($scope.parents).length,
        children: Object.keys($scope.children).length,
        };
     $scope.tree_link = CRM.url('civicrm/a/#/campaign/' + $scope.currentCampaign.id + '/tree', {});
     $scope.subcampaign_link = CRM.url('civicrm/campaign/add', {reset: 1, pid: $scope.currentCampaign.id});
     $scope.edit_link = CRM.url('civicrm/campaign/add', {reset: 1, id: $scope.currentCampaign.id, action: 'update'});
     $scope.add_link = CRM.url('civicrm/a/#/campaign/' + $scope.currentCampaign.id + '/expense/add', {});
     $scope.clone_link = CRM.url('civicrm/a/#/campaign/' + $scope.currentCampaign.id + '/clone', {});
     $scope.btd_link = CRM.url('civicrm/campaign', {reset: 1});

     $scope.predicate = 'amount';
     $scope.reverse = true;
     $scope.order = function(predicate) {
       $scope.reverse = ($scope.predicate === predicate) ? !$scope.reverse : false;
       $scope.predicate = predicate;
     };

     $scope.updateKpiAndExpenses = function() {
       crmApi('CampaignExpense', 'get', {campaign_id: $scope.currentCampaign.id}).then(function (apiResult) {
         $scope.expenses = apiResult.values;
       }, function(apiResult) {
         CRM.alert(apiResult.error_message, ts('Error while fetching expenses'), "error");
       });
       crmApi('CampaignKpi', 'get', {id: $scope.currentCampaign.id}).then(function (apiResult) {
         $scope.kpi = JSON.parse(apiResult.result);
       }, function(apiResult) {
         CRM.alert(apiResult.error_message, ts('Error while fetching expenses'), "error");
       });
    };

     $scope.deleteExpense = function(expense) {
       crmApi('CampaignExpense', 'delete', {id: expense.id}).then(function (apiResult) {
         $scope.updateKpiAndExpenses();
         CRM.alert(ts('Successfully removed expense %1',  {1: expense.description}), ts('Expense deleted'), "success");
       }, function(apiResult) {
         CRM.alert(apiResult.error_message, ts('Could not delete expense'), "error");
       });
    };

     $scope.addExpense = function() {
       var model = {
         campaign_id: $scope.currentCampaign.id,
         is_new_expense: true
        };
        var options = CRM.utils.adjustDialogDefaults({
          width: '40%',
          height: 'auto',
          autoOpen: false,
          title: ts('Add Expense')
        });
        dialogService.open('addExpenseDialog', resourceUrl + '/partials/campaign_expense.html', model, options).then(function (result) {
          $scope.updateKpiAndExpenses();
        });
     };

     $scope.editExpense = function(exp) {
       var model = {
         campaign_id: $scope.currentCampaign.id,
         amount: exp.amount,
         description: exp.description,
         transaction_date: exp.transaction_date,
         expense_type_id: exp.expense_type_id,
         id: exp.id,
         is_new_expense: false
        };
        var options = CRM.utils.adjustDialogDefaults({
          width: '40%',
          height: 'auto',
          autoOpen: false,
          title: ts('Edit Expense')
        });
        dialogService.open('addExpenseDialog', resourceUrl + '/partials/campaign_expense.html', model, options).then(function (result) {
          $scope.updateKpiAndExpenses();
        });
     };

     $scope.applyToChildren = function(property, value) {
        for(var child_campaign_id in $scope.children) {
           var val = {id: child_campaign_id};
           val[property] = value;
           crmApi('Campaign', 'create', val);
        }
     };

  }]);

  campaign.controller('CampaignExpenseCtrl', ['$scope', '$routeParams','dialogService', 'crmApi',
  function($scope, $routeParams, dialogService, crmApi) {
    $scope.ts = CRM.ts('de.systopia.campaign');
    crmApi('OptionValue', 'get', {"option_group_id": "campaign_expense_types"}).then(function (apiResult) {
      $scope.categories = apiResult.values;
    });
    crmApi('Setting', 'getsingle', {"return": "defaultCurrency"}).then(function (apiResult) {
      $scope.defaultCurrency = apiResult["defaultCurrency"];
    });
    $scope.submit = function() {
      if($scope.model.is_new_expense && $scope.addExpenseForm.$invalid) {
        return;
      }
      crmApi('CampaignExpense', 'create', $scope.model).then(function (apiResult) {
        var expense = apiResult.values[apiResult.id];
        CRM.alert(ts('Successfully added expense %1',  {1: expense.description}), ts('Expense added'), "success");
        dialogService.close('addExpenseDialog', expense);
      }, function(apiResult) {
        CRM.alert(apiResult.error_message, ts('Could not add expense'), "error");
      });
   };
  }]);

  campaign.filter("filterCustomInfo", function(){
   return function(items){
     var filtered = [];
     for (var item in items) {
       if (items.hasOwnProperty(item)) {
         var current_item = items[item];
         // skip array values (can't be displayed)
         if(Array.isArray(current_item.value)) {
           continue;
         }
         filtered.push(current_item);
       }
     }
     return filtered;
    }
  });

  campaign.filter("preFilterKPI", function(){
   return function(items){
     var filtered = [];
     for (var item in items) {
       if (items.hasOwnProperty(item)) {
         var current_item = items[item];
         // skip array values (can't be displayed)
         if(Array.isArray(current_item.value)) {
           continue;
         }
         switch (current_item.kpi_type) {
           case "money":
           case "percentage":
           case "date":
           case "number":
            filtered.push(current_item);
            break;
           default:
         }
       }
     }
     return filtered;
    }
  });

  campaign.filter("formatKPI", function(currencyFilter, numberFilter){
   return function(input){
      // skip array values (can't be displayed)
      if(Array.isArray(input.value)) {
        return "-";
      }
      switch (input.kpi_type) {
        case "date":
          // TODO: how to format date
          return input.value;
        case "money":
          return CRM.formatMoney(input.value);
        case "percentage":
          return (input.value == -1 ? "-" : numberFilter(input.value * 100, 2) + "%");
        case "number":
          return numberFilter(input.value);
        default:
          return "-";
      }
   }
  });

  campaign.filter('formatMoney', function() {
    return function(input, onlyNumber, format) {
      return CRM.formatMoney(input, onlyNumber, format);
    };
  });

  campaign.filter("filterKPI", function(){
   return function(items){
    var filtered = [];
    for (var item in items) {
      if (items.hasOwnProperty(item)) {
        var current_item = items[item];
        if(!"vis_type" in current_item) {
          continue;
        }
        switch (current_item.vis_type) {
          case "":
          case "none":
          case "table":
            continue;
          default:
            filtered.push(current_item);
        }
      }
    }
    return filtered;
   }
  });

  campaign.filter("filterTableKPI", function(){
   return function(items){
    var filtered = [];
    for (var item in items) {
      if (items.hasOwnProperty(item)) {
        var current_item = items[item];
        if(!"vis_type" in current_item) {
          continue;
        }
        switch (current_item.vis_type) {
          case "":
          case "none":
            continue;
          case "table":
            filtered.push(current_item);
        }
      }
    }
    return filtered;
   }
  });

  campaign.directive("kpivisualization", function($window) {
    return {
      template: '<ng-include src="getTemplateUrl()"/>',
      scope: {
          kpi: '=kpi'
      },
      restrict: 'E',
      controller: function($scope) {
        $scope.chartdata = $scope.kpi;
        //function used on the ng-include to resolve the template
        $scope.getTemplateUrl = function() {
          return resourceUrl + '/partials/kpi_' + $scope.kpi.vis_type + '.html';
        }
      }
    };
  });

  campaign.directive("piechart", function($window) {
    return {
      scope: {
          chartdata: '=chartdata'
      },
      restrict: 'E',
      link: function(scope, elem, attrs){
        var chartdata=scope[attrs.chartdata];
        var d3 = $window.d3;

        var w = 300;
        var h = 300;
        var r = h/2;
        var color = d3.scale.category20c();

        var data = chartdata.value;

        angular.forEach(data, function(d, i) {
          if(typeof(d.label) === 'undefined' || typeof(d.value) === 'undefined' || d.value === false) {
            delete data[i];
            data.length = data.length - 1;
          }
        });

        if(CRM._.isEmpty(data)) {
          data.push({label: ts('No Data'), value: 100});
        }

        var vis = d3.select(elem[0]).append("svg:svg").data([data]).attr("width", w).attr("height", h).append("svg:g").attr("transform", "translate(" + r + "," + r + ")");
        var pie = d3.layout.pie().value(function(d){return d.value;});
        var arc = d3.svg.arc().outerRadius(r);

        var enter = vis.selectAll("g.slice").data(pie).enter();
        var arcs  = enter.insert("svg:g",":first-child").attr("class", "slice");
        arcs.append("svg:path")
            .attr("fill", function(d, i){
                return color(i);
            })
            .attr("d", function (d) {
                return arc(d);
            });

        // add label
        var labels = enter.append("svg:g").attr("class", "slice");
        labels.append("svg:text").attr("transform", function(d){
        			d.innerRadius = 0;
        			d.outerRadius = r;
            return "translate(" + arc.centroid(d) + ")";})
                   .attr("text-anchor", "middle")
                   .text( function(d, i) {return data[i].label;} );
      }
    }
  });

  campaign.directive("linegraph", function($window) {
    return {
      scope: {
          chartdata: '=chartdata'
      },
      restrict: 'E',
      link: function(scope, elem, attrs){
        var chartdata=scope[attrs.chartdata];
        var d3 = $window.d3;

        var margin = {top: 30, right: 20, bottom: 30, left: 50},
            width  = 600 - margin.left - margin.right,
            height = 300 - margin.top  - margin.bottom;

        var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;

        var x = d3.time.scale().range([0, width]);
        var y = d3.scale.linear().range([height, 0]);

        var xAxis = d3.svg.axis()
                      .scale(x)
                      .orient("bottom")
                      .ticks(5);

        var yAxis = d3.svg.axis().scale(y)
            .orient("left").ticks(5);

        var valueline = d3.svg.line()
            .x(function(d) { return x(d.date); })
            .y(function(d) { return y(d.value); })
            .interpolate("basis");

        var vis = d3.select(elem[0]).append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
            .append("g")
                .attr("transform",
                      "translate(" + margin.left + "," + margin.top + ")");
        var data = chartdata.value;

        angular.forEach(data, function(d, i) {
          if(typeof(d.date) === 'undefined' || typeof(d.value) === 'undefined') {
            delete data[i];
            data.length = data.length - 1;
          }
        });


          data.forEach(function(d) {
              d.date = parseDate(d.date);
              d.value = +d.value;
          });

          x.domain(d3.extent(data, function(d) { return d.date; }))
                  .ticks(d3.time.day);
          y.domain([0, d3.max(data, function(d) { return d.value; })]);

          var newData = x.ticks(d3.time.day)
               .map(function(day) {
                   return _.find(data,
                       { date: day }) ||
                       { date: day, value: 0 };
                });


          vis.append("path")
             .attr("class", "line")
             .attr("d", valueline(newData));

          vis.append("g")
             .attr("class", "x axis")
             .attr("transform", "translate(0," + height + ")")
             .call(xAxis)

          vis.append("g")
             .attr("class", "y axis")
             .call(yAxis);

          if(CRM._.isEmpty(data)) {
            vis.append("text")
                  .attr("x", width/2-20)
                  .attr("y", height/2)
                  .text(ts('No Data'));
          }

      }
    }
  });

  campaign.directive('titlechanger',function() {
        return {
            restrict : 'C',
            link : function postLink(scope, elem, attr) {
                elem.ready(function(){
                   var new_title = scope.currentCampaign.is_active === "1" ? scope.currentCampaign.title  : scope.currentCampaign.title + " " + ts("(INACTIVE)");
                   	$('h1.with-tabs').text(new_title);
			$('#page-title').text(new_title);
                });
            }
        }
    });

  campaign.controller('CampaignCloneCtrl', ['$scope', '$routeParams', 'crmApi', 'currentCampaign',
  function($scope, $routeParams, crmApi, currentCampaign) {
    $scope.ts = CRM.ts('de.systopia.campaign');
    $scope.currentCampaign = currentCampaign;
    $scope.campaign_link = CRM.url('civicrm/a/#/campaign/' + $scope.currentCampaign.id + '/view', {});

    $scope.form_model = {
      id: $scope.currentCampaign.id,
      depth: 0,
      titleSearch: "/2015/",
      titleReplace: "2016",
      startDateOffset: "+1 day",
      endDateOffset: "+1 day"
    };

    $scope.cloneCampaign = function() {
      crmApi('CampaignTree', 'clone', $scope.form_model).then(function (apiResult) {
        CRM.alert(ts('Successfully cloned campaign'), ts('Campaign cloned'), "success");
      }, function(apiResult) {
        CRM.alert(apiResult.error_message, ts('Could not clone campaign'), "error");
      });
    };

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

    $scope.getTemplateUrl = function() {
      return resourceUrl + '/partials/tree_help_text.html';
    }

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

        var margin = {top: 100, right: 50, bottom: 100, left: 50},
        width  = 960 - margin.left - margin.right,
        height = 500 - margin.top  - margin.bottom;

        var center = [width / 2, height / 2];

        var d3 = $window.d3;
        var rawSvg = elem.find("svg")[0];

        var svg = d3.select(rawSvg)
        .attr("style", "outline: thin dashed black;")
        .attr("width", width + margin.right + margin.left)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("class","drawarea")
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        var nodes;
        var node;
        var link;
        var dragInitiated = false;

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

         var selectedNode = null, subNodes = null, parentLink = null;

         function hideSubtree(node, depth) {
           if(depth > 1 && node.children) {
             node.children.forEach(function(child) {
                  // hide node
                  var dom_node = d3.select('#node_' + child.id);
                  dom_node.style("visibility", "hidden");
                  // hide link
                  var dom_path =  d3.select('#p_' + child.parentid + '_' + child.id);
                  dom_path.style("visibility", "hidden");

                  hideSubtree(child, depth-1);
             });
           }
         }

         var drag = d3.behavior.drag()
         .on("dragstart", function(c) {
            if (d3.event.sourceEvent.button == 0) {
              dragInitiated = true;
            }
            if(c != root && dragInitiated) {
               selectedNode = c;
               selectedNode.startx = selectedNode.x;
               selectedNode.x0 = selectedNode.x;
               selectedNode.starty = selectedNode.y;
               selectedNode.y0 = selectedNode.y;
               hideSubtree(c, 99);
               subNodes = tree.nodes(c);
               subNodes.splice(0,1);
               parentLink = d3.select('#p_' + c.parentid + '_' + c.id);
               parentLink.style("visibility", "hidden");
               d3.event.sourceEvent.stopPropagation();
            }
         })
         .on("drag", function(d) {

            if(selectedNode) {
               var cnode = d3.select('#node_' + selectedNode.id);
               var t = d3.transform(cnode.attr("transform"));
               nt = {'x': t.translate[0] + d3.event.x,
                         'y': t.translate[1] + d3.event.y};

               selectedNode.x = nt.x;
               selectedNode.y = nt.y;

               cnode.attr("transform", "translate(" + nt.x + "," + nt.y + ")");
            }

         })
         .on("dragend", function(c) {
           if (d3.event.sourceEvent.button == 0) {
             dragInitiated = false;

             var nodeTarget = null;
             nodes.forEach(function(nd) {
                  if(nd.id != selectedNode.id) {
                     // check visibility
                     var dom_node = d3.select('#node_' + nd.id);
                     var isVisible = (dom_node.style("visibility") != "hidden");
                     if(isVisible) {
                       // Check we didn't just click the node, if vertically below root node this sets y=0
                       //   for selectedNode which triggers a connection to root node. To actually connect root node
                       //   you must be off by 1 pixel (in x or y) with this workaround.
                       if(!((selectedNode.y == 0) && (selectedNode.x == nd.x))) {
                         //get distance
                         var xdist = Math.abs(selectedNode.x - nd.x);
                         var ydist = Math.abs(selectedNode.y - nd.y);
                         var dist = Math.sqrt((xdist * xdist) + (ydist * ydist));

                         if (dist <= 30) {
                           nodeTarget = nd;
                         }
                       }
                     }

                }
             });

             d3.selectAll(".node").select("circle").style("stroke", null);
             d3.selectAll(".node").style("visibility", null);
             d3.selectAll(".link").style("visibility", null);

             parentLink.style("visibility", null);

             if(nodeTarget) {

                CRM.api3('CampaignTree', 'setnodeparent', {
                  "sequential": 1,
                  "id": selectedNode.id,
                  "parentid": nodeTarget.id
               });

                var index = selectedNode.parent.children.indexOf(selectedNode);
                if (index > -1) {
                   selectedNode.parent.children.splice(index, 1);
                }
                if (typeof nodeTarget.children !== 'undefined' || typeof nodeTarget._children !== 'undefined') {
                   if (typeof nodeTarget.children !== 'undefined') {
                      nodeTarget.children.push(selectedNode);
                   } else {
                      nodeTarget._children.push(selectedNode);
                   }
                } else {
                   nodeTarget.children = [];
                   nodeTarget.children.push(selectedNode);
                }
                init();
                update();
             }else{
                var cnode = d3.select('#node_' + selectedNode.id);
                selectedNode.x = selectedNode.startx;
                selectedNode.y = selectedNode.starty;

                cnode.attr("transform", "translate(" + selectedNode.startx + "," + selectedNode.starty + ")");
             }

             selectedNode = null;
           }
         });


        d3.select("svg")
        .call(zoom);

        var resetBtn = d3.select("#tree_container #resetBtn");
        resetBtn.on("click", reset);

        var i = 0;

        var tree = d3.layout.tree()
        	.size([width, height]);

        var diagonal = d3.svg.diagonal()
          .source(function(d) { return {"x":d.source.x, "y":d.source.y}; })
          .target(function(d) { return {"x":d.target.x, "y":d.target.y}; })
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
           init();
           update();
        }

        function createCampaignLink(d) { return CRM.url('civicrm/a/#/campaign/' + d.id + '/tree', {}); }
        function createSubcampaignLink(d) { return CRM.url('civicrm/campaign/add', {pid: d.id}); }

        function init() {
          nodes = tree.nodes(root).reverse();
          nodes.forEach(function(d) { d.y = d.depth * 100; });
        }

        function update(source) {
           node = svg.selectAll("g.node")
        	  .data(nodes, function(d) { return d.id || (d.id = ++i); });

          link = svg.selectAll("path.link")
                .data(tree.links(nodes), function(d) { return d.target.id; });

          node.attr("transform", function(d) {
            return "translate(" + d.x + "," + d.y + ")"; });

          var nodeEnter = node.enter().append("g")
        	  .attr("class", "node")
            .attr("id", function(d) {return "node_" + d.id;})
        	  .attr("transform", function(d) {
        		  return "translate(" + d.x + "," + d.y + ")"; });

         var menu = [
                   {
                       title: ts('View Campaign'),
                       action: function(elm, d, i) {
                           window.open(CRM.url('civicrm/a/#/campaign/' + d.id + '/view', {}), '_self');
                       }
                   },
                   {
                       title: ts('Edit Campaign'),
                       action: function(elm, d, i) {
                          window.open(CRM.url('civicrm/campaign/add', {reset: 1, id: d.id, action: 'update'}), '_blank');
                       }
                   },
                   {
                       title: ts('Create Subcampaign'),
                       action: function(elm, d, i) {
                         window.open(createSubcampaignLink(d));
                       }
                   }
         ];

          nodeEnter.append("a")
           .attr("xlink:href", createCampaignLink)
           .append("circle")
        	   .attr("r", 15)
        	   .style("fill", "#fff")
             .on('contextmenu', d3.contextMenu(menu))
             .call(drag);

          nodeEnter.append("a")
          .attr("xlink:href", createCampaignLink)
          .append("text")
        	  .attr("y", function(d) {
        		  return d.children || d._children ? -23 : 23; })
        	  .attr("dy", ".40em")
        	  .attr("text-anchor", "middle")
        	  .text(function(d) { return d.name; })
        	  .style("fill-opacity", 1);


          link.attr("d", diagonal);

          link.enter().insert("path", "g")
        	  .attr("class", "link")
            .style("stroke", "#aaa")
            .attr("id", function(d) { return "p_" + d.source.id + "_" + d.target.id;})
        	  .attr("d", diagonal);

          link.exit().remove();
        }

        init();
        update(root);
      }
    };
  });

})(angular, CRM.$, CRM._);
