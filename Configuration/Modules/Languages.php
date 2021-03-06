<?php
namespace redbrook\LolitaFramework\Configuration\Modules;

use \redbrook\LolitaFramework\Core\HelperString as HelperString;
use \redbrook\LolitaFramework\Configuration\Init as Init;
use \redbrook\LolitaFramework\Configuration\Configuration as Configuration;
use \redbrook\LolitaFramework\Configuration\IModule as IModule;

class Languages extends Init implements IModule
{
    /**
     * Languages class constructor
     *
     * @param array $data engine data.
     */
    public function __construct($data = null)
    {
        $this->data        = (array) $data;
        $this->init_action = 'after_setup_theme';
        $this->init();
    }

    /**
     * Run by the 'init' hook.
     * Execute the "add_theme_support" function from WordPress.
     *
     * @return void
     */
    public function install()
    {
        if (is_array($this->data) && ! empty($this->data)) {
            foreach ($this->data as $domain => $path) {
                /*
                 * Make theme available for translation.
                 * Translations can be filed in the /languages/ directory.
                 */
                load_theme_textdomain($domain, HelperString::compileVariables($path));
            }
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
