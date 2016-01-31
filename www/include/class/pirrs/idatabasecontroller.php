<?php
namespace tyto;
interface iDatabaseController{
  /**
   * Check if a given user ID, $uid, is valid
   *   and registered in the database.
   * @param $uid The user ID to check.
   * @return TRUE if a user exists in the database
   *   registered with this user id, FALSE otherwise.
   */
  public function isValidUid($uid);

  /**
	 * Checks if a user is currently registered with this email address
	 * @return 1 if email exists, 0 if email does not exist, FALSE on error
	 */
	public function checkIfEmailExists($email);

  /**
	 * Registers a new user.
	 * @return FALSE on error, or an int representing the user's UID on success.
	 */
	public function registerNewUser($fullName,$email,$password);

  /**
	 * Changes a users password
	 * @return TRUE on successful update, FALSE otherwise.
	 */
	public function changeUserPassword($uid,$newPassword);

  /**
	 * Checks whether a user's login credentials are valid
	 * @param $email The user's email address
	 * @param $password the user's unhashed password
	 * @return FALSE on error, user's UID on valid credentials (uid > 0), 0 on invalid credentials
	 */
	public function isValidLogin($email, $password);

  /**
	 * Checks whether a user's login credentials are valid
	 * @param $email The user's email address
	 * @param $password the user's unhashed password
	 * @return false on error, user's UID on valid credentials (uid > 0), 0 on invalid credentials
	 */
	 public function isValidPassword($uid, $password);

   /**
 	  * Gets a user's UID from the user table corresponding to the given email address.
 	  * @return an int representing the uid of the user, 0 if the there is no matching email, or FALSE on error.
 	  */
   public function getUidFromEmail($email);

   /**
 	  * Get information from the user table
 	  */
   public function getUserInformation($uid);
}
?>
