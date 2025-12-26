CREATE TABLE tx_sitescore_analysis (
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    uid int(11) unsigned NOT NULL auto_increment,
    pid int(11) unsigned DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    scores text,
    suggestions text,

    PRIMARY KEY (uid),
    KEY language (pid,sys_language_uid)
);
