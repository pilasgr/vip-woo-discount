<?php
/*
Plugin Name: VIP Woo Discount
Description: Προσθέτει έκπτωση βάσει ρόλου χρήστη στα προϊόντα του WooCommerce.
Version: 1.2
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
    <style>
        .discount-field {
            display: block;
            margin: 5px 0;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            width: 300px;
        }
        .discount-field input {
            width: 50px;
            text-align: right;
            margin-left: 10px;
        }
        .discount-table {
            width: 100%;
            border-collapse: collapse;
        }
        .discount-table th, .discount-table td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            text-align: left;
        }
    </style>
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
            settings_fields('vip_woo_new_role_options');
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
    register_setting('vip_woo_discount_options', 'b2b_discounts');
    register_setting('vip_woo_new_role_options', 'new_role_title', 'vip_woo_create_new_role');

    // Κύριες Ρυθμίσεις
    add_settings_section('vip_woo_discount-main', 'Εισάγετε τις επιλογές σας', null, 'vip-woo-discount-main');
    add_settings_field('b2b_discounts', 'Ρόλοι και έκπτωση', 'b2b_discounts_callback', 'vip-woo-discount-main', 'vip_woo_discount-main');

    // Ρυθμίσεις Νέου Ρόλου
    add_settings_section('vip_woo_discount-new-role', 'Εισάγετε τον τίτλο του νέου ρόλου', null, 'vip-woo-discount-new-role');
    add_settings_field('new_role_title', 'Τίτλος νέου ρόλου', 'new_role_title_callback', 'vip-woo-discount-new-role', 'vip_woo_discount-new-role');
}
add_action('admin_init', 'vip_woo_discount_settings');

function b2b_discounts_callback() {
    global $wp_roles;
    $roles = $wp_roles->get_names();
    $discounts = get_option('b2b_discounts', array());

    echo '<table class="discount-table">';
    echo '<thead><tr><th>Ρόλος</th><th>Έκπτωση (%)</th></tr></thead>';
    echo '<tbody>';

    foreach ($roles as $role_value => $role_name) {
        $discount = isset($discounts[$role_value]) && !empty($discounts[$role_value]) ? $discounts[$role_value] : '0';
        echo "<tr><td>" . esc_html($role_name) . "</td><td><input type='text' name='b2b_discounts[" . esc_attr($role_value) . "]' value='" . esc_attr($discount) . "' /></td></tr>";
    }

    echo '</tbody></table>';
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
        $discounts = get_option('b2b_discounts', array());

        foreach ($user->roles as $role) {
            if (isset($discounts[$role])) {
                $discount_percentage = floatval($discounts[$role]);
                if (is_numeric($discount_percentage) && $discount_percentage > 0) {
                    $discount = ($price * $discount_percentage) / 100;
                    return $price - $discount;
                }
            }
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


// Συνάρτηση για την εμφάνιση των πληροφοριών του ρόλου και της έκπτωσης
function display_role_and_discount_on_my_account() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $discounts = get_option('b2b_discounts', array());

        // Έναρξη της στοίχισης
        echo '<div class="account-role-discount">';
        
        // Προσθέστε την κεφαλίδα "Προνόμια χρήστη" εδώ
        echo '<strong><font color="#000">Προνόμια χρήστη</font></strong>';

        foreach ($user->roles as $role) {
            $role_name = translate_user_role($role);
            $discount_percentage = isset($discounts[$role]) ? floatval($discounts[$role]) : 0;

            echo '<div class="account-info-item"><span class="account-info-label">Ρόλος:</span> <span class="account-info-value">' . esc_html($role_name) . '</span></div>';
            echo '<div class="account-info-item"><span class="account-info-label">Έκπτωση που δικαιούστε:</span> <span class="account-info-value">' . esc_html($discount_percentage) . '%</span></div>';
        }

        // Λήξη της στοίχισης
        echo '</div>';
    }
}


// Συνδέει τη συνάρτηση στο hook για την εμφάνιση στη σελίδα "My Account"
add_action('woocommerce_account_dashboard', 'display_role_and_discount_on_my_account');

// Προσθήκη CSS για την εμφάνιση
function add_role_discount_css() {
    echo '<style>
        .account-role-discount {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            margin-top: 50px;
        }
        .account-info-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e0e0e0;
            padding: 10px 0;
        }
        .account-info-label {
            font-weight: bold;
        }
        .account-info-value {
            color: #333;
        }
    </style>';
}

add_action('wp_head', 'add_role_discount_css');
