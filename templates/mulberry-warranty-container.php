<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined('ABSPATH') || exit;

global $product;

if (!$product->is_purchasable()) {
    return;
}

$settings = get_option('woocommerce_mulberry-warranty_settings');

if (!$settings['active'] === 'yes') {
    return;
}

$sku = $product->get_sku() ? $product->get_sku() : $product->get_id();
$gallery = [];
foreach ($product->get_gallery_image_ids() as $attachment_id) {
    $gallery[] = wp_get_attachment_url($attachment_id);
}

if ($product->is_in_stock()): ?>
    <div class="mulberry-container-wrapper">
        <div class="mulberry-inline-container"></div>
        <input type="hidden" id="warranty_hash" name="warranty[hash]" value=""/>
        <input type="hidden" id="warranty_sku" name="warranty[sku]" value="<?php echo esc_html($sku); ?>"/>
        <input type="hidden" id="warranty" name="mulberry_warranty" value=""/>

        <script type="text/javascript">
            window.mulberryProductData = {
                product: {
                    id: "<?php echo esc_html($sku); ?>",
                    title: "<?php echo $product->get_name(); ?>",
                    price: <?php echo $product->get_price(); ?>,
                    url: "<?php echo $product->get_permalink(); ?>"
                    //images: <?php //echo json_encode($gallery); ?>//,
                    //meta: {
                    //    breadcrumbs: <?php //echo $this->getBreadcrumbsInfo(); ?>//,
                    //},
                    //description: "<?php //echo wc_format_content($product->get_description()); ?>//"
                }
                //originalSku: "<?php //echo $product->get_sku(); ?>//",
                //originalPrice: <?php //echo (float) $product->get_price(); ?>//,
                //originalDescription: "<?php //echo wc_format_content($product->get_description()); ?>//"
            };

            window.mulberryConfigData = {
                "containerClass": "mulberry-inline-container",
                "magentoDomain": "<?php echo $_SERVER['SERVER_NAME']; ?>",
                "mulberryUrl": "<?php echo $settings['api_url']; ?>",
                "partnerUrl": "<?php echo $settings['partner_url']; ?>",
                "retailerId": "<?php echo $settings['retailer_id']; ?>",
                "publicToken": "<?php echo $settings['public_token']; ?>",
            };
        </script>
    </div>
<?php endif; ?>
