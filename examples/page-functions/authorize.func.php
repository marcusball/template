<?php
namespace pirrs;

use ohmy\Auth2;

class AuthorizePage extends PageObject{
    public $user;
    private $accessToken;

    public function requireLoggedIn() { return false; }

    public function executeGet(){
        $oauthMan = ResourceManager::getOAuthManager();
        
        if($this->request->user->isLoggedIn() && $oauthMan->hasValidAccessToken()){
            //Forward to destination
            if($this->request->issetReq('destination')){
                $dest = $this->request->getReq('destination');

                $this->response->forwardTo('//' . SITE_DOMAIN . $dest);
            }
            else{
                $this->response->forwardTo('//' . SITE_DOMAIN);
            }
        }
        else{
            if($this->request->issetReq('destination')){
                $dest = substr($this->request->getReq('destination'),0,128); //Limit the length
                $_SESSION['authorize_forward_dest'] = $dest;
            }

            //Perform the OAuth Authorization request, receive data containing access token.
            $authData = $oauthMan->getAuthorization();

            //If all went well
            if($authData->status_code == 200){
                //...and we actually received the access token
                if(!isset($authData->access_token)){
                    $this->response->apply(DefaultAPIResponses::ServerError());
                    Log::error('Received unexpected data from OAuth server!',json_encode($authData));
                }

                $accessToken = new \tyto\user\AccessToken();
                //uid is unknown
                $accessToken->token = $authData->access_token;
                $accessToken->expiration = $authData->expires_in;
                $accessToken->updated = DatabaseController::getSQLTimeStamp();

                //Temporarily set the access token, so we can make requests with it.
                $oauthMan->setAccessToken($accessToken);

                //Get data about the user who just authenticated
                $userData = $oauthMan->getOAuthUserData();
                if($userData !== false){
                    if($this->handleUserData($userData->user, $authData)){
                        //Successfully authorized!
                        //Forward to destination

                        if(isset($_SESSION['authorize_forward_dest'])){
                            $dest = $_SESSION['authorize_forward_dest'];
                            $this->response->forwardTo('//' . SITE_DOMAIN . $dest);


                        }
                        else{
                            $this->response->forwardTo('/');
                        }
                    }
                    else{
                        //Unable to authorize for some reason
                        //$this->response->forwardto('/');
                    }
                }
                else{
                    $this->response->apply(DefaultAPIResponses::ServerError());
                    Log::error('Received unexpected data from OAuth server!',json_encode($userData));
                }
                unset($_SESSION['authorize_forward_dest']);
            }
            elseif($authData->status_code == 400){
                //If we're getting a bad request from the server,
                //we're likely using old data. Send the user back to
                //the auth page to restart the authorization process.
                $this->response->forwardTo(getCurrentUrl(false));
            }
            else{
                $this->response->apply(DefaultAPIResponses::ServerError());
                Log::error('Received unexpected data from OAuth server!',json_encode($authData));
            }
        }
    }

    private function handleUserData($user, $accessData){
        //Make sure the data we got from the oauth request appears valid
        if(!utilities\Validation::isValidName($user->full_name, Config::input('full_name_max_length'), Config::input('name_min_length')) ||
           !utilities\Validation::isValidName($user->addressing_name, Config::input('addressing_name_max_length'), Config::input('name_min_length'))){
            $this->response->addError('Received invalid data from OAuth request!');
            Log::error('Received bad user name data OAuth request!',sprintf('Full name: "%s", Addressing name: "%s"',$user->full_name, $user->addressing_name));
            return false;
        }

        //Check if this is a new user
        if(!$this->dbCon->isRegisteredUser($user->id)){
            //This user is new, so we'll register her.
            if(!$this->dbCon->insertNewUser($user->id, $user->full_name, $user->addressing_name)){
                $this->response->addError('Something went wrong while trying to register you! Please try again.');
                return false;
            }
        }
        else{
            //Update the user information in the database, in case anything has changed.
            $this->dbCon->updateUser($user->id, $user->full_name, $user->addressing_name);
        }

        //Update the oauth access token, then log the user in
        if($this->dbCon->updateUserAccessToken($user->id, $accessData->access_token, $accessData->expires_in)){
            //Update the refresh token as well
            if(!$this->dbCon->updateUserRefreshToken($user->id, $accessData->refresh_token)){
                //Maybe do something if there was an error
            }

            //Log the user in
            $this->request->user->giveCredentials($user->id);
            return true;
        }
        return false;
    }
}
?>
