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
