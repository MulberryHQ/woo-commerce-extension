/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @version 1.0.0
 * @copyright Copyright (c) 2018 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

jQuery(document).ready(function() {
    /**
     * Register Mulberry library
     */
    if (window.mulberryConfigData) {
        var element = document.createElement('script'),
            scriptTag = document.getElementsByTagName('script')[0],
            mulberryUrl = window.mulberryConfigData.mulberryUrl;

        element.async = true;
        element.src = mulberryUrl + '/plugin/static/js/mulberry.js';

        scriptTag.parentNode.insertBefore(element, scriptTag);
    }
});
