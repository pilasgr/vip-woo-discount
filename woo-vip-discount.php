<?php
/*
Plugin Name: VIP Woo Discount
Description: Προσθέτει έκπτωση βάσει ρόλου χρήστη στα προϊόντα του WooCommerce.
Version: 1.1
Author: Pilas.Gr - Go Brand Yourself
*/

// Δημιουργία του μενού στον πίνακα διαχείρισης
function vip_woo_discount_menu() {
    add_options_page('VIP Woo Discount Settings', 'VIP Woo Discount', 'manage_options', 'vip-woo-discount', 'vip_woo_discount_options_page');
}
add_action('admin_menu', 'vip_woo_discount_menu');

// Σελίδα ρυθμίσεων
function vip_woo_discount_options_page() {
    ?>
    <div class="wrap">
        <h2>VIP Woo Discount Ρυθμίσεις</h2>

        <!-- Ρυθμίσεις Έκπτωσης -->
        <form method="post" action="options.php">
            <?php
            settings_fields('vip_woo_discount_options');
            do_settings_sections('vip-woo-discount-main');
            submit_button('Αποθήκευση Ρυθμίσεων');
            ?>
        </form>

        <!-- Προσθήκη Νέου Ρόλου -->
        <h3>Προσθήκη Νέου Ρόλου</h3>
        <form method="post" action="options.php">
            <?php
            settings_fields('vip_woo_discount_options');
            do_settings_sections('vip-woo-discount-new-role');
            submit_button('Προσθήκη Ρόλου');
            ?>
        </form>

        <p style="text-align: center; margin-top: 50px; font-size: 20px;">Δημιουργήθηκε από το <a href="https://pilas.gr" target="_blank"><img width="70px" src="https://www.pilas.gr/sign/logo.png"/></a></p>
    </div>
    <?php
}

// Ρυθμίσεις και πεδία
function vip_woo_discount_settings() {
    register_setting('vip_woo_discount_options', 'b2b_discount_role');
    register_setting('vip_woo_discount_options', 'b2b_discount_percentage');
    register_setting('vip_woo_discount_options', 'new_role_title', 'vip_woo_create_new_role');

    // Κύριες Ρυθμίσεις
    add_settings_section('vip_woo_discount-main', 'Εισάγετε τις επιλογές σας', null, 'vip-woo-discount-main');
    add_settings_field('b2b_discount_role', 'Ρόλος με έκπτωση', 'b2b_discount_role_callback', 'vip-woo-discount-main', 'vip_woo_discount-main');
    add_settings_field('b2b_discount_percentage', 'Ποσοστό έκπτωσης', 'b2b_discount_percentage_callback', 'vip-woo-discount-main', 'vip_woo_discount-main');

    // Ρυθμίσεις Νέου Ρόλου
    add_settings_section('vip_woo_discount-new-role', 'Εισάγετε τον τίτλο του νέου ρόλου', null, 'vip-woo-discount-new-role');
    add_settings_field('new_role_title', 'Τίτλος νέου ρόλου', 'new_role_title_callback', 'vip-woo-discount-new-role', 'vip_woo_discount-new-role');
}
add_action('admin_init', 'vip_woo_discount_settings');

function b2b_discount_role_callback() {
    global $wp_roles;
    $roles = $wp_roles->get_names();
    $selected_role = esc_attr(get_option('b2b_discount_role', ''));

    echo "<select name='b2b_discount_role'>";
    foreach ($roles as $role_value => $role_name) {
        echo "<option value='" . esc_attr($role_value) . "' " . selected($selected_role, $role_value, false) . ">" . esc_html($role_name) . "</option>";
    }
    echo "</select>";
}

function b2b_discount_percentage_callback() {
    $setting = esc_attr(get_option('b2b_discount_percentage', ''));
    echo "<input type='text' name='b2b_discount_percentage' value='$setting' />";
}

function new_role_title_callback() {
    $setting = esc_attr(get_option('new_role_title', ''));
    echo "<input type='text' name='new_role_title' value='$setting' />";
}

// Δημιουργία του νέου ρόλου όταν αποθηκεύονται οι ρυθμίσεις
function vip_woo_create_new_role($new_role_title) {
    if (!empty($new_role_title) && !get_role($new_role_title)) {
        add_role($new_role_title, $new_role_title, array('read' => true));
    }
    return $new_role_title;
}

// Εφαρμογή της έκπτωσης στα προϊόντα βάσει του ρόλου και του ποσοστού
function apply_vip_woo_discount($price, $product) {
    if (!is_numeric($price)) {
        return $price; // Επιστροφή της αρχικής τιμής αν δεν είναι αριθμητική
    }

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $discount_role = get_option('b2b_discount_role');
        $discount_percentage = floatval(get_option('b2b_discount_percentage'));

        // Έλεγχος αν το ποσοστό έκπτωσης είναι αριθμητικό και μεγαλύτερο από 0
        if (!is_numeric($discount_percentage) || $discount_percentage <= 0) {
            return $price;
        }

        if (in_array($discount_role, $user->roles)) {
            $discount = ($price * $discount_percentage) / 100;
            return $price - $discount;
        }
    }
    return $price;
}

add_filter('woocommerce_product_get_price', 'apply_vip_woo_discount', 10, 2);
add_filter('woocommerce_product_get_regular_price', 'apply_vip_woo_discount', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'apply_vip_woo_discount', 10, 2);
add_filter('woocommerce_product_variation_get_price', 'apply_vip_woo_discount', 10, 2);
add_filter('woocommerce_product_get_sale_price', 'apply_vip_woo_discount', 10, 2);
add_filter('woocommerce_product_variation_get_sale_price', 'apply_vip_woo_discount', 10, 2);
