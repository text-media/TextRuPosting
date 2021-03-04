/* global jQuery, ajaxurl */

import OrdersManager from './js/OrdersManager';
import './scss/index.scss';

/**
 * Скрипты для управления админкой плагина text.ru.posting
 *
 */
jQuery(($) => {
    // В версиях wordpress ниже 2.8 плагин работать не будет
    if (ajaxurl === undefined) {
        return;
    }

    const $token = $('#textru_token');

    if ($token.length === 1) {
        const table = new OrdersManager($);
        table.loadPage(1);
    }
});
