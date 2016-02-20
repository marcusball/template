<?php
namespace pirrs;
use \PDO;

class DatabaseController implements iDatabaseController{
	private $_sqlCon; //DO NOT ACCESS DIRECTLY! Use $this->sqlCon()
	// add other private database connections here
	public function __construct(){
	}
	public function setPDOAttribute($attribute,$value){
		return $this->sqlCon()->setAttribute($attribute,$value);
	}

	public static function getSQLTimeStamp($time = null){
		if($time == null) $time = time();
		return date('Y-m-d H:i:s',$time);
	}

	public function getSQLConnection(){
		return $this->sqlCon();
	}

	private function sqlCon(){
		if($this->_sqlCon == null){
			$this->_sqlCon = new PDO(DB_PDO_NAME.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, null);
		}
		return $this->_sqlCon;
	}

	/*
	 * Add other private databaseConnect functions here
	 * Return the connection object, then save to private member.
	 */

	/***********************************************************/
	/* DO NOT MODIFY THE CODE ABOVE THIS SECTION               */
	/* Add in your own database access methods below this point*/
	/***********************************************************/


	/***********************************************************/
	/* Registration and Authentication methods                 */
	/***********************************************************/

	/**
	 * Check if a given user ID, $uid, is valid
	 *   and registered in the database.
	 * @param $uid The user ID to check.
	 * @return TRUE if a user exists in the database
	 *   registered with this user id, FALSE otherwise.
	 */
	public function isValidUid($uid){
		try{
			$uidCheck = $this->sqlCon()->prepare('SELECT uid FROM users WHERE uid=:uid');
			$uidCheck->execute(array(':uid'=>$uid));

			if(($uidReturn = $uidCheck->fetch(PDO::FETCH_ASSOC)) !== false){
				if($uidReturn['uid'] == $uid){
					return true;
				}
			}
		}
		catch(PDOException $e){
			Log::error('Error while checking if Uid is valid',$e->getMessage());
		}
		return false;
	}

	/**
	 * Checks if a user is currently registered with this email address
	 * @return 1 if email exists, 0 if email does not exist, FALSE on error
	 */
	public function checkIfEmailExists($email){
		$val = $this->getUidFromEmail($email);
		if($val !== false){
			return min(1,$val); //If val >= 1, return 1; if val == 0, return 0;
		}
		return false;
	}

	/**
	 * Registers a new user.
	 * @return FALSE on error, or an int representing the user's UID on success.
	 */
	public function registerNewUser($fullName,$email,$password){
		$passwordHash = password_hash($password.PASSWORD_SALT, PASSWORD_BCRYPT, array("cost" => AUTH_HASH_COMPLEXITY));
		$nowDatetime = self::getSQLTimeStamp();
		try{
			$this->sqlCon()->beginTransaction(); //Registering a new user means a lot of different inserts, so we want to make sure either all or nothing occurs.


			//Let's insert the user into the user table
			$regStatement = $this->sqlCon()->prepare($insertQuery);
			$regStatement->execute(array(':email'=>$email,':fullname'=>$fullName,':password'=>$passwordHash));

			$this->sqlCon()->commit();

			$uidValue = $this->getUidFromEmail($email); //get the ID we just inserted, because lastInsertId can be weird sometimes.
		}
		catch(PDOException $e){
			$this->sqlCon()->rollBack();
			Log::error("An error occurred while registering a new user! Code: {$e->getCode()}.",$e->getMessage());
			return false;
		}

		//If the uidValue is valid
		if($uidValue > 0 && $uidValue != false){
			return $uidValue;
		}
		return false;
	}

	/**
	 * Changes a users password
	 * @return TRUE on successful update, FALSE otherwise.
	 */
	public function changeUserPassword($uid,$newPassword){
		$passwordHash = password_hash($newPassword.PASSWORD_SALT, PASSWORD_BCRYPT, array('cost' => AUTH_HASH_COMPLEXITY));
		try{
			$updateQuery = $this->sqlCon()->prepare('UPDATE users SET password=:password WHERE uid=:uid');
			$updateQuery->execute(array(':uid'=>$uid,':password'=>$passwordHash));
			if($updateQuery->rowCount() == 1){
				return true;
			}
		}
		catch(PDOException $e){
			Log::error('databasecontroller.php',__LINE__,'Error while trying to update user\'s password!',$e->getMessage,time(),false);

		}
		return false;
	}

	/**
	 * Checks whether a user's login credentials are valid
	 * @param $email The user's email address
	 * @param $password the user's unhashed password
	 * @return FALSE on error, user's UID on valid credentials (uid > 0), 0 on invalid credentials
	 */
	public function isValidLogin($email, $password){
		$uid = $this->getUidFromEmail($email);
		if($uid === 0 || $uid === false){ // Invalid email, or an error occurred
			return $uid;
		}

		return $this->isValidPassword($uid,$password);
	}

	/*
	 * Checks whether a user's login credentials are valid
	 * $email: The user's email address
	 * $password: the user's unhashed password
	 * Returns false on error, user's UID on valid credentials (uid > 0), 0 on invalid credentials
	 */
	 public function isValidPassword($uid, $password){
		/** BEGIN: Query database for login authentication **/
		$loginQuery = 'SELECT uid, email, password FROM users WHERE uid=:uid LIMIT 1;';
		try{
			$loginStatement = $this->sqlCon()->prepare($loginQuery);
			$loginStatement->bindParam(':uid',$uid,PDO::PARAM_STR);
			$loginStatement->execute();
			$loginResult = $loginStatement->fetch();
		}
		catch(PDOException $e){
			Log::error("Could not check user's login credentials. Code: {$e->getCode()}. UID: \"{$uid}\"");
			return false;
		}

		/** We've gotten the result of the query, now we need to validate **/
		if($loginResult === false || $loginResult == null){
			return 0;
			// Email was wrong, but we don't tell the
			// user as this information could be exploited
		}

		/** At this point we know the email matches a record in the DB.
		 ** Now we just need to make sure the password is correct.
		 ** If the password is correct we'll give session info
		 **/
		$uidValue = $loginResult['uid'];
		$hash = $loginResult['password'];
		if(!password_verify($password.PASSWORD_SALT,$hash)){
			/** The password provided did not match the one in the database **/

			/** Increment the attempt_count for this user, and lock the account if necessary **/
			return 0;
		}
		else{
			if (password_needs_rehash($hash, PASSWORD_BCRYPT, array("cost" => AUTH_HASH_COMPLEXITY))) {
				/** If we change the hash algorithm, or the complexity, then old passwords need to be rehashed and updated **/
				$this->updatePasswordHash($uidValue, $password);
			}
			return $uidValue;
		}

		return 0; //This code should never be reached, but I like to be safe.
	}

	/*
	 * Updates the database with a password hash of new complexity value.
	 */
	private function updatePasswordHash($uid, $unhashedPassword){
		$hash = password_hash($unhashedPassword.PASSWORD_SALT, PASSWORD_BCRYPT, array("cost" => AUTH_HASH_COMPLEXITY));

		$hashUpdate = "UPDATE users SET password=:hash WHERE uid=:uid;";
		try{
			$hashUpdateStatement = $this->sqlCon()->prepare($hashUpdate);
			$hashUpdateStatement->execute(array(':hash'=>$hash,':uid'=>$uid));
		}
		catch(PDOException $e){
			Log::error("Could not update a user's rehashed password! Code: {$e->getCode()}. UID: \"{$uid}\"",$e->getMessage());
		}
	}

	/*
	 * Gets a user's UID from the user table corresponding to the given email address.
	 * Returns an int representing the uid of the user, 0 if the there is no matching email, or false on error.
	 */
	public function getUidFromEmail($email){
		$userCheckQuery = "SELECT uid FROM users WHERE email=:email LIMIT 1;"; //Make sure this keeps LIMIT 1
		try{
			$statement = $this->sqlCon()->prepare($userCheckQuery);
			$statement->execute(array(':email' => $email));

			//If the query returned rows, then someone IS registered using this email
			if($statement->rowCount() > 0){
				$match = $statement->fetch();
				return $match['uid'];
			}
			else{
				return 0;
			}
		}
		catch(PDOException $e){
			Log::error("Error executing getting user from email! Query: \"$userCheckQuery\", Email: \"$email\".",$e->getMessage());
		}
		return false;
	}


    /*
     * Updates a specific user's oauth access token.
     * $uid: the user id of to whom the access token belongs
     * $accessToken: the oauth access token
     * $expires: the integer representing the number of seconds
     *   until the access token expires.
     * returns true on success, false on error.
     */
    public function updateUserAccessToken($uid,$accessToken,$expires){
        try{
            $updateStatement = $this->sqlCon()->prepare('UPDATE users SET access_token=:token, access_expiration=:expire, access_updated=NOW() WHERE uid=:uid');
            $updateStatement->bindParam(':token',$accessToken,PDO::PARAM_STR);
            $updateStatement->bindParam(':expire',$expires,PDO::PARAM_INT);
            $updateStatement->bindParam(':uid',$uid,PDO::PARAM_STR);
            $updateStatement->execute();
            if($updateStatement->rowCount() > 0){
                return true;
            }
        }
        catch(PDOException $e){
            Log::error('Error updating user access token!',$e->getMessage());
        }
        return false;
    }

    /*
     * Updates a user's oauth refresh token.
     * $uid: the user id of to whom the refresh token belongs
     * $refreshToken: the oauth refresh token
     * returns true on success, false on error.
     */
    public function updateUserRefreshToken($uid,$refreshToken){
        try{
            $updateStatement = $this->sqlCon()->prepare('UPDATE users SET refresh_token=:token WHERE uid=:uid');
            $updateStatement->bindParam(':token',$refreshToken,PDO::PARAM_STR);
            $updateStatement->bindParam(':uid',$uid,PDO::PARAM_STR);
            $updateStatement->execute();
            if($updateStatement->rowCount() > 0){
                return true;
            }
        }
        catch(PDOException $e){
            Log::error('Error updating user refresh token!',$e->getMessage());
        }
        return false;
    }

	/***********************************************************/
	/* User information methods                                */
	/***********************************************************/

	/*
	 * Get information from the user table
	 * @param $uid The uid of the user to update.
	 * @param $userObject A *reference* to a \tyto\User object. Data will be
	 *   fetched into this object if provided.
	 * @return a User object containing information. If $userObject is null,
	 *   a new User object will be created, otherwise, return will be
	 *   the updated $userObject.
	 * @note If $userObject is not null, all information will be overwritten,
	 *   regardless of whether $userObject->uid == $uid.
	 */
	public function getUserInformation($uid, User &$userObject = null){
		$userQuery = 'SELECT id, email FROM users WHERE id=:uid LIMIT 1;';
		try{
			$userStatement = $this->sqlCon()->prepare($userQuery);
			$userStatement->bindParam(':uid',$uid,PDO::PARAM_INT);
			$userStatement->execute();

			//If no $userObject is provided, we'll fetch into a new object
			if($userObject === null){
				$userStatement->setFetchMode(PDO::FETCH_CLASS, User::class);
			}
			//Otherwsie, fetch into the existing object
			else{
				$userStatement->setFetchMode(PDO::FETCH_INTO, $userObject);
			}

			//Fetch and return the user object
			if(($userData = $userStatement->fetch()) !== false){
				return $userData;
			}
		}
		catch(PDOException $e){
			Log::error("Error executing getting user information! Uid = {$uid}.",$e->getMessage());
		}
		return false;
	}

    /*
     * Fetches the OAuth access token for the user specified by $uid.
     * returns a \pirrs\user\AccessToken object, or false on error.
     */
    public function getUserAccessToken($uid){
        try{
            //Select
            $tokenQuery = $this->sqlCon()->prepare('
                SELECT
                    users.uid, users.access_token AS token,
                    users.access_expiration AS expiration,
                    users.access_updated AS updated
                FROM users
                WHERE users.uid=:uid
            ');

            $tokenQuery->bindParam(':uid',$uid,PDO::PARAM_STR);
            $tokenQuery->execute();

            $tokenQuery->setFetchMode(PDO::FETCH_CLASS,\pirrs\user\AccessToken::class);
            $token = $tokenQuery->fetch();

            if($token !== false){
                return $token;
            }
        }
        catch(PDOException $e){
            Log::error('Error fetching user access token!',$e->getMessage());
        }
        return false;
    }

    /*
     * Fetches the OAuth refresh token for the user specified by $uid
     * returns the a \pirrs\user\RefreshToken object if a refresh token exists
     * returns false on error, or if no refresh token is present.
     */
    public function getUserRefreshToken($uid){
        try{
            $tokenQuery = $this->sqlCon()->prepare('SELECT uid, refresh_token AS token FROM users WHERE uid=:uid');
            $tokenQuery->bindParam(':uid',$uid,PDO::PARAM_STR);
            $tokenQuery->execute();

            $tokenQuery->setFetchMode(PDO::FETCH_CLASS,\pirrs\user\RefreshToken::class);
            $token = $tokenQuery->fetch();
            if($token !== false && $token->token != ''){
                return $token;
            }
        }
        catch(PDOException $e){
            Log::error('Error fetching user refresh token!',$e->getMessage());
        }
        return false;
    }

	/*
	 * Returns the string of the full name of the user corresponding to the given uid.
	 */
	public function getUserFullName($uid){
		if(($info = $this->getUserInformation($uid)) !== false){
			return $info['full_name'];
		}
		return false;
	}

	/***********************************************************/
	/* Database Methods                                        */
	/***********************************************************/
	public function getLastErrorCode(){
		return $this->sqlCon()->errorCode();
	}
}
?>
