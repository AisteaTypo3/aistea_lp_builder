CREATE TABLE tt_content (
  tx_aistealpproductslider_ba_image_before int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_ba_image_after int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_ba_label_before varchar(255) DEFAULT '' NOT NULL,
  tx_aistealpproductslider_ba_label_after varchar(255) DEFAULT '' NOT NULL,
  tx_aistealpproductslider_ba_initial_position int(11) unsigned DEFAULT '50' NOT NULL,
  tx_aistealpproductslider_hi_image int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_hi_hotspots int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_interviews int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_highlights int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_slides int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_hslides int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_fsv_short_video int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_fsv_long_video int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_fsv_kicker varchar(255) DEFAULT '' NOT NULL,
  tx_aistealpproductslider_fsv_headline varchar(255) DEFAULT '' NOT NULL,
  tx_aistealpproductslider_fsv_button_label varchar(255) DEFAULT 'Watch full video' NOT NULL,
  tx_aistealpproductslider_sequence_source varchar(20) DEFAULT 'filelist' NOT NULL,
  tx_aistealpproductslider_sequence_collection int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_sequence_frames int(11) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_sequence_fps int(11) unsigned DEFAULT '12' NOT NULL,
  tx_aistealpproductslider_sequence_loop tinyint(1) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_layout_mode varchar(20) DEFAULT 'default' NOT NULL,
  tx_aistealpproductslider_breakpoint_mobile int(11) unsigned DEFAULT '768' NOT NULL,
  tx_aistealpproductslider_reduced_motion_behavior varchar(30) DEFAULT 'static' NOT NULL,
  tx_aistealpproductslider_video_autoplay_desktop tinyint(1) unsigned DEFAULT '1' NOT NULL,
  tx_aistealpproductslider_video_autoplay_mobile tinyint(1) unsigned DEFAULT '0' NOT NULL,
  tx_aistealpproductslider_preload_strategy varchar(20) DEFAULT 'smart' NOT NULL,
  tx_aistealpproductslider_stage_aspect_ratio varchar(20) DEFAULT '1/1' NOT NULL,
  tx_aistealpproductslider_theme varchar(20) DEFAULT 'dark' NOT NULL,
  file_collection int(11) unsigned DEFAULT '0' NOT NULL,
  desktop_fps int(11) unsigned DEFAULT '24' NOT NULL,
  desktop_loop tinyint(1) unsigned DEFAULT '0' NOT NULL,
  mobile_breakpoint int(11) unsigned DEFAULT '768' NOT NULL,
  mobile_mode varchar(20) DEFAULT 'lastFrame' NOT NULL,
  mobile_static_image int(11) unsigned DEFAULT '0' NOT NULL,
  preload_strategy varchar(20) DEFAULT 'smart' NOT NULL,
  scroll_driven tinyint(1) unsigned DEFAULT '0' NOT NULL,
  alt_text varchar(255) DEFAULT '' NOT NULL,
  background_color varchar(32) DEFAULT '' NOT NULL,
  render_full_list_on_mobile tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_aistealpproductslider_slide (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  parentid int(11) unsigned DEFAULT '0' NOT NULL,
  parenttable varchar(255) DEFAULT 'tt_content' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) unsigned DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  bodytext text,
  slide_type varchar(30) DEFAULT 'image' NOT NULL,
  aria_label varchar(255) DEFAULT '' NOT NULL,

  model_autorotate tinyint(1) unsigned DEFAULT '0' NOT NULL,
  model_camera_preset varchar(20) DEFAULT 'front' NOT NULL,
  model_bg_color varchar(20) DEFAULT '' NOT NULL,
  color_variants text,

  PRIMARY KEY (uid),
  KEY parent (parentid, parenttable),
  KEY language (l10n_parent, sys_language_uid)
);

CREATE TABLE tx_aistealpproductslider_interview (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  parentid int(11) unsigned DEFAULT '0' NOT NULL,
  parenttable varchar(255) DEFAULT 'tt_content' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) unsigned DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  subline varchar(255) DEFAULT '' NOT NULL,
  bodytext text,

  PRIMARY KEY (uid),
  KEY parent (parentid, parenttable),
  KEY language (l10n_parent, sys_language_uid)
);

CREATE TABLE tx_aistealpproductslider_highlight (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  parentid int(11) unsigned DEFAULT '0' NOT NULL,
  parenttable varchar(255) DEFAULT 'tt_content' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) unsigned DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  bodytext text,
  media_image int(11) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (parentid, parenttable),
  KEY language (l10n_parent, sys_language_uid)
);

CREATE TABLE tx_aistealpproductslider_hotspot (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  parentid int(11) unsigned DEFAULT '0' NOT NULL,
  parenttable varchar(255) DEFAULT 'tt_content' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) unsigned DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  title varchar(255) DEFAULT '' NOT NULL,
  description text,
  pos_x int(11) unsigned DEFAULT '50' NOT NULL,
  pos_y int(11) unsigned DEFAULT '50' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (parentid, parenttable),
  KEY language (l10n_parent, sys_language_uid)
);

CREATE TABLE tx_aistealpproductslider_hslide (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  parentid int(11) unsigned DEFAULT '0' NOT NULL,
  parenttable varchar(255) DEFAULT 'tt_content' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) unsigned DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  headline varchar(255) DEFAULT '' NOT NULL,
  slide_type varchar(30) DEFAULT 'image' NOT NULL,
  sequence_fps int(11) unsigned DEFAULT '12' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (parentid, parenttable),
  KEY language (l10n_parent, sys_language_uid)
);
