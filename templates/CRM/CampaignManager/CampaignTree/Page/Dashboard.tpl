{*-------------------------------------------------------+
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
+-------------------------------------------------------*}


{* CiviCampaign DashBoard (launch page) *}

{if !empty($subPageType)}
    {* load campaign/survey/petition tab *}
    {if ($subPageType == 'Campaign')}
        {include file="CRM/CampaignManager/CampaignTree/Form/Search.tpl"}
    {else}
        {include file="CRM/Campaign/Form/Search/$subPageType.tpl"}
    {/if}
{else}
    {include file="CRM/common/TabHeader.tpl"}
    <div class="clear"></div>
{/if}

