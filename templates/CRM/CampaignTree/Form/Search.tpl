{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{crmStyle ext=de.systopia.campaign file=campaigntree.css}

{if !$hasCampaigns}
<div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    &nbsp;
    {ts}None found.{/ts}
</div>
<div class="action-link">
    <a href="{crmURL p='civicrm/campaign/add' q='reset=1' h=0 }" class="button"><span><div
                    class="icon ui-icon-circle-plus"></div>{ts}Add Campaign{/ts}</span></a>
</div>
{else}
<div class="action-link">
    <a href="{crmURL p='civicrm/campaign/add' q='reset=1' h=0 }" class="button"><span><div
                    class="icon ui-icon-circle-plus"></div>{ts}Add Campaign{/ts}</span></a>
</div>
{* build search form here *}

{* Search form and results for campaigns *}
<div class="crm-block crm-form-block crm-campaign-search-form-block">
    <div id="{$searchForm}"
         class="crm-accordion-wrapper crm-campaign_search_form-accordion {if $force and !$buildSelector}collapsed{/if}">
        <div class="crm-accordion-header">
            {ts}Find Campaigns{/ts}
        </div>
        <!-- /.crm-accordion-header -->

        <div class="crm-accordion-body">

  <table class="form-layout">
    <tr>
      <td>
          {$form.title.label}<br />
          {$form.title.html}<br />
        <span class="description font-italic">
          {ts}Complete OR partial campaign name.{/ts}
      </span>
      </td>
      <td>
          {$form.description.label}<br />
          {$form.description.html}<br />
        <span class="description font-italic">
          {ts}Complete OR partial description.{/ts}
      </span>
      </td>
    </tr><tr>
      <td>
          {$form.start_date.label}<br />
          {include file="CRM/common/jcalendar.tpl" elementName=start_date}
      </td>
      <td>
          {$form.end_date.label}<br />
          {include file="CRM/common/jcalendar.tpl" elementName=end_date}
      </td>
      </tr><tr>
        <td id="campaign-show-block">
          {$form.show.label}<br />
          {$form.show.html}<br />
      </td>
      <td id="campaign-active-block">
          {$form.active.label}<br />
          {$form.active.html}<br />
      </td>
    </tr><tr>
      <td id="campaign_type-block">
          {$form.type_id.label}<br />
          {$form.type_id.html}<br />
        <span class="description font-italic">
          {ts}Filter search by campaign type.{/ts}
      </span>
      </td>
      <td id="campaign_status-block">
          {$form.status_id.label}<br />
          {$form.status_id.html}<br />
          <span class="description font-italic">
          {ts}Filter search by campaign status.{/ts}
          </span>
      </td>
    </tr><tr>
      <td>
          {$form.created_by.label}<br />
          {$form.created_by.html}<br />
          <span class="description font-italic">
          {ts}Complete OR partial creator name.{/ts}
          </span>
      </td>
      <td>
          {$form.external_id.label}<br />
          {$form.external_id.html}<br />
          <span class="description font-italic">
          </span>
      </td>
    </tr>
    <tr>
      <td>{$form.buttons.html}</td><td colspan="2">
    </tr>
  </table>
</div>
    </div>
</div>
<table class="crm-campaign-selector">
  <thead>
  <tr>
    <th class='crm-campaign-name'>{ts}Name{/ts}</th>
    <th class='crm-campaign-description'>{ts}Description{/ts}</th>
    <th class='crm-campaign-start_date'>{ts}Start Date{/ts}</th>
    <th class='crm-campaign-end_date'>{ts}End Date{/ts}</th>
    <th class='crm-campaign-campaign_type'>{ts}Type{/ts}</th>
    <th class='crm-campaign-status'>{ts}Status{/ts}</th>
    <th class='crm-campaign-created_by'>{ts}Created By{/ts}</th>
    <th class='crm-campaign-is_active'>{ts}Active?{/ts}</th>
    <th class='crm-campaign-campaign_links nosort'>&nbsp;</th>
    <th class='hiddenElement'>&nbsp;</th>
  </tr>
  </thead>
</table>
{/if} {* end of search form build *}

{* handle enable/disable actions*}
{include file="CRM/common/enableDisableApi.tpl"}

{literal}
<script type="text/javascript">
    CRM.$(function($) {
        // for CRM-11310 and CRM-10635 : processing just parent groups on initial display
        // passing '1' for parentsOnlyArg to show parent child heirarchy structure display
        // on initial load of manage group page and
        // also to handle search filtering for initial load of same page.
        buildCampaignSelector(true, 1);
        $('#_qf_Search_refresh').click( function() {
            buildCampaignSelector( true );
        });
        // Add livePage functionality
        $('#crm-container')
            .on('click', 'a.button, a.action-item[href*="action=update"], a.action-item[href*="action=delete"]', CRM.popup)
            .on('crmPopupFormSuccess', 'a.button, a.action-item[href*="action=update"], a.action-item[href*="action=delete"]', function() {
                // Refresh datatable when form completes
                var $context = $('#crm-main-content-wrapper');
                $('table.crm-campaign-selector', $context).dataTable().fnDraw();
            });

        function buildCampaignSelector( filterSearch, parentsOnlyArg ) {
            if ( filterSearch ) {
                if (typeof crmCampaignSelector !== 'undefined') {
                    crmCampaignSelector.fnDestroy();
                }
                var parentsOnly = 0;
                var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}No matching Campaigns found for your search criteria. Suggestions:{/ts}{literal}<div class="spacer"></div><ul><li>{/literal}{ts escape="js"}Check your spelling.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Try a different spelling or use fewer letters.{/ts}{literal}</li><li>{/literal}{ts escape="js"}Make sure you have enough privileges in the access control system.{/ts}{literal}</li></ul></div>';
            } else {
                var parentsOnly = 1;
                var ZeroRecordText = {/literal}'{ts escape="js"}<div class="status messages">No Campaigns have been created for this site.{/ts}</div>'{literal};
            }

            // this argument should only be used on initial display i.e onPageLoad
            if (typeof parentsOnlyArg !== 'undefined') {
                parentsOnly = parentsOnlyArg;
            }

            var columns = '';
            var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/campaign/list" h=0 q="snippet=4"}'{literal};
            var $context = $('#crm-main-content-wrapper');
            var campaignTypes = {/literal}{$campaignTypes}{literal};
            var campaignStatus = {/literal}{$campaignStatus}{literal};

            crmCampaignSelector = $('table.crm-campaign-selector', $context).dataTable({
                "bFilter"    : false,
                "bAutoWidth" : false,
                "aaSorting"  : [],
                "aoColumns"  : [
                    {sClass:'crm-campaign-name'},
                    {sClass:'crm-campaign-description'},
                    {sClass:'crm-campaign-start_date'},
                    {sClass:'crm-campaign-end_date'},
                    {sClass:'crm-campaign-type'},
                    {sClass:'crm-campaign-status'},
                    {sClass:'crm-campaign-created_by'},
                    {sClass:'crm-campaign-is_active'},
                    {sClass:'crm-campaign-action', bSortable: false},
                    {sClass:'hiddenElement', bSortable:false}
                ],
                "bProcessing": true,
                "asStripClasses" : [ "odd-row", "even-row" ],
                "sPaginationType": "full_numbers",
                "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
                "bServerSide": true,
                "bJQueryUI": true,
                "sAjaxSource": sourceUrl,
                "iDisplayLength": 25,
                "oLanguage": { "sZeroRecords":  ZeroRecordText,
                    "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
                    "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
                    "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
                    "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
                    "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
                    "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
                    "oPaginate": {
                        "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
                        "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
                        "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
                        "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
                    }
                },
                "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                    var id = $('td:last', nRow).text().split(',')[0];
                    var cl = $('td:last', nRow).text().split(',')[1];
                    $(nRow).addClass(cl).attr({id: 'row_' + id, 'data-id': id, 'data-entity': 'campaign'});

                    //handled disabled rows.
                    var isActive = Boolean(aData[7]);
                    if (!isActive) {
                        $(nRow).addClass('disabled');
                    }

                    if (parentsOnly) {
                        if ($(nRow).hasClass('crm-campaign-parent')) {
                            $(nRow).find('td:first').prepend('{/literal}<span class="collapsed show-children" title="{ts}show child campaigns{/ts}"/></span>{literal}');
                        }
                    }
                    return nRow;
                },
                "fnDrawCallback": function() {
                    // FIXME: trigger crmLoad and crmEditable would happen automatically
                    $('.crm-editable').crmEditable();
                },
                "fnServerData": function ( sSource, aoData, fnCallback ) {
                    aoData.push( {name:'parentsOnly', value: parentsOnly }
                    );
                    if ( filterSearch ) {

                        var showActive = '';
                        if ( $('.crm-campaign-search-form-block #active_1').prop('checked') ) {
                            showActive = '1';
                        }
                        if ( $('.crm-campaign-search-form-block #active_2').prop('checked') ) {
                            if ( showActive ) {
                                showActive = '3';
                            } else {
                                showActive = '2';
                            }
                        }

                        var show = 0;
                        if ( $('.crm-campaign-search-form-block #show_1').prop('checked') ) {
                            show += 1;
                        }
                        if ( $('.crm-campaign-search-form-block #show_2').prop('checked') ) {
                            show += 2;
                        }
                        if ( $('.crm-campaign-search-form-block #show_3').prop('checked') ) {
                            show += 4;
                        }
                        aoData.push(
                            {name:'title', value: $('.crm-campaign-search-form-block #title').val()},
                            {name:'description', value: $('.crm-campaign-search-form-block #description').val()},
                            {name:'start_date', value: $('.crm-campaign-search-form-block #start_date').val()},
                            {name:'end_date', value: $('.crm-campaign-search-form-block #end_date').val()},
                            {name:'show', value: show},
                            {name:'campaign_type', value: $('.crm-campaign-search-form-block #type_id').val()},
                            {name:'campaign_status', value: $('.crm-campaign-search-form-block #status_id').val()},
                            {name:'created_by', value: $('.crm-campaign-search-form-block #created_by').val()},
                            {name:'external_id', value: $('.crm-campaign-search-form-block #external_id').val()},
                            {name:'showActive', value: showActive}
                        );
                    }
                    $.ajax( {
                        "dataType": 'json',
                        "type": "POST",
                        "url": sSource,
                        "data": aoData,
                        "success": fnCallback
                    } );
                }
            });
        }

        // show hide children
        var $context = $('#crm-main-content-wrapper');
        $('table.crm-campaign-selector', $context).on( 'click', 'span.show-children', function(){
            var rowID = $(this).parents('tr').prop('id');
            var parentRow = rowID.split('_');
            var parent_id = parentRow[1];
            var campaign_id = '';
            if ( parentRow[2]) {
                campaign_id = parentRow[2];
            }
            var levelClass = 'level_2';
            // check enclosing td if already at level 2
            if ( $(this).parent().hasClass('level_2') ) {
                levelClass = 'level_3';
            }
            if ( $(this).hasClass('collapsed') ) {
                $(this).removeClass("collapsed").addClass("expanded").attr("title",{/literal}"{ts escape='js'}hide child campaigns{/ts}"{literal});
                showChildren( parent_id, campaign_id, levelClass );
            }
            else {
                $(this).removeClass("expanded").addClass("collapsed").attr("title",{/literal}"{ts escape='js'}show child campaigns{/ts}"{literal});
                $('.parent_is_' + parent_id).find('.show-children').removeClass("expanded").addClass("collapsed").attr("title",{/literal}"{ts escape='js'}show child campaigns{/ts}"{literal});
                $('.parent_is_' + parent_id).hide();
                $('.parent_is_' + parent_id).each(function(i, obj) {
                    // also hide children of children
                    var gID = $(this).find('td:nth-child(2)').text();
                    $('.parent_is_' + gID).hide();
                });
            }
        });
        function showChildren( parent_id, campaign_id, levelClass) {
            var rowID = '#row_' + parent_id;
            if ( campaign_id ) {
                rowID = '#row_' + parent_id + '_' + campaign_id;
            }
            if ( $(rowID).next().hasClass('parent_is_' + parent_id ) ) {
                // child rows for this parent have already been retrieved so just show them
                $('.parent_is_' + parent_id ).show();
            } else {
                var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/campaign/list" h=0 q="snippet=4"}'{literal};
                $.ajax( {
                    "dataType": 'json',
                    "url": sourceUrl,
                    "data": {'parent_id': parent_id},
                    "success": function(response){
                        var appendHTML = '';
                        $.each( response, function( i, val ) {
                            appendHTML += '<tr id="row_'+ val.id +'_'+parent_id+'" data-entity="campaign" data-id="'+ val.id +'" class="parent_is_' + parent_id + ' crm-row-child ' + val.class.split(',')[1] + '">';
                            if ( val.is_parent ) {
                                appendHTML += '<td class="crm-campaign-name ' + levelClass + '">' + '{/literal}<span class="collapsed show-children" title="{ts}show child campaigns{/ts}"/></span>{literal}' + val.name + '</td>';
                            }
                            else {
                                appendHTML += '<td class="crm-campaign-name ' + levelClass + '"><span class="crm-no-children"></span>' + val.name + '</td>';
                            }
                            appendHTML += '<td class="crm-editable crmf-description" data-type="textarea">' + (val.campaign_description || '') + "</td>";
                            appendHTML += '<td class="crm-campaign-start_date">' + val.start_date + "</td>";
                            appendHTML += '<td class="crm-campaign-start_date">' + val.end_date + "</td>";
                            appendHTML += '<td class="crm-campaign-type">' + val.type + "</td>";
                            appendHTML += '<td class="crm-campaign-status">' + val.status + "</td>";
                            appendHTML += '<td class="crm-campaign-created_by">' + val.created_by + "</td>";
                            appendHTML += '<td class="crm-campaign-is_active">' + val.is_active + "</td>";
                            appendHTML += "<td>" + val.links + "</td>";
                            appendHTML += "</tr>";
                        });
                        $( rowID ).after( appendHTML );
                        $( rowID ).next().trigger('crmLoad');

                    }
                });
            }
        }
    });

</script>
{/literal}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
