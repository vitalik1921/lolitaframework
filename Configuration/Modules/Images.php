<?php
namespace redbrook\LolitaFramework\Configuration\Modules;

use \redbrook\LolitaFramework\Core\HelperString as HelperString;
use \redbrook\LolitaFramework\Configuration\Configuration as Configuration;
use \redbrook\LolitaFramework\Configuration\IModule as IModule;

class Images implements IModule
{
    /**
     * Images class constructor
     *
     * @param array $data engine data.
     */
    public function __construct($data = null)
    {
        $this->data = (array) $data;
        $this->make();
    }

    /**
     * Add custom image sizes.
     *
     * @return \Themosis\Configuration\Images
     */
    public function make()
    {
        // Add registered image sizes.
        $this->addImages();

        // Add sizes to the media attachment settings dropdown list.
        add_filter('image_size_names_choose', array($this, 'addImagesToDropDownList'));

        return $this;
    }

    /**
     * Loop through the registered image sizes and add them.
     *
     * @return void
     */
    private function addImages()
    {
        foreach ($this->data as $slug => $properties) {
            list($width, $height, $crop) = $properties;
            add_image_size($slug, $width, $height, $crop);
        }
    }

    /**
     * Add image sizes to the media size dropdown list.
     *
     * @param array $sizes The existing sizes.
     * @return array
     */
    public function addImagesToDropDownList(array $sizes)
    {
        $new = array();

        foreach ($this->data as $slug => $properties) {
            // If no 4th option, stop the loop.
            if (4 !== count($properties)) {
                continue;
            }

            // Grab last property
            $show = array_pop($properties);

            // Allow true or string value.
            // If string, use it as display name.
            if ($show) {
                if (is_string($show)) {
                    $new[ $slug ] = $show;
                } else {
                    $new[ $slug ] = $this->label($slug);
                }
            }
        }

        return array_merge($sizes, $new);
    }

    /**
     * Clean the image slug for display.
     * Remove '-', '_' and set first character to uppercase.
     *
     * @param type $text The text to clean.
     * @return string
     */
    private function label($text)
    {
        return ucwords(str_replace(array('-', '_'), ' ', $text));
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
