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
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`amount`				float(12,4)		unsigned	NOT NULL	DEFAULT '0.0000',
			
			UNIQUE (`uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY (uni_id) PARTITIONS 7;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `credits_purchases`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`txn_id`				varchar(22)					NOT NULL	DEFAULT '',
			`payment_status`		varchar(12)					NOT NULL	DEFAULT '',
			`email`					varchar(85)					NOT NULL	DEFAULT '',
			
			`amount_paid`			float(8,2)		unsigned	NOT NULL	DEFAULT '0.00',
			`credits_provided`		float(8,2)		unsigned	NOT NULL	DEFAULT '0.00',
			
			`date_paid`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			INDEX (`uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY (uni_id) PARTITIONS 13;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `credits_giftcards`
		(
			`giftcard_code`			varchar(20)					NOT NULL	DEFAULT '',
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`credits`				float(10,4)		unsigned	NOT NULL	DEFAULT '0.00',
			
			`email`					varchar(85)					NOT NULL	DEFAULT '',
			`date_purchased`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`giftcard_code`),
			INDEX (`uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("credits", array("uni_id", "amount"));
		$pass2 = DatabaseAdmin::columnsExist("credits_purchases", array("uni_id", "amount_paid"));
		$pass3 = DatabaseAdmin::columnsExist("credits_giftcards", array("uni_id", "credits"));
		
		return ($pass1 and $pass2 and $pass3);
	}
	
}