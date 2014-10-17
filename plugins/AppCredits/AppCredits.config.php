<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppCredits_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppCredits";
	public $title = "Credit System";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Allows handling of credits and transactions within Credit site.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `credits`
		(
			`auth_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`amount`				float(12,4)		unsigned	NOT NULL	DEFAULT '0.0000',
			
			UNIQUE (`auth_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		return DatabaseAdmin::columnsExist("credits", array("auth_id", "amount"));
	}
	
}