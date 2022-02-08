<?php
/**
 * Plugin Name: TextRuPosting
 * Plugin URI: https://github.com/ModescoIT/TextRuPosting
 * Description: Плагин для взаимодействия WP-сайта с биржей контента text.ru
 * Version: 1.0.0
 * Author: text.ru
 * Author URI: http://text.ru
 */

include_once __DIR__ . '/TextRuAdminPage.php';
include_once __DIR__ . '/TextruOrdersManager.php';
include_once __DIR__ . '/TextRuAntiplagiat.php';

new TextRuAdminPage();
new TextruOrdersManager();

register_activation_hook(__FILE__, 'textru_create_plugin_tables');

/**
 * Создаёт таблицу БД для хранения данных импорта статей с биржи контента
 *
 */
function textru_create_plugin_tables()
{
    global $wpdb;

    // Для версий 3.5 и выше
    $charsetCollate = $wpdb->get_charset_collate();

    $query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}textru_orders (
        id INT(11) UNSIGNED auto_increment PRIMARY KEY,
        order_id INT(11) UNSIGNED NOT NULL,
        post_id BIGINT(20) UNSIGNED NULL,
        number INT(11) UNSIGNED NOT NULL,
        title VARCHAR (255),
        text VARCHAR (255),
        created_at DATETIME NOT NULL,
        published_at DATETIME NULL,
        INDEX(order_id) 
    )" . $charsetCollate;

    $wpdb->query($query);

    update_option(TextRuAdminPage::OPTION_NAME, '');
}

/**
 * Добавляет элемент 'Проверить на уникальность' под заголовком записи в таблице записей в админ-панели
 *
 */
add_filter( 'post_row_actions', 'post_actions_antiplagiat', 10, 2 );
function post_actions_antiplagiat( $actions, $post )
{
    $url = add_query_arg(
        array(
            'post' => $post->ID,
            'action' => 'showRes',
        )
    );
    $actions['antiplagiat'] =  '<a href="' . $url . '">Проверить на уникальность</a>';
    return $actions;
}
add_action( 'showRes', array('TextRuAntiplagiat', 'showRes'));
do_action('showRes');

