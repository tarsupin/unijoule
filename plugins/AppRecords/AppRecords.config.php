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
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `credits_records`
		(
			`auth_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`transaction_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			`is_sender`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`running_total`			float(12,4)		unsigned	NOT NULL	DEFAULT '0.0000',
			
			INDEX (`auth_id`, `transaction_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PARTITION BY KEY (`auth_id`) PARTITIONS 61;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		return DatabaseAdmin::columnsExist("credits_records", array("auth_id", "transaction_id"));
	}
	
}