<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class PurchaseAPI_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "api";
	public $pluginName = "PurchaseAPI";
	public $title = "Purchase API";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides an API to purchase things from other sites using TeslaCoin.";
	
	public $data = array();
	
}