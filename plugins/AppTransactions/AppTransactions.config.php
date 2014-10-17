<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppTransactions_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppTransactions";
	public $title = "Credit Transaction Tools";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Manages transactions and exchanges of credits..";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `credits_transactions`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`sender_auth_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			`sender_uni_id`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`recipient_auth_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			`recipient_uni_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`amount`				float(12,4)					NOT NULL	DEFAULT '0.0000',
			`fee`					float(6,4)					NOT NULL	DEFAULT '0.0000',
			
			`date_exchange`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`site_handle`			varchar(22)					NOT NULL	DEFAULT '',
			`description`			varchar(100)				NOT NULL	DEFAULT '',
			`was_refunded`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY (`id`) PARTITIONS 61;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		return DatabaseAdmin::columnsExist("credits_transactions", array("id", "amount"));
	}
	
}