<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class ExchangeAPI_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "api";
	public $pluginName = "ExchangeAPI";
	public $title = "Exchange Credits";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides an API to exchange credits between two users, such as for a tip.";
	
	public $data = array();
	
}