<?php
/**
Plugin Name: Deli Mailchimp API Integration
Description: Display forms to add subscriber handled by Mailchimp v3 API
Version: 0.1.1
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
Text Domain: deli-mailchimp
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die; 

/**
 * @example     into template page use : echo do_shortcode('[formulaire-subscribe]');
 * @example     As shortcode use : [formulaire-subscribe][/formulaire-subscribe]
 *
 * @uses        MAILCHIMP V3
 *              https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
 */

class Deli_Mailchimp_Plugin
{
    public function __construct()
    {
        // CSS 
        add_action( 'wp_enqueue_scripts', array($this, "scripts_styles"));

        // TRAITEMENT $_POST
        add_action( 'wp_head', array($this , 'subscribe_do') );

        // SHORTCODE
        add_shortcode( 'formulaire-subscribe', array( $this , 'shortcode_formulaire' ) );

        // ADMIN PAGE REGISTER
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // ADMIN SETTINGS REGISTER
        add_filter( 'admin_init' , array( $this, 'register_settings_fields' ) );

        // GLOBAL init
        $this -> subscribe_do = false;

        // STYLING CSS classes (optional customisation)
        $this -> formClass          = 'mailchimp-subscribe nl-embed';
        $this -> textSuccessClass   = 'text-success';
        $this -> textErrorClass     = 'text-danger';
        $this -> formRowClass       = 'input-wrapper';
        $this -> submitBtnClass     = 'submit';
        $this -> labelRequiredClass     = 'label-required';
        $this -> formID             = 'mailchimp-subscribe-form';

        // CREDENTIALS (required)
        // Cle d'api a generer sur MC dev dashboard
        $this -> apiKey = $this -> get_api_key();

        // ID de la liste MC dans laquelle sera inscrit l'abonné
        //$this -> listId = 'b34a75e04b';
        $this -> listId = $this -> get_list_id();

        // Data center
        $this -> dataCenter = substr( $this -> apiKey ,strpos( $this -> apiKey ,'-')+1);
    }



    // RENDERING

    // CS JS 
    public function scripts_styles(){
        wp_enqueue_style( 
            'deli-mailchimp-css', 
            plugin_dir_url(__FILE__) . '/css/styles.css' , 
            '' ,  
            strtotime(date('Y-m-d H:i:s') )  , 
            'screen' );
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
                <p class="<?php echo $this -> labelRequiredClass; ?>">(*) Champs requis</p>
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
    // RENDER STRINGS
    private function l_($string,$return){

        // All plugins strings
        $strings = array(
            'bravo'             => 'Bravo !',
            'votre-e-mail'      => 'Votre e-mail *',
            'votre-nom'         => 'Votre nom *',
            'votre-prenom'      => 'Votre pr&eacute;nom *',
            'recevoir-les-news' => 'S\'abonner',
            'oups-error'        => 'Oups ! Une erreur s\'est produite. R&eacute;essayez.',
            'err-email'         => 'V&eacute;rifiez votre e-mail',
            'err-firstname'     => 'Merci de saisir votre pr&eacute;nom',
            'err-lastname'      => 'Merci de saisir votre nom',
            );

        // Return (string)
        return $strings[$string];
    }
    // GET HTML TEMPLATE 
    private function get_template_html( $template_name, $attributes = null ) {
        if ( ! $attributes ) {
            $attributes = array();
        }

        ob_start();
        require( 'templates/' . $template_name . '.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }





    // DATAS 

    // DO $_POST :  subscribe un nouvel abonne
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
        $do_api_get = $this -> do_api_get_subscribe_do();

        // Return
        if(200==$do_api_get -> httpCode)
            $this -> subscribe_do -> results = json_decode($do_api_get -> result);
        else
            $this -> subscribe_do -> feedbacks = array($this -> l_('oups-error','return'));

        return $this -> subscribe_do;
    }
    // REQUESTS TO API
    // Add a new subcriber
    private function do_api_get_subscribe_do(){

        // PARAMS REQUEST API
        $memberId   = md5(strtolower($_POST['email']));
        $url        = 'https://' . $this -> dataCenter . '.api.mailchimp.com/3.0/lists/' . $this -> listId . '/members/' . $memberId;
        $json = json_encode([
            'email_address' => $_POST['email'],
            'status'        => "subscribed", // "subscribed","unsubscribed","cleaned","pending"
            'merge_fields'  => [
                'FNAME'     => $_POST['firstname'],
                'LNAME'     => $_POST['lastname']
            ]
        ]);

        // Requete via CURL 
        $return = $this -> do_api_get($url , $json , 'PUT');
        
        return $return;
    }
    // Get list data
    private function do_api_get_list(){

        // PARAMS REQUEST API
        $memberId   = md5(strtolower($_POST['email']));
        $url        = 'https://' . $this -> dataCenter . '.api.mailchimp.com/3.0/lists/' . $this -> listId;
        $json = json_encode();

        // Requete via CURL 
        $return = $this -> do_api_get($url , $json , 'GET');

        return $return;
    } 
    // Get subscribers from list ID
    private function do_api_get_subscribers(){

        // PARAMS REQUEST API
        $memberId   = md5(strtolower($_POST['email']));
        $url        = 'https://' . $this -> dataCenter . '.api.mailchimp.com/3.0/lists/' . $this -> listId . '/members?count=50';
        $json = json_encode();

        // Requete via CURL 
        $return = $this -> do_api_get($url , $json , 'GET');
               
        return $return;
    }        
    // REQUEST GET to API
    private function do_api_get( $url , $json , $method){

        if('' == $url  || '' ==  $method )
            return FALSE;

        // Requete via CURL
        $ch = curl_init($url);
        if($ch==FALSE)
            return FALSE;

        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $this -> apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        // Results
        $result     = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if(!$result || !$httpCode)
            return FALSE;

        // return
        $return -> result = $result;
        $return -> httpCode = $httpCode;    
        return $return;    
    }






    // SETTINGS

    // ADMIN PAGE REGISTER
    public function add_admin_menu() {
        add_menu_page( 
            'Mailchimp Wordpress',  
            'Mailchimp Wordpress', 
            'manage_options', 
            'plugin-admin-page', 
            array($this,'manage_plugin_admin_page'), 
            'dashicons-admin-tools', 
            90 );
    }
    public function manage_plugin_admin_page(){

        echo $this -> get_template_html( 'plugin-admin-page' );
    }

    // REGISTER the settings fields needed by the plugin.
    public function register_settings_fields() {

        // for the API keys
        register_setting( 'general', 'mailchimp-api-key' );

        add_settings_field(
            'mailchimp-api-key',
            '<label for="mailchimp-api-key">Mailchimp API key</label>',
            array( $this, 'render_mailchimp_api_key_field' ),
            'general' 
        );

        // for the list ID
        register_setting( 'general', 'mailchimp-list-id' );

        add_settings_field(
            'mailchimp-list-id',
            '<label for="mailchimp-list-id">Mailchimp List ID </label>',
            array( $this, 'render_mailchimp_list_id_field' ),
            'general' 
        );       
    }
    // RENDER settings fields
    public function render_mailchimp_api_key_field() {
        $value = get_option( 'mailchimp-api-key', '' );
        echo '<input type="text" id="mailchimp-api-key" name="mailchimp-api-key" value="' . esc_attr( $value ) . '" />';
    }    
    public function render_mailchimp_list_id_field() {
        $value = get_option( 'mailchimp-list-id', '' );
        echo '<input type="text" id="mailchimp-list-id" name="mailchimp-list-id" value="' . esc_attr( $value ) . '" />';
    }

    // GET CREDENTIALS
    private function get_api_key(){
        $get_option = get_option( 'mailchimp-api-key', '' );
        if('' != $get_option)
            return $get_option;

        return '';          
    }    
    private function get_list_id(){
        $get_option = get_option( 'mailchimp-list-id', '' );
        if('' != $get_option)
            return $get_option;

        return '';          
    }    
}




//
// Init du plugin dans variable globale
//
global $Deli_Mailchimp_Plugin;
$Deli_Mailchimp_Plugin = new Deli_Mailchimp_Plugin();
?>
