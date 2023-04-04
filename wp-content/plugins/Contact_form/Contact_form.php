<?php
require_once ABSPATH . 'wp-includes/pluggable.php';
/*
Plugin Name: Contact-Form
Plugin URI: http://www.wordpress.com
Description: Plugin de formulaire de contact personnalisé
Version: 1.0
Author: Nouhaila
Author URI: http://www.wordpress.com
License: GPL2
*/

if (isset($_POST['submit'])) {
    mon_plugin_validate();
}
function contact_us_plugin_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . "contact_us_messages";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nom VARCHAR(50) NOT NULL,
        email VARCHAR(50) NOT NULL,
        sujet VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'contact_us_plugin_activation' );

// Drop the database table on plugin deactivation
function contact_us_plugin_deactivation() {
    global $wpdb;
    $table_name = $wpdb->prefix . "contact_us_messages";
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}
register_deactivation_hook( __FILE__, 'contact_us_plugin_deactivation' );


// Add the plugin menu to the WordPress admin dashboard
function contact_us_plugin_menu() {
    add_menu_page(
        'Contact Us Plugin',
        'Contact Us',
        'manage_options',
        'contact-us-plugin',
        'list_received_emails',
        'dashicons-email'
    );
}
add_action( 'admin_menu', 'contact_us_plugin_menu' );
function list_received_emails()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT * FROM `wp_contact_us_messages` ;");
    ?>

    <h5>Contact emails</h5>
    <table class="table">
        <?php if (count($results) < 1) { ?>
            <div class="alert alert-danger">
                you do not have any incoming messages yet!
            </div>
        <?php } else { ?>
            <tr>
                <th>#</th>
                <th>Email</th>
                <th>Name</th>
                <th>Sujet</th>
                <th>Message</th>
            </tr>
        <?php }
        foreach ($results as $entry) { ?>
            <tr>
                <td><?= $entry->id ?></td>
                <td><?= $entry->email ?></td>
                <td><?= $entry->nom ?></td>
                <td><?= $entry->sujet ?></td>
                <td><?= $entry->message ?></td>
            </tr>
        <?php } ?>

    </table>
<?php
}
function mon_plugin_form()
{
?>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="mon_plugin_submit">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom :</label>
            <input type="text" name="nom" id="nom" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" required>
            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
        </div>
        <div class="mb-3">
            <label for="sujet" class="form-label">Sujet :</label>
            <input type="text" class="form-control" name="sujet" id="sujet" required>
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message :</label>
            <textarea name="message" class="form-control" id="message" rows="5" required></textarea>
        </div>
        <div>
            <input type="submit" name="submit" value="Envoyer">
        </div>
    </form>
<?php
}

function mon_plugin_validate()
{ global $wpdb;
    if (isset($_POST['submit'])) {
        $nom = sanitize_text_field($_POST['nom']);
        $email = sanitize_text_field($_POST['email']);
        $sujet = sanitize_text_field($_POST['sujet']);
        $content = sanitize_text_field($_POST['message']);
        if (preg_match('/^[a-zA-Z ]+$/', $nom) && filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^[a-zA-Z0-9\'\.\-\?\!\&\,\s]+$/', $sujet) && preg_match('/^[a-zA-Z0-9\'\.\-\?\!\&\,\s]+$/', $content)) {
            // Envoyer le message
            $table_name = $wpdb->prefix . 'contact_us_messages';
            $data = array(
                'name' => $nom,
                'email' => $email,
                'subject' => $sujet,
                'message' => $content
            );
            $format = array( '%s', '%s', '%s', '%s' );
            $wpdb->insert( $table_name, $data, $format );
            $message = $nom . ' - ' . $email . ' - ' . $content;
            wp_mail('khaoutinouhaila@gmail.com', $sujet, $message);
            echo '<div class="notice notice-success"><p>Votre message a bien été envoyé.</div>';

        } else {
            echo '<div class="notice notice-danger"><p>Votre message a echoué.</div>';
        }
        // $url = $_SERVER['REQUEST_URI'];
        // wp_redirect( $url );
    }
}
add_action( 'admin_post_contact_us_plugin_submit', 'mon_plugin_validate' );
add_action( 'admin_post_nopriv_contact_us_plugin_submit', 'mon_plugin_validate' );
// add_shortcode('valide', 'mon_plugin_validate');

function contact_form_plugin_styles()
{
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css');
}
add_action('wp_enqueue_scripts', 'contact_form_plugin_styles');
// Add the shortcode for the contact form
function contact_us_plugin_shortcode() {
    ob_start();
    mon_plugin_form();
    return ob_get_clean();
}
add_shortcode( 'contact_us_form', 'contact_us_plugin_shortcode' );
?>