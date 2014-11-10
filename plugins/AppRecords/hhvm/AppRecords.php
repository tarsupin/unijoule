<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppRecords Plugin ------
-----------------------------------------

This plugin provides tools to track the user's transaction records.


-------------------------------
------ Methods Available ------
-------------------------------

AppRecords::record($uniID, $transactionID, $runningTotal);

*/

abstract class AppRecords {
	
	
/****** Records a Credit Transaction ******/
	public static function record
	(
		int $uniID			// <int> The UniID of the user to record this transaction for.	
	,	int $transactionID	// <int> The ID of the transaction.
	,	bool $isSender		// <bool> TRUE if this user is the sender, FALSE if not.
	,	float $runningTotal	// <float> The running total for the user.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppRecords::record($uniID, $transactionID, $isSender, $runningTotal);
	{
		return Database::query("INSERT INTO credits_records (`uni_id`, `transaction_id`, `is_sender`, `running_total`) VALUES (?, ?, ?, ?)", array($uniID, $transactionID, ($isSender ? 1 : 0), $runningTotal));
	}
	
	
/****** Retrieves record data for a user ******/
	public static function get
	(
		int $uniID			// <int> The UniID of the user to review records for.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> TRUE on success, FALSE on failure.
	
	// $results = AppRecords::get($uniID);
	{
		return Database::selectMultiple("SELECT t.amount, t.fee, t.date_exchange, t.description, t.sender_uni_id, t.recipient_uni_id, r.is_sender, r.running_total FROM credits_records r INNER JOIN credits_transactions t ON t.id=r.transaction_id WHERE r.uni_id=? ORDER BY transaction_id DESC LIMIT 0, 20", array($uniID));
	}
	
	
}