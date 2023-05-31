<?php

/*
Plugin Name: Contact Form
Description: Just a simple contact form plugin for WordPress.
Version: 1.0.0
Author: Lamine
Author URI: https://www.example.com
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Enregistrement et chargement du fichier CSS
function load_contact_form_styles()
{
    wp_enqueue_style('contact-form-styles', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'load_contact_form_styles');

// Activation du plugin
function contact_form_activate()
{
    // Création de la table dans la base de données lors de l'activation
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_form_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        subject VARCHAR(255) NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'contact_form_activate');

// Désactivation du plugin
function contact_form_deactivate()
{
    // Suppression de la table de la base de données lors de la désactivation
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_form_data';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_deactivation_hook(__FILE__, 'contact_form_deactivate');


// Fonction qui affiche le formulaire de contact
function contact_form_shortcode()
{

    $errors = array();
    $subject = '';
    $first_name = '';
    $last_name = '';
    $email = '';
    $message = '';

    if (!empty($_POST)) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_data';

        // Récupération des données soumises
        $subject = sanitize_text_field($_POST['subject']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_text_field($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);

        if (empty($subject)) {
            $errors['subject'] = 'Le champ sujet est requis.';
        }

        if (empty($first_name)) {
            $errors['first_name'] = 'Le champ prénom est requis.';
        }

        if (empty($last_name)) {
            $errors['last_name'] = 'Le champ nom est requis.';
        }

        if (empty($email)) {
            $errors['email'] = 'Le champ email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Le champ email est invalide.';
        }

        if (empty($message)) {
            $errors['message'] = 'Le champ message est requis.';
        }

        // S'il n'y a pas d'erreurs, enregistrez les données dans la base de données
        if (empty($errors)) {

            // Insertion des données dans la base de données
            $wpdb->insert(
                $table_name,
                array(
                    'subject' => $subject,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'message' => $message
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                )
            );

            // Redirection après l'envoi du formulaire
            wp_redirect(esc_url(home_url('/message-envoye')));
            exit();
        }
    }

    // Code HTML pour le formulaire de contact
    $form_html = '
        <form method="post" class="contact-form">
            <label for="subject">Sujet :</label>
            <input type="text" name="subject" id="subject" value="' . $subject . '">
            ' . (isset($errors['subject']) ? '<p class="error-message">' . $errors['subject'] . '</p>' : '') . '
            
            <label for="first_name">Prénom :</label>
            <input type="text" name="first_name" id="first_name" value="' . $first_name . '">
            ' . (isset($errors['first_name']) ? '<p class="error-message">' . $errors['first_name'] . '</p>' : '') . '
            
            <label for="last_name">Nom :</label>
            <input type="text" name="last_name" id="last_name" value="' . $last_name . '">
            ' . (isset($errors['last_name']) ? '<p class="error-message">' . $errors['last_name'] . '</p>' : '') . '
            
            <label for="email">Email :</label>
            <input type="email" name="email" id="email" value="' . $email . '">
            ' . (isset($errors['email']) ? '<p class="error-message">' . $errors['email'] . '</p>' : '') . '
            
            <label for="message">Message :</label>
            <textarea name="message" id="message">' . $message . '</textarea>
            ' . (isset($errors['message']) ? '<p class="error-message">' . $errors['message'] . '</p>' : '') . '
            
            <input type="submit" name="contact_form_submit" value="Envoyer">
        </form>
    ';

    return $form_html;
}

// Enregistrement de la page d'administration personnalisée
function register_contact_form_admin_page()
{
    add_menu_page(
        'Messages des internautes', // Titre de la page
        'Messages', // Titre du menu
        'manage_options', // Capacité requise pour voir le menu
        'contact-form-messages', // Slug de la page
        'display_contact_form_messages', // Fonction de rappel pour afficher la page
        'dashicons-email'
    );
}

// Fonction pour récupérer les messages des internautes depuis la base de données
function get_contact_form_messages()
{
    global $wpdb;

    // Nom de la table dans la base de données (à adapter selon votre structure)
    $table_name = $wpdb->prefix . 'contact_form_data';

    // Récupération des messages
    $messages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

    return $messages;
}

// Fonction de rappel pour afficher les messages des internautes
function display_contact_form_messages()
{
    // Récupérez les messages des internautes depuis la base de données
    $messages = get_contact_form_messages();

    // Affichez les messages dans un tableau ou d'une autre manière
    echo '<div class="wrap">';
    echo '<h1>Messages des internautes</h1>';

    if (empty($messages)) {
        echo '<p>Aucun message trouvé.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Nom</th>';
        echo '<th>Email</th>';
        echo '<th>Sujet</th>';
        echo '<th>Date</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($messages as $message) {
            echo '<tr>';
            echo '<td>' . esc_html($message['first_name'] . ' ' . $message['last_name']) . '</td>';
            echo '<td>' . esc_html($message['email']) . '</td>';
            echo '<td>' . esc_html($message['subject']) . '</td>';
            echo '<td>' . esc_html($message['created_at']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    echo '</div>';
}


// Enregistrement du shortcode
function register_contact_form_shortcode()
{
    add_shortcode('contact_form', 'contact_form_shortcode');
    add_action('admin_menu', 'register_contact_form_admin_page');
}

add_action('init', 'register_contact_form_shortcode');
