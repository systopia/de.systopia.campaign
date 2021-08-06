/*-------------------------------------------------------+
| CAMPAIGN MANAGER                                       |
| Copyright (C) 2015-2017 SYSTOPIA                       |
| Author: N. Bochan                                      |
|         B. Endres (endres@systopia.de)                 |
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
  CRM.loadScript(resourceUrl + '/js/lib/d3-context-menu.js');
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
          expenseSum: function($route, crmApi) {
             return crmApi('CampaignExpense', 'getsum', {campaign_id: $route.current.params.id});
          },
          expenses: function($route, crmApi) {
             return crmApi('CampaignExpense', 'get', {campaign_id: $route.current.params.id, 'options': {'limit' : 0}});
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
  'expenseSum',
  'expenses',
  'actions',
  'customInfo',
  'dialogService',
  'crmApi',
  '$interval',
   function($scope, $routeParams, $sce, currentCampaign, children, parents, expenseSum, expenses, actions, customInfo, dialogService, crmApi, $interval) {
     $scope.ts = CRM.ts('de.systopia.campaign');
     $scope.currentCampaign = currentCampaign;
     $scope.currentCampaign.goal_general_htmlSafe = $sce.trustAsHtml($scope.currentCampaign.goal_general);
     $scope.currentCampaign.start_date_date = $scope.currentCampaign.start_date ? CRM.$.datepicker.formatDate(CRM.config.dateInputFormat, new Date($scope.currentCampaign.start_date.replace(/-/g, ' '))) : 'none';
     $scope.currentCampaign.end_date_date = $scope.currentCampaign.end_date ? CRM.$.datepicker.formatDate(CRM.config.dateInputFormat, new Date($scope.currentCampaign.end_date.replace(/-/g, ' '))) : 'none';
     $scope.children = children.children;
     $scope.parents = parents.parents.reverse();
     $scope.expenseSum = expenseSum.values;
     $scope.expenses = [];
     $scope.actions = JSON.parse(actions.result);
     $scope.customInfo = customInfo;

     // load KPIs asynchronously (see #46)
     crmApi('CampaignKpi', 'get', {id: $scope.currentCampaign.id}).then(function(apiResult) {
       $scope.kpi = JSON.parse(apiResult.result);
     });

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
       crmApi('CampaignExpense', 'get', {campaign_id: $scope.currentCampaign.id, 'options': {'limit' : 0}}).then(function (apiResult) {
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

        var width = 600;
        var height = 300;
        var radius = Math.min(width, height) / 2;
        var labelWidth = (width - Math.min(width, height)) / 2;
        var color = d3.scale.category20c();

        var data = chartdata.value;
        // Sort by value for coherent rendering.
        data.sort((a, b) => (a.value > b.value) ? 1 : -1);

        angular.forEach(data, function(d, i) {
          if(typeof(d.label) === 'undefined' || typeof(d.value) === 'undefined' || d.value === false) {
            delete data[i];
            data.length = data.length - 1;
          }
        });

        if(CRM._.isEmpty(data)) {
          data.push({label: ts('No Data'), value: 100});
        }

        var svg = d3.select(elem[0])
          .append("svg:svg")
          .style('width', width)
          .style('height', height)
          .append("g");

        svg.append("g")
          .attr("class", "slices");
        svg.append("g")
          .attr("class", "labels");
        svg.append("g")
          .attr("class", "lines");

        var pie = d3.layout.pie()
          .sort(null)
          .value(function(d) {
            return d.value;
          });

        var arc = d3.svg.arc()
          .outerRadius(radius * 0.8)
          .innerRadius(radius * 0.4);

        var outerArc = d3.svg.arc()
          .innerRadius(radius * 0.9)
          .outerRadius(radius * 0.9);

        svg.attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

        var key = function(d){ return d.data.label; };

        /* ------- PIE SLICES -------*/
        function toggleLabel(d, i) {
          var label = svg.select('.labels text[data-index="' + i + '"]')
          var line = svg.select('.lines polyline[data-index="' + i + '"]')
          if (label.classed('hide')) {
            label.classed('hidden', !label.classed('hidden'));
          }
          if (line.classed('hide')) {
            line.classed('hidden', !line.classed('hidden'));
          }
        }

        var slice = svg.select(".slices").selectAll("path.slice")
          .data(pie(data), key);

        slice.enter()
          .insert("path")
          .style("fill", function(d) { return color(d.data.label); })
          .attr("class", "slice")
          .attr('d', arc)
          .on('mouseover', toggleLabel)
          .on('mouseout', toggleLabel)
          .on('touchstart', toggleLabel)
          .on('touchend', toggleLabel);

        slice.exit()
          .remove();

        /* ------- TEXT LABELS -------*/

        function midAngle(d){
          return d.startAngle + (d.endAngle - d.startAngle)/2;
        }

        var text = svg.select(".labels").selectAll("text")
          .data(pie(data), key);

        text.enter()
          .append("text")
          .text(function(d) {
            return d.data.label;
          })
          .attr('transform', function(d) {
            var pos = outerArc.centroid(d);
            pos[0] = radius * (midAngle(d) < Math.PI ? 1 : -1);
            return "translate("+ pos +")";
          })
          .style('text-anchor', function(d) {
            return midAngle(d) < Math.PI ? "start":"end";
          })
          .attr('data-index', function(d, i) {
            return i;
          })
          .classed('hidden hide', function(d) {
            return d.data.value < 0.1;
          })
          .each(function(d, i) {
            svg_textMultiline(this, labelWidth);
          })
          .attr('y', function() {
            return -(this.getBBox().height / 2);
          });

        text.exit()
          .remove();

        /* ------- SLICE TO TEXT POLYLINES -------*/

        var polyline = svg.select(".lines").selectAll("polyline")
          .data(pie(data), key);

        polyline.enter()
          .append("polyline");

        polyline
          .attr('points', function(d) {
            var pos = outerArc.centroid(d);
            pos[0] = radius * 0.95 * (midAngle(d) < Math.PI ? 1 : -1);
            return [arc.centroid(d), outerArc.centroid(d), pos];
          })
          .attr('data-index', function(d, i) {
            return i;
          })
          .classed('hidden hide', function(d) {
            return d.data.value < 0.1;
          });

        polyline.exit()
          .remove();
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

        var color = d3.scale.category20c();

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


        data.forEach(function (d) {
          d.date = parseDate(d.date);
          if (d.start_date) {
            d.start_date = parseDate(d.start_date);
          }
          if (d.end_date) {
            d.end_date = parseDate(d.end_date);
          }
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


        var num_campaigns = data.reduce(function(count, entry) {
          return count + (entry.type == 'campaign_range' ? 1 : 0);
        }, 0);

          var spanH = 23;
          var height = num_campaigns * spanH;

        var bands_y = d3.scale.linear().range([height, 0]),
          bands_x = x;

        bands_y.domain([
          d3.min(data, function(d) { return d.pos; }),
          d3.max(data, function(d) { return d.pos; })
        ]);

        var spanX = function (d, i) {
            return bands_x(d['start_date']);
          },
          spanY = function (d) {
            return bands_y(d.pos);
          },
          spanW = function (d, i) {
            return bands_x(d['end_date']) - bands_x(d['start_date']);
          };

        var wrappedLabel = function(d) {
          // Cut the label at a character count approximated based on the
          // font-size (13px) and the chart width.
          var label = d.campaign.substr(0, width / 13 * 2);
          return (d.campaign.length > label.length ? label.concat('…') : label);
        };

        var bands_svg = d3.select(elem[0]).append("svg")
          .attr('width', width + margin.left + margin.right)
          .attr('height', height + margin.top + margin.bottom)
          .append('g')
          .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        bands_svg.append("g")
          .attr("class", "bands");
        bands_svg.append("g")
          .attr("class", "labels")
          .attr('transform', 'translate(-' + margin.left + ', 0)');

        // Add spans
        var span = bands_svg.select(".bands").selectAll('.chart-span')
          .data(data.filter(function(dataPoint) {
            return dataPoint['type'] == 'campaign_range';
          }))
          .enter()
          .append('rect')
          .attr('title', function(d) { return d.campaign })
          .style("fill", function(d) { return color(d.campaign); })
          .classed('chart-span', true)
          .attr('x', spanX)
          .attr('y', spanY)
          .attr('width', spanW)
          .attr('height', spanH);

        bands_svg.select(".labels").selectAll("text.stroke")
          .data(data.filter(function(dataPoint) {
            return dataPoint['type'] == 'campaign_range';
          }))
          .enter()
          .append("text")
          .text(wrappedLabel)
          .attr('x', '50%')
          .attr('y', spanY)
          .attr('dy', spanH / 2)
          .attr('height', spanH)
          .attr('stroke', 'white')
          .attr('stroke-width', 4)
          .style('text-anchor', 'middle')
          .style('dominant-baseline', 'middle');

        bands_svg.select(".labels").selectAll("text.label")
          .data(data.filter(function(dataPoint) {
            return dataPoint['type'] == 'campaign_range';
          }))
          .enter()
          .append("text")
          .text(wrappedLabel)
          .attr('x', '50%')
          .attr('y', spanY)
          .attr('dy', spanH / 2)
          .attr('height', spanH)
          .attr('fill', 'black')
          .style('text-anchor', 'middle')
          .style('dominant-baseline', 'middle');
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

  /**
   * @url https://stackoverflow.com/a/38162224
   *
   * @param element
   *   The SVG TEXT element to wrap.
   * @param width
   *   The desired width of the wrapped text.
   */
  function svg_textMultiline(element, width) {
    var y = '1.15em';

    /* get the text */
    var text = element.innerHTML;

    /* split the words into array */
    var words = text.split(' ');
    var line = '';

    /* Make a tspan for testing */
    element.innerHTML = '<tspan id="PROCESSING">busy</tspan >';

    for (var n = 0; n < words.length; n++) {
      var testLine = line + words[n] + ' ';
      var testElem = document.getElementById('PROCESSING');
      /*  Add line in testElement */
      testElem.innerHTML = testLine;
      /* Messure textElement */
      var metrics = testElem.getBoundingClientRect();
      testWidth = metrics.width;

      if (testWidth > width && n > 0) {
        element.innerHTML += '<tspan x="0" dy="' + y + '">' + line + '</tspan>';
        line = words[n] + ' ';
      } else {
        line = testLine;
      }
    }

    element.innerHTML += '<tspan x="0" dy="' + y + '">' + line + '</tspan>';

    document.getElementById("PROCESSING").remove();
  }

})(angular, CRM.$, CRM._);
