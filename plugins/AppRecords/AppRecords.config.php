<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppRecords_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppRecords";
	public $title = "Credit Records Handler";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Stores the records of all credit transactions made on the site.";
	
	public $data = array();
	
}