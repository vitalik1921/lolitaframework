<?php
namespace redbrook\LolitaFramework\Widgets\SubscribeForm;

use \redbrook\LolitaFramework\Widgets\AbstractWithControls\AbstractWithControls as AbstractWithControls;
use \redbrook\LolitaFramework\Core\View as View;
use \redbrook\LolitaFramework\Core\HelperArray as HelperArray;
use \redbrook\LolitaFramework\Widgets\SubscribeForm\vendor\DrewM\MailChimp\MailChimp as MailChimp;

class SubscribeForm extends AbstractWithControls{
    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
            __('Lolita subscribe form', 'lolita'),
            array('description' => __('Subscribe form widget', 'lolita'))
        );
        add_action('wp_enqueue_scripts', array(&$this, 'addScriptsAndStyles'));
        add_action('admin_enqueue_scripts', array(&$this, 'adminAddScriptsAndStyles'));
        add_action( 'wp_ajax_lolita_subscribe', array(&$this, 'subscribe'));
        add_action( 'wp_ajax_nopriv_lolita_subscribe', array(&$this, 'subscribe'));
    }

    public function test()
    {
        $mail_chimp = new MailChimp('806a154965068e03f0bbd93efbe23be0-us12');
        $result = $mail_chimp->post(
            "lists/777377f75b/members", 
            [
                'email_address' => 'mymom@gmail.com',
                'status'        => 'subscribed',
            ]
        );

        echo '<pre>';
        var_dump($result, $mail_chimp);
        echo '</pre>';
        die();
    }

    /**
     * Subscribe our user
     */
    public function subscribe()
    {
        $response = $_POST;
        if (array_key_exists('type', $response) && '' !== $response['type']) {
            $method = sprintf('subscribe%s', ucwords($response['type']));
            if (method_exists($this, $method)) {
                $this->$method($response);
            }
        } else {
            $result = false;
        }
        
        if ( true === $result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Subscribe default type
     * @param  array $response request response.
     * @return boolean result.
     */
    public function subscribeDefault($response)
    {
        return wp_mail(
            get_bloginfo('admin_email'),
            'New subscriber',
            $reponse['value'] . '. Thank you for leave us your email!'
        );
    }

    /**
     * Subscribe mailchimp type
     * @param  array $response request response.
     * @return boolean result.
     */
    public function subscribeMailchimp($response)
    {
        $mail_chimp = new MailChimp($response['mailchimp_api_key']);
        $result = $mail_chimp->post(
            sprintf('lists/%s/members', $response['mailchimp_list_id']),
            array(
                'email_address' => $response['value'],
                'status'        => 'subscribed',
            )
        );
        
    }

    /**
     * Add scripts and styles
     */
    public function addScriptsAndStyles()
    {
        $assets = $this->getURL() . 'assets' . DS;
        // ==============================================================
        // Scripts
        // ==============================================================
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'lolita-widget-subscribe-form',
            $assets . 'js' . DS . 'lolita_widget_subscribe_form.js',
            array('jquery'),
            false,
            true
        );

        // ==============================================================
        // Localize
        // ==============================================================
        wp_localize_script(
            'lolita-widget-subscribe-form',
            'lolita_widget_subscribe_form',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => LF_NONCE,
            )
        );
    }

    public function adminAddScriptsAndStyles()
    {
        $assets = $this->getURL() . 'assets' . DS;
        // ==============================================================
        // Scripts
        // ==============================================================
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'lolita-widget-subscribe-form',
            $assets . 'js' . DS . 'admin_lolita_widget_subscribe_form.js',
            array('jquery'),
            false,
            true
        );
    }

    /**
     * Get controls data
     * @return array data to generate controls.
     */
    public function getControlsData()
    {
        return array(
            array(
                'name'     => 'title',
                '__TYPE__' => 'Input',
                'type'     => 'text',
                'label'    => 'Title',
            ),
            array(
                'name'     => 'type',
                '__TYPE__' => 'Select',
                'label'    => 'Subscribe form type',
                'options'  => array(
                    'default'   => 'Default',
                    'mailchimp' => 'Mailchimp',
                )
            ),
            array(
                'name'     => 'mailchimp_api_key',
                '__TYPE__' => 'Input',
                'type'     => 'text',
                'label'    => 'Mailchimp API key',
            ),
            array(
                'name'     => 'mailchimp_list_id',
                '__TYPE__' => 'Input',
                'type'     => 'text',
                'label'    => 'Mailchimp list id',
            ),
            array(
                'name'     => 'description',
                '__TYPE__' => 'Textarea',
                'type'     => 'text',
                'label'    => 'Description',
                'rows'     => '10',
            ),
            array(
                'name'     => 'success_message',
                '__TYPE__' => 'Textarea',
                'type'     => 'text',
                'label'    => 'Sueccess message',
                'rows'     => '10',
            ),
            array(
                'name'     => 'error_message',
                '__TYPE__' => 'Textarea',
                'type'     => 'text',
                'label'    => 'Error message',
                'rows'     => '10',
            ),
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        $instance['success_message'] = HelperArray::get(
            $instance,
            'success_message',
            'Message sent successfully.'
        );
        $instance['error_message'] = HelperArray::get(
            $instance,
            'success_message',
            'Message not sent. Please contact the administrator for help.'
        );
        $instance['mailchimp_api_key'] = HelperArray::get(
            $instance,
            'mailchimp_api_key',
            ''
        );
        $instance['mailchimp_list_id'] = HelperArray::get(
            $instance,
            'mailchimp_list_id',
            ''
        );
        $instance['type'] = HelperArray::get(
            $instance,
            'type',
            'default'
        );
        echo View::make(
            dirname(__FILE__) . DS . 'views' . DS . $this->id_base . '.php',
            array(
                'instance' => $instance,
                'args'     => $args,
                'id_base'  => $this->id_base,
            )
        );
    }
}
