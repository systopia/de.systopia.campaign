DROP TABLE IF EXISTS civicrm_campaign_config_activity_type;
CREATE TABLE civicrm_campaign_config_activity_type (
  activity_type_id INT UNSIGNED PRIMARY KEY COMMENT 'Activity type which will be displayed',
  is_fixed INT UNSIGNED DEFAULT 0 COMMENT '0 : show only when data exists, 1 : show always'
);

DROP TABLE IF EXISTS civicrm_campaign_config_activity_status;
CREATE TABLE civicrm_campaign_config_activity_status (
  activity_type_id INT UNSIGNED COMMENT '',
  status_id INT UNSIGNED COMMENT 'Status which you want to display',
  grouping VARCHAR(64) NOT NULL COMMENT 'Statuses can by grouped by label. By default grouping is equal label of status',
  PRIMARY KEY (activity_type_id, status_id),
  KEY civicrm_campaign_config_activity_status_label_ind (grouping)
);

DROP TABLE IF EXISTS civicrm_campaign_config_status_sequence;
CREATE TABLE civicrm_campaign_config_status_sequence (
  grouping VARCHAR(64) PRIMARY KEY ,
  sequence INT UNSIGNED NOT NULL
);

-- insert all current types
INSERT INTO civicrm_campaign_config_activity_type (activity_type_id)
  SELECT ov.value
  FROM civicrm_option_value ov
    JOIN civicrm_option_group og ON og.id = ov.option_group_id AND og.name = 'activity_type';

-- insert all current pairs type-status
INSERT INTO civicrm_campaign_config_activity_status
  SELECT
    a.value AS activity_type_id, s.value AS status_id, s.label AS grouping
  FROM (SELECT ov.value
  FROM civicrm_option_value ov
    JOIN civicrm_option_group og ON og.id = ov.option_group_id AND og.name = 'activity_type') a,
    (SELECT
      ov.value, ov.label
    FROM civicrm_option_value ov
      JOIN civicrm_option_group og ON og.id = ov.option_group_id AND og.name = 'activity_status') s;

-- Set statuses as an Other except of Scheduled and Completed
UPDATE civicrm_campaign_config_activity_status
SET grouping = 'Other'
WHERE status_id >= 3;

-- insert grouping based on value of statuses
INSERT INTO civicrm_campaign_config_status_sequence
  SELECT
    ov.label, ov.value * 10
  FROM civicrm_option_value ov
    JOIN civicrm_option_group og ON og.id = ov.option_group_id AND og.name = 'activity_status';
INSERT INTO civicrm_campaign_config_status_sequence VALUES ('Other', 1000);
