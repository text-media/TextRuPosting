<?php

include_once __DIR__ . '/TextRuApi.php';
include_once __DIR__ . '/TextruOrdersManager.php';

/**
 * Страница настроек в админке wordpress
 *
 */
class TextRuAdminPage
{
    const MENU_TITLE = 'Text.ru';

    const PAGE_NAME = 'text-ru-settings';
    const PAGE_TITLE = 'Text.ru - Настройки плагина автопостинга';
    const PAGE_CONTENT = 'Для того чтобы Text.ru мог обратиться к вашему сайту, нужно зарегистрировать 
свой токен доступа, который можно получить на странице <a href="https://text.ru/api-check" target="_blank">https://text.ru/api-check</a>. 
         Никому не сообщайте этот токен.';

    const OPTION_GROUP_NAME = 'textru_options_group';
    const OPTION_SECTION_NAME = 'textru_options_section';
    const OPTION_NAME = 'textru_token';
    const OPTION_TITLE = 'Токен: <span style="color: red;">*</span>';

    const OPTION_CUSTOMER_ID_NAME = 'textru_customer_id';
    const OPTION_SITE_ID_NAME = 'textru_site_id';

    const MESSAGE_NAME = 'textru_message';
    const MESSAGE_ERROR_EMPTY_TOKEN = 'Токен обязательно должен быть указан';
    const MESSAGE_ERROR_FORMAT_TOKEN = 'Ошибка в формате токена';
    const MESSAGE_UPDATED_TOKEN = 'Токен успешно сохранён';

    const ORDERS_PER_PAGE = 10;

    /**
     * Конструктор класса
     *
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuItem']);
        add_action('admin_init', [$this, 'registerOption']);
        add_action('update_option_' . self::OPTION_NAME, [$this, 'onUpdated'], 10, 2);
    }

    /**
     * Добавляет элемент в меню настроек wordpress
     *
     */
    public function addMenuItem(): void
    {
        add_options_page(self::PAGE_TITLE, self::MENU_TITLE, 'manage_options', self::PAGE_NAME, [$this, 'showPage']);
    }

    /**
     * Регистрирует опцию токена для возможности сохранения её в БД wordpress
     *
     */
    public function registerOption(): void
    {
        register_setting(self::OPTION_GROUP_NAME, self::OPTION_NAME, [$this, 'validate']);
        add_settings_section(self::OPTION_SECTION_NAME, '', '', self::PAGE_NAME);
        add_settings_field(
            self::OPTION_NAME,
            self::OPTION_TITLE,
            [$this, 'showField'],
            self::PAGE_NAME,
            self::OPTION_SECTION_NAME,
            ['label_for' => self::OPTION_NAME]);
    }

    /**
     * Выводит страницу настроек плагина
     *
     */
    public function showPage(): void
    {
        TextruOrdersManager::registerAssets();

        echo '
    <div class="wrap">
        <h1>' . self::PAGE_TITLE . '</h1>
        <p>' .self::PAGE_CONTENT . '</p>
        <form method="POST" action="options.php" enctype="multipart/form-data">';

        do_settings_sections(self::PAGE_NAME);
        settings_fields(self::OPTION_GROUP_NAME);
        submit_button();

        echo '
        </form>
    </div>';

        $option = (int)get_option(self::OPTION_CUSTOMER_ID_NAME);
        if ($option) {
            echo '
    <br>
    <table id="textru_table" class="widefat fixed">
        <thead>
            <tr>
                <th class="manage-column">Идентификатор<br>заказа</th>
                <th class="manage-column textru_title">Заголовок</th>
                <th class="manage-column">Дата</th>
                <th class="manage-column">Действия</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div id="textru_empty_table">
        <h4>Не найдено заказов для публикации.</h4>
        <p>Выберите ваш сайт при создании заказа и после принятия этого заказа он вместе с прикреплёнными текстами появится на этой странице.</p>
    </div>
    <br>
    <div id="textru_paginator">Страницы: <span></span></div>
';
        }
    }

    /**
     * Выводит поле ввода для токена
     *
     */
    public function showField(): void
    {
        echo '<input 
            id="' . self::OPTION_NAME . '" 
            name="' . self::OPTION_NAME . '"
            type="text" 
            value="' . esc_attr(get_option(self::OPTION_NAME)) . '" 
            class="regular-text"
        >';
    }

    /**
     * Валидирует токен при сохранении
     *
     * @param string $value
     *
     * @return string
     */
    public function validate(string $value): string
    {
        $validators = $this->getTokenValidators();

        foreach ($validators as $message => $valid) {
            if (!$valid($value)) {
                add_settings_error(self::OPTION_NAME, self::MESSAGE_NAME, $message, 'error');

                return $value;
            }
        }

        add_settings_error(self::OPTION_NAME, self::MESSAGE_NAME, self::MESSAGE_UPDATED_TOKEN, 'updated');

        return $value;
    }

    /**
     * Вызывается после сохранения токена
     *
     * @param $oldToken
     * @param $newToken
     */
    public function onUpdated($oldToken, $newToken): void
    {
        if (!$newToken) {
            return;
        }

        $validators = $this->getTokenValidators();

        foreach ($validators as $message => $valid) {
            if (!$valid($newToken)) {
                return;
            }
        }

        $http = new TextRuApi($newToken);
        $response = $http->addSite();

        if (isset($response['customerId'], $response['id'])) {
            update_option(self::OPTION_CUSTOMER_ID_NAME, $response['customerId']);
            update_option(self::OPTION_SITE_ID_NAME, $response['id']);
        }
    }

    /**
     * Возвращает валидаторы токена
     *
     * @return array
     */
    private function getTokenValidators(): array
    {
        return [
            self::MESSAGE_ERROR_EMPTY_TOKEN => function($value) {
                return trim($value) !== '';
            },
            self::MESSAGE_ERROR_FORMAT_TOKEN => function($value) {
                return preg_match('~^[a-f0-9]{32}$~', $value);
            }
        ];
    }
}
