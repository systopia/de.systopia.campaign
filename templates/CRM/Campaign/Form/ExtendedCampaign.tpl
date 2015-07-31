<!--
/*#######################################################+
| de.systopia.campaign                                   |
| Copyright (C) 2015 SYSTOPIA                            |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+########################################################+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+########################################################*/
-->

<div id="pid_label_src">{$form.parent_id.label}</div>
<div id="pid_html_src">{$form.parent_id.html}</div>

{literal}
<script type="text/javascript">
   cj(function() {
      var target = cj('tr.crm-campaign-form-block-is_active').parent();

      target
       .prepend(cj('<tr>')
           .append(cj('<td>')
               .attr('id', 'pid_label')
               .addClass('label')
           )
           .append(cj('<td>')
               .attr('id', 'pid_html')
               .addClass('view-value')
           )
       );

      cj('#pid_label_src').appendTo('#pid_label');
      cj('#pid_html_src').appendTo('#pid_html');
   });
</script>
{/literal}
