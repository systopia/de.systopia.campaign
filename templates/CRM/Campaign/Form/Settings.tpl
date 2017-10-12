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

<h3>{ts domain="de.systopia.campaign"}Settings{/ts}</h3>
<div class="crm-section">
  <div class="label">{$form.cache.label}</div>
  <div class="content">{$form.cache.html}</div>
  <div class="clear"></div>
</div>

<h3>{ts domain="de.systopia.campaign"}Enable built-in KPIs{/ts}</h3>
<div>
  {foreach from=$kpis item=kpi key=name}
  <div class="crm-section">
    <div class="label"><code>{$name}</code></div>
    <div class="content">{$form.$name.html} {$form.$name.label}</div>
    <div class="clear"></div>
  </div>
  {/foreach}
</div>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
