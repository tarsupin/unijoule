<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class credits_records_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Credits Owned";		// <str> The title for this table.
	public $description = "The amount of credits owned by users.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "credits_records";		// <str> The name of the table.
	public $fieldIndex = array("auth_id", "transaction_id");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 9;			// <int> The clearance level required to view this table.
	public $permissionSearch = 9;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 11;		// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 11;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Install the table ******/
	public function install (
	)			// RETURNS <bool> TRUE if the installation was success, FALSE if not.
	
	// $schema->install();
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
		
		$define->set("auth_id")->title("Auth ID")->description("The User's Auth ID.")->isReadonly();
		$define->set("transaction_id")->title("Transaction ID")->description("The ID of the transaction.")->isUnique()->isReadonly();
		$define->set("is_sender")->title("Sender")->description("Whether or not the user is the sender of the credits.")->fieldType("boolean")->pullType("select", "yes-no");
		$define->set("running_total")->description("The current running total of the user.");
		
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
				$schema->addFields("auth_id", "transaction_id", "is_sender", "running_total");
				$schema->sort("id");
				break;
				
			case "search":
				$schema->addFields("auth_id", "transaction_id", "is_sender");
				break;
				
			case "create":
			case "edit":
				break;
		}
		
		return $schema;
	}
	
}