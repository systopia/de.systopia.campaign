<?php
/*-------------------------------------------------------+
| CAMPAIGN MANAGER                                       |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Campaign_ExtensionUtil as E;

/**
 * Download for KPI data
 *
 * @todo permissions? formatting?
 */
class CRM_Campaign_Page_CampaignKPIExport extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Export KPI Data'));

    // parameters
    $campaign_id = CRM_Utils_Request::retrieve('campaign_id', 'Integer');
    $kpi_name = CRM_Utils_Request::retrieve('kpi_name', 'String');

    // get all KPIs (seems to be the easiest way)
    $kpis_json = CRM_Campaign_KPI::getCampaignKPI($campaign_id);
    $kpis = json_decode($kpis_json, 1);

    // some sanity checks
    if (!isset($kpis[$kpi_name])) {
      throw new CRM_Core_Exception(E::ts("KPI '%1' does not exist or has no data.", [1 => $kpi_name]));
    }
    if (empty($kpis[$kpi_name]['value']) || !is_array($kpis[$kpi_name]['value'])) {
      throw new CRM_Core_Exception(E::ts("KPI '%1' is only a single value and can not be exported.",
                                         [1 => $kpis[$kpi_name]['title']]));
    }

    // this seems fine, export to CSV
    //  based on https://stackoverflow.com/a/30533173
    $tmp_file = fopen('php://temp/maxmemory:8388608', 'w');
    if ($tmp_file === false) {
      throw new CRM_Core_Exception(E::ts("Failed to create temp file, KPI '%1' could not be exported.",
                                         [1 => $kpis[$kpi_name]['title']]));
    }

    // extract headers
    $values = $kpis[$kpi_name]['value'];
    $headers = array_keys(reset($values));
    fputcsv($tmp_file, $headers);

    // extract data
    foreach ($values as $value) {
      $row = [];
      foreach ($headers as $header) {
        $row[$header] = CRM_Utils_Array::value($header, $value, '');
      }
      fputcsv($tmp_file, $row);
    }

    // extract CSV data
    rewind($tmp_file);
    $csv_data = stream_get_contents($tmp_file);
    fclose($tmp_file);

    // generate file name
    if (!empty($kpis[$kpi_name]['title'])) {
      $file_name = $kpis[$kpi_name]['title'] . '.csv';
    } else {
      $file_name = $kpi_name . '.csv';
    }

    // run download
    CRM_Utils_System::download($file_name,'text/csv',$csv_data,'csv');

    // shouldn't get here:
    parent::run();
  }

}
