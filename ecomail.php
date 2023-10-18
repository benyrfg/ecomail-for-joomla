<?php
/**
 * @version    1.0.0
 * @package    Ecomail (plugin)
 * @author     Petr Benes - https://petben.cz
 * @copyright  Copyright (c) 2023 Petr Benes. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */


// no direct access
defined( '_JEXEC' ) or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\Factory;


class PlgUserEcomail extends CMSPlugin  
{

	 public function onUserAfterSave($user, $isnew, $success, $msg)
	 {
		if (!$success)
		{
			return;
		}


        $key =              $this->params->get('ecomailapikey', 0);
        $listID =           $this->params->get('listID', 0);
        $source =           $this->params->get('source', 'API');
        $autoSubscribe =    filter_var($this->params->get('autoSubscribe', 'true'), FILTER_VALIDATE_BOOLEAN);
        $saveSplitName =    filter_var($this->params->get('saveSplitName', 'false'), FILTER_VALIDATE_BOOLEAN);
        $updateExisting =   filter_var($this->params->get('updateExisting', 'false'), FILTER_VALIDATE_BOOLEAN);
        $resubscribe =      filter_var($this->params->get('resubscribe', 'false'), FILTER_VALIDATE_BOOLEAN);
        $skipConfirmation = filter_var($this->params->get('skipConfirmation', 'false'), FILTER_VALIDATE_BOOLEAN);
        $autoresponders =   filter_var($this->params->get('autoresponders', 'false'), FILTER_VALIDATE_BOOLEAN);

        
        if($isnew === true AND $success === true){

            if($autoSubscribe === false){
                $db = Factory::getDbo();
                $query = $db
                    ->getQuery(true)
                    ->select('value')
                    ->from($db->quoteName('#__fields_values'))
                    ->where($db->quoteName('item_id') . " = " . $db->quote($user['id']))
                    ->where($db->quoteName('value') . " = " . $db->quote('subscribe'));
    
                $db->setQuery($query);
                $result = $db->loadResult();
            } elseif($autoSubscribe === true){
                $result = "subscribe";
            }            

            if($result == "subscribe"){

                $params = array(
                    "subscriber_data" => array (               
                        "email" => $user['email'],
                    )/*,                      
                    "trigger_autoresponders" => $autoresponders,
                    "update_existing" => $updateExisting,
                    "resubscribe" => $resubscribe,
                    "skip_confirmation" => $skipConfirmation*/
                      
                );

                // save split name
                if($saveSplitName === true ){                
                    $words = explode(' ', $user['name']);
                    $firstName = $words[0];
                    $lastName = implode(' ', array_slice($words, 1));
                    $fullName = array('firstName' => $firstName, 'surname' => $lastName);

                    if($fullName['firstName'] != ""){
                        $params["subscriber_data"]["name"] = $fullName['firstName'];
                    }
                    if($fullName['lastName'] != ""){
                        $params["subscriber_data"]["surname"] = $fullName['surname'];
                    }
                }              
                
                // send to Ecomail
                require_once __DIR__ . '/includes/Ecomail.php';
                $ecomail = new Ecomail($key);        
                $ecomail->addSubscriber($listID, $params);              

                JLog::add(json_encode($ecomail), JLog::ERROR, 'jerror');
               
            }
            
        }
        

		return;
	}

}
?>

