<?php
namespace ECG\LolitaFramework\Configuration\Modules;

use \ECG\LolitaFramework\Core\HelperString as HelperString;
use \ECG\LolitaFramework\Core\HelperArray as HelperArray;
use \ECG\LolitaFramework\Core\GlobalLocator as GlobalLocator;
use \ECG\LolitaFramework\Core\View as View;
use \ECG\LolitaFramework\Configuration\Configuration as Configuration;
use \ECG\LolitaFramework\Configuration\IModule as IModule;
use \ECG\LolitaFramework\Controls\Controls as Controls;

class MetaBoxes implements IModule
{
    /**
     * Nonce
     */
    const NONCE = 'LolitaFramework';

    /**
     * Metaboxes class constructor
     *
     * @param array $data engine data.
     */
    public function __construct($data = null)
    {
        $this->data = (array) $data;
        $this->prepareData();
        $this->init();
    }

    /**
     * Init hooks
     */
    private function init()
    {
        add_action('add_meta_boxes', array($this, 'addMetaBoxes'), 10, 2);
        add_action('save_post', array($this, 'saveMeta'), 10, 2);
    }

    /**
     * Prepare data before render
     * @return MetaBoxes $this.
     */
    private function prepareData()
    {
        $default = $this->getDefaults();
        foreach ($this->data as $slug => &$data) {
            $data = array_merge($default, (array) $data);
            if (array_key_exists('controls', $data)) {
                $controls = new Controls;
                $controls->generateControls((array) $data['controls']);

                $data['callback']      = array($this, 'renderControls');
                $data['callback_args'] = array(
                    'controls' => $controls,
                );
                $data['collection'] = $controls;
            }
        }
        return $this;
    }

    /**
     * Get default add_meta_box parameters
     * @return [array] default parameters.
     */
    private function getDefaults()
    {
        return array(
            'title'         => __('My meta box', 'lolita'),
            'callback'      => array($this, 'defaultCallback'),
            'screen'        => 'post',
            'context'       => 'normal',
            'priority'      => 'low',
            'callback_args' => null,
        );
    }

    /**
     * Default callback
     */
    public function defaultCallback()
    {
        echo View::make(Configuration::getFolder() . DS . 'views' . DS . 'meta_box_default.php');
    }

    /**
     * Meta box controls
     * @param  object $post The post object currently being edited.
     * @param  array $metabox Specific information about the meta box being loaded.
     * @return void
     */
    public function renderControls($post, $metabox)
    {
        $controls     = $metabox['args']['controls'];
        $metabox_name = $metabox['id'];
        $meta_data    = get_post_meta($post->ID, $metabox_name, true);

        if ($controls instanceof Controls) {

            foreach ($controls->collection as $control) {
                // ==============================================================
                // Set name with prefix
                // ==============================================================
                $control->setName(
                    $this->controlNameWithPrefix($metabox_name, $control->getName())
                );
                // ==============================================================
                // Set new value
                // ==============================================================
                $control->setValue(get_post_meta($post->ID, $control->getName(), true));

                // ==============================================================
                // Fill new attributes
                // ==============================================================
                $control->setAttributes(
                    array(
                        'class' => 'widefat',
                        'id'    => $control->getName() . '-id',
                    )
                );
            }

            wp_nonce_field(self::NONCE, self::NONCE);
            echo $controls->render(
                Configuration::getFolder() . DS . 'views' . DS . 'meta_box_with_controls.php',
                Configuration::getFolder() . DS . 'views' . DS . 'meta_box_row.php'
            );
        } else {
            throw new \Exception('Wront $controls object');
            
        }
    }

    /**
     * Add prefix to name
     * @param  string $prefix prefix.
     * @param  string $name   name.
     * @return string         name with prefix.
     */
    public function controlNameWithPrefix($prefix, $name)
    {
        return sprintf(
            '%s_%s',
            $prefix,
            $name
        );
    }

    /**
     * Save row meta
     * @param  integer $post_id post id.
     * @param  string $post     post type.
     */
    public function saveMeta($post_id, $post = '')
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], self::NONCE)) {
            return;
        }
        if (!current_user_can('edit_posts')) {
            return;
        }
        if (!is_object($post)) {
            $post = get_post();
        }
        foreach ($this->data as $slug => $data) {
            $this->toggleSave($slug, $post_id);

            if (array_key_exists('controls', $data)) {
                foreach ($data['controls'] as $name => $arguments) {
                    $control_name = $this->controlNameWithPrefix($slug, $name);
                    $this->toggleSave($control_name, $post_id);
                }
            }
        }
    }

    /**
     * Toggle save
     * @param  string $name    $_POST key.
     * @param  string $post_id post id.
     * @return boolean true = saved / false = deleted.
     */
    private function toggleSave($name, $post_id)
    {
        if (array_key_exists($name, $_POST)) {
            update_post_meta($post_id, $name, $_POST[ $name ]);
            return true;
        } else {
            delete_post_meta($post_id, $name);
            return false;
        }
    }

    /**
     * Add metaboxes
     */
    public function addMetaBoxes()
    {
        foreach ($this->data as $slug => $data) {
            add_meta_box(
                $slug,
                $data['title'],
                $data['callback'],
                $data['screen'],
                $data['context'],
                $data['priority'],
                $data['callback_args']
            );
        }
    }

    /**
     * Module priority
     * @return [int] priority, the smaller number the faster boot.
     */
    public static function getPriority()
    {
        return Configuration::DEFAULT_PRIORITY;
    }
}
