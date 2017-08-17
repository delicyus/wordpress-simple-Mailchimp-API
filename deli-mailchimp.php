<?php
/**
Plugin Name: Deli Mailchimp API Integration
Description: Affichage du formulaire et traitement des requetes avec API v3
Version: 2017-08
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * @example     global $Deli_Mailchimp_Plugin;
 *              $Deli_Mailchimp_Plugin -> render_formulaire();
 *
 * @uses        MAILCHIMP V3
 *              https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
 *
 * @Tutorial    https://stackoverflow.com/questions/30481979/adding-subscribers-to-a-list-using-mailchimps-api-v3
 */
class Deli_Mailchimp_Plugin
{
    public function __construct()
    {
        add_action( 'wp_head', array($this , 'subscribe_do') );
        //
        $this -> subscribe_do = false;
    }

    // RENDER FORMULAIRE
    public function render_formulaire(){
        // We display the form
        ?>
        <div class="nl-embed" id="mailchimp-subscribe-form">
            <form method="post" action="#mailchimp-subscribe-form">
            <?php
                // We display feedbacks of the form
                if( $this -> subscribe_do){
                    // Success
                    if("subscribed"== $this -> subscribe_do -> results -> status){
                        ?>
                        <div class="text-success ">
                            *** <?php
                            echo $this -> l_('bravo-well-keep-in-touch');
                            ?> ***
                        </div><br />
                        <?php
                    }
                    // Failed
                    if($this -> subscribe_do -> feedbacks){
                        ?>
                        <div class="text-danger">
                            <?php
                            foreach($this -> subscribe_do -> feedbacks as $feedback){
                                echo $feedback;
                            }
                            ?>
                            <p>******* ! *******</p>
                        </div><br />
                        <?php
                    }
                }
                ?>
                <div class="input-wrapper"><label><?php echo $this -> l_('votre-e-mail'); ?></label><input type="text" name="email" value="<?php echo $_POST['email'];?>" /></div>
                <div class="input-wrapper"><label><?php echo $this -> l_('votre-nom'); ?></label><input type="text" name="firstname" value="<?php echo $_POST['firstname'];?>" /></div>
                <div class="input-wrapper"><label><?php echo $this -> l_('votre-prenom'); ?></label><input type="text" name="lastname" value="<?php echo $_POST['lastname'];?>" /></div>
                <input type="submit" name="mailchimp-subscribe" value="<?php echo $this -> l_('recevoir-les-news'); ?>" class="submit" />
            </form>
        </div>
        <?php
    }

    // Traitement du formulaire pour subscribe un nouveau abonne
    public function subscribe_do(){

        // Trigger the form submission
        if(!isset($_POST['mailchimp-subscribe'])
            || ""==$_POST['mailchimp-subscribe'])
        return false;

        // Init the returned result
        $this -> subscribe_do = new stdClass();

        // Validate the fields
        $this -> subscribe_do -> feedbacks = $this -> validate_form();
        if($this -> subscribe_do -> feedbacks)
            return false;

        // do api call
        $do_api_call = $this -> do_api_call();

        // Return
        if(200==$do_api_call -> httpCode)
            $this -> subscribe_do -> results = json_decode($do_api_call -> result);
        else
            $this -> subscribe_do -> feedbacks = array($this -> l_('oups-error','return'));

        return $this -> subscribe_do;
    }

    // FORM VALIDATION
    private function validate_form()
   {
        // Required fields
        $feedbacks = false;

        if(!is_email($_POST['email']))
            $feedbacks['err-email'] = '<div class="text-danger">'.$this -> l_('err-email', true).'</div>';

        if(!sanitize_text_field($_POST['firstname']))
            $feedbacks['err-firstname'] = '<div class="text-danger">'.$this -> l_('err-firstname', true).'</div>';

        if(!sanitize_text_field($_POST['lastname']))
            $feedbacks['err-lastname'] = '<div class="text-danger">'.$this -> l_('err-lastname', true).'</div>';

        return $feedbacks;

   }

    // CREDENTIALS
    private function get_apiKey(){
        // Depuis API dev panel
        return '065027c53d73e880e1d462271ffd408a-us15';
    }

    // REQUETE
    private function do_api_call(){

        // API from dev panel
        $apiKey = $this -> get_apiKey();

        // Depuis admin list panel
        $listId = 'b34a75e04b';

        // Params pour requete vers API
        $memberId   = md5(strtolower($_POST['email']));
        $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
        $url        = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;
        $json = json_encode([
            'email_address' => $_POST['email'],
            'status'        => "subscribed", // "subscribed","unsubscribed","cleaned","pending"
            'merge_fields'  => [
                'FNAME'     => $_POST['firstname'],
                'LNAME'     => $_POST['lastname']
            ]
        ]);

        // Requete via CURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        // Results
        $result     = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $return -> result = $result;
        $return -> httpCode = $httpCode;
        return $return;

    }

    // Strings rendering
    private function l_($string,$return){

        // Si le plugin deli-i18n existes
        if(function_exists('l_'))
            return l_($string,$return);

        // sinon return le string sans rien faire
        return $string;


    }
}
global $Deli_Mailchimp_Plugin;
$Deli_Mailchimp_Plugin = new Deli_Mailchimp_Plugin();
?>
