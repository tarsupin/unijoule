<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class credits_transactions_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Credit Transactions";		// <str> The title for this table.
	public $description = "The transactions made with credits.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "credits_transactions";		// <str> The name of the table.
	public $fieldIndex = array("id");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 9;			// <int> The clearance level required to view this table.
	public $permissionSearch = 9;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 9;			// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 9;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Install the table ******/
	public function install (
	)			// RETURNS <bool> TRUE if the installation was success, FALSE if not.
	
	// $schema->install();
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
		
		return DatabaseAdmin::tableExists($this->tableKey);
	}
	
	
/****** Build the schema for the table ******/
	public function buildSchema (
	)			// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $schema->buildSchema();
	{
		Database::startTransaction();
		
		// Create Schmea
		$define = new SchemaDefine($this->tableKey, true);
		
		$define->set("id")->title("Transaction ID")->description("The ID of the transaction.")->isUnique()->isReadonly();
		$define->set("sender_auth_id")->title("Auth ID")->description("The Auth ID of the sender.")->isReadonly();
		$define->set("sender_uni_id")->title("UniID")->description("The UniFaction ID of the sender.")->isReadonly();
		$define->set("recipient_auth_id")->title("Recipient Auth ID")->description("The Auth ID of the recipient.")->isReadonly();
		$define->set("recipient_uni_id")->title("Recipient UniID")->description("The UniFaction ID of the recipient.")->isReadonly();
		$define->set("amount")->description("The amount of credits exchanged in the transaction.");
		$define->set("fee")->description("The fee associated with this transaction.");
		$define->set("date_exchange")->title("Exchange Date")->description("The timestamp of when the exchange was made.")->fieldType("timestamp");
		$define->set("site_handle")->description("The site handle of the site responsible for the exchange.")->fieldType("variable");
		$define->set("description")->description("The descripttion associated with the transaction.");
		$define->set("was_refunded")->description("Whether or not the transaction was refunded.");
		
		Database::endTransaction();
		
		return true;
	}
	
	
/****** Set the rules for interacting with this table ******/
	public function __call
	(
		$name		// <str> The name of the method being called ("view", "search", "create", "delete")
	,	$args		// <mixed> The args sent with the function call (generaly the schema object)
	)				// RETURNS <mixed> The resulting schema object.
	
	// $schema->view($schema);		// Set the "view" options
	// $schema->search($schema);	// Set the "search" options
	{
		// Make sure that the appropriate schema object was sent
		if(!isset($args[0])) { return; }
		
		// Set the schema object
		$schema = $args[0];
		
		switch($name)
		{
			case "view":
				$schema->addFields("id", "sender_auth_id", "sender_uni_id", "recipient_auth_id", "recipient_uni_id", "amount", "fee", "date_exchange", "site_handle", "description", "was_refunded");
				$schema->sort("id");
				break;
				
			case "search":
				$schema->addFields("id", "sender_auth_id", "sender_uni_id", "recipient_auth_id", "recipient_uni_id", "amount", "date_exchange", "site_handle", "description", "was_refunded");
				break;
				
			case "create":
				break;
				
			case "edit":
				$schema->addFields("id", "sender_auth_id", "sender_uni_id", "recipient_auth_id", "recipient_uni_id", "amount", "fee", "date_exchange", "site_handle", "description", "was_refunded");
				break;
		}
		
		return $schema;
	}
	
}