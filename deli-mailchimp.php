<?php
/**
Plugin Name: Deli Mailchimp API Integration
Description: Affichage du formulaire et traitement des requetes avec API v3
Version: 0.1.1
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * @example     echo do_shortcode('[formulaire-subscribe]');
 *
 * @uses        MAILCHIMP V3
 *              https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
 */
class Deli_Mailchimp_Plugin
{
    public function __construct()
    {
        // TRAITEMENT $_POST
        add_action( 'wp_head', array($this , 'subscribe_do') );

        // SHORTCODE
        add_shortcode( 'formulaire-subscribe', array( $this , 'shortcode_formulaire' ) );

        // GLOBAL init
        $this -> subscribe_do = false;

        // STYLING CSS classes (optional customisation)
        $this -> formClass          = 'nl-embed';
        $this -> textSuccessClass   = 'text-success';
        $this -> textErrorClass     = 'text-danger';
        $this -> formRowClass       = 'input-wrapper';
        $this -> submitBtnClass     = 'submit';
        $this -> formID             = 'mailchimp-subscribe-form';

        // CREDENTIALS (required)
        // Cle d'api a generer sur MC dev dashboard
        $this -> apiKey = ''; // <------- REPLACE WITH YOUR API KEY

        // ID de la liste MC dans laquelle sera inscrit l'abonné
        $this -> listId = ''; // <------- REPLACE WITH YOUR LIST ID

    }

    // RENDER FORMULAIRE
    private function render_formulaire(){
    ?>
        <div class="<?php echo $this -> formClass; ?>" id="<?php echo $this -> formID; ?>">
            <form method="post" action="#<?php echo $this -> formID; ?>">
                <?php
                // Feebacks strings
                if( $this -> subscribe_do){
                    // Success
                    if("subscribed"== $this -> subscribe_do -> results -> status){
                        ?>
                        <div class="<?php echo $this -> textSuccessClass; ?>">
                            <?php
                            echo $this -> l_('bravo');
                            ?>
                        </div><br />
                        <?php
                    }
                    // Failed
                    if($this -> subscribe_do -> feedbacks){
                        ?>
                        <div class="<?php echo $this -> textErrorClass; ?>">
                            <?php
                            foreach($this -> subscribe_do -> feedbacks as $feedback){
                                echo $feedback;
                            }
                            ?>
                        </div><br />
                        <?php
                    }
                }

                // Formulaire
                ?>
                <div class="<?php echo $this -> formRowClass; ?>">
                    <label><?php echo $this -> l_('votre-e-mail'); ?></label>
                    <input type="text" name="email" value="<?php echo $_POST['email'];?>" />
                </div>
                <div class="<?php echo $this -> formRowClass; ?>">
                    <label><?php echo $this -> l_('votre-nom'); ?></label>
                    <input type="text" name="firstname" value="<?php echo $_POST['firstname'];?>" />
                </div>
                <div class="<?php echo $this -> formRowClass; ?>">
                    <label><?php echo $this -> l_('votre-prenom'); ?></label>
                    <input type="text" name="lastname" value="<?php echo $_POST['lastname'];?>" />
                </div>
                <input type="submit" name="mailchimp-subscribe" value="<?php echo $this -> l_('recevoir-les-news'); ?>" class="<?php echo $this -> submitBtnClass; ?>" />
            </form>
        </div>
        <?php
    }

    // SHORTCODE FORMULAIRE
    // usage : echo do_shortcode('[formulaire-subscribe]');
    public function shortcode_formulaire( $atts ) {

        if('' == $this -> apiKey
            || '' == $this -> listId)
            return 'ERR : Credentials params missing';

        return $this -> render_formulaire();
    }

    // TRAITEMENT $_POST :  subscribe un nouvel abonne
    // @return    $this -> subscribe_do 
    public function subscribe_do(){

        // Requis pour trigger the form submission
        if(
            !isset($_POST['mailchimp-subscribe'])
            || '' == $_POST['mailchimp-subscribe']
            || '' == $this -> apiKey
            || '' == $this -> listId)
        return false;

        // Init the returned result
        $this -> subscribe_do = new stdClass();

        // Validate the fields
        $this -> subscribe_do -> feedbacks = $this -> validate_form();
        if($this -> subscribe_do -> feedbacks)
            return false;

        // do api call
        // pour subscribe un nouvel abonne
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

    // REQUETE
    private function do_api_call(){

        // API from dev panel
        $apiKey = $this -> apiKey;

        // Depuis admin list panel
        $listId = $this -> listId;

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

    // RENDER STRINGS
    private function l_($string,$return){

        // All plugins strings
        $strings = array(
            'bravo'             => 'bravo',
            'votre-e-mail'      => 'votre-e-mail',
            'votre-nom'         => 'votre-nom',
            'votre-prenom'      => 'votre-prenom',
            'recevoir-les-news' => 'recevoir-les-news',
            'oups-error'        => 'oups-error',
            'err-email'         => 'err-email',
            'err-firstname'     => 'err-firstname',
            'err-lastname'      => 'err-lastname',
            );

        // Return (string)
        return $strings[$string];
    }


}
// Init du plugin dans variable globale
global $Deli_Mailchimp_Plugin;
$Deli_Mailchimp_Plugin = new Deli_Mailchimp_Plugin();
?>
