// eslint-disable-next-line no-unused-vars
/* global ajaxurl */

/**
 * Класс управления таблицей заказов
 *
 */
class OrdersManager {
    /**
     * Конструктор класса
     *
     * @param {Function|jQuery|HTMLElement}  $
     */
    constructor($) {
        this.$ = $;
    }

    /**
     * Формирует и выводит список документов указанного заказа
     *
     *
     * @returns {void}
     *
     * @param selectedOrderId
     * @param $button
     */
    viewDocuments(selectedOrderId, $button) {
        // Список документов уже может быть загружен на страницу, в таком случае
        // ищем табличку документов и просто меняем ей видимость.
        const $table = this.$(`#${OrdersManager.getTitleNodeId(selectedOrderId)}`).find('table');
        if ($table.length) {
            $table.toggle();
            $table.parents('.order_row').toggleClass('opened');

            return;
        }

        let $loader = $button.parent().find('span.spinner');
        if ($loader.length > 0) {
            return;
        }

        $loader = this.$('<span class="spinner is-active"></span>');
        $loader.insertAfter($button);

        // Если же таблички с документами нет, то загружаем её с сервера
        const data = {
            action: 'textru_view_documents',
            orderId: selectedOrderId,
        };

        this.$.post(ajaxurl, data, (response) => {
            // eslint-disable-next-line no-param-reassign
            response = JSON.parse(response);

            /**
             * Переданные с сервера документы
             *
             * @type {Array.<{
             *  number: number,
             *  title: string,
             *  text: string,
             *  createdAt: string,
             *  url: ?string,
             * }>} */
            const documents = response.list;
            const { orderId } = response;
            const $documents = this.$('<table>');

            if (documents.length === 0) {
                // Если документов не получили, то выведем соответствующее сообщение
                const $row = this.$('<tr>')
                    .append('<td>')
                    .append('<div>')
                    .find('div')
                    .addClass('update-nag notice notice-warning')
                    .text('У этого заказа нет документов');

                $documents.append($row);
            } else {
                // Если же нашли документы, то в цикле каждый добавим в табличку
                documents.forEach((document) => {
                    let $link = this.$('<a>');
                    if (document.url === null) {
                        $link
                            .text('Сохранить в черновики')
                            .addClass('button button-primary clearfix')
                            .on('click', () => {
                                this.publishDocument(orderId, document.number);
                                return false;
                            });
                    } else {
                        $link = this.getDocumentLink(document.url);
                    }

                    const $row = this.$('<tr>')
                        .attr('id', OrdersManager.getDocumentNodeId(orderId, document.number))
                        .append(this.$('<td>').text(document.number))
                        .append(this.$('<td>')
                            .append(this.$('<h4>').text(document.title))
                            .append(this.$('<p><em></em></p>').find('em').text(document.createdAt))
                            .append(this.$('<p>').text(document.text))
                            .append($link));
                    $documents.append($row);
                });
            }

            // Табличку поместим под заголовком заказа
            this.$(`#${OrdersManager.getTitleNodeId(orderId)}`)
                .append($documents)
                .parents('.order_row')
                .toggleClass('opened');

            $loader.remove();
        });
    }

    /**
     * Создание таблицы заказов
     *
     * @returns {array}
     */
    makeTable() {
        const rows = [];

        if (this.orders === null) {
            return rows;
        }

        this.orders.forEach((order) => {
            /**
             * @type {{id: number, finishedAt: string, title :string}} order
             * @type {jQuery} $link
             */
            const $link = this.$('<a href="#">'
                + '<span class="show_documents">Посмотреть документы</span>'
                + '<span class="hide_documents">Скрыть документы</span>'
                + '</a>');

            $link.on('click', () => {
                this.viewDocuments(order.id, $link);

                return false;
            });

            const $row = this.$('<tr>')
                .addClass('order_row')
                .append(this.$('<td>').text(order.id))
                .append(this.$('<td>').text(order.title).addClass('title').attr('id', OrdersManager.getTitleNodeId(order.id)))
                .append(this.$('<td>').text(order.finishedAt))
                .append(this.$('<td><div class="row-actions"><span></span></div></td>').find('span').append($link).end());
            rows.push($row);
        });

        return rows;
    }

    /**
     * Создание паджинатора
     *
     * @param {number} currentPage
     * @returns {jQuery}
     */
    makePaginator(currentPage) {
        const $buttons = this.$('<span>');
        const countButtons = Math.ceil(this.pages / 20);

        this.$('#textru_paginator').toggle(countButtons > 0);

        for (let page = 1; page <= countButtons; page += 1) {
            const $button = this
                .$('<a href="#">')
                .text(page)
                .on('click', () => {
                    this.loadPage(page);

                    return false;
                });

            if (page === currentPage) {
                $button.addClass('active');
            }

            if (page % 20 === 0) {
                $buttons.append('<br>');
            }

            $buttons.append($button);
        }

        return $buttons;
    }

    /**
     * Загрузка страницы заказов с сервера
     *
     * @param {number} page
     * @returns {void}
     */
    loadPage(page) {
        const data = {
            action: 'textru_load_posts',
            page,
        };

        this.$.post(ajaxurl, data, (response) => {
            /**
             * Данные по заказам, пришедшие с сервера
             *
             * @type {{totalCount: number, list: array}} response
             */
            // eslint-disable-next-line no-param-reassign
            response = JSON.parse(response);
            this.pages = response.totalCount || null;
            this.orders = response.list || null;

            if (this.orders !== null) {
                const $table = this.makeTable();
                const $paginator = this.makePaginator(page);

                this.$('#textru_table tbody')
                    .empty()
                    .append($table);

                this.$('#textru_paginator span')
                    .empty()
                    .append($paginator);
            }

            const emptyOrdersList = this.orders === null || this.orders.length === 0;

            this.$('#textru_table').toggle(!emptyOrdersList);
            this.$('#textru_empty_table').toggle(emptyOrdersList);
        });
    }

    /**
     * Публикация выбранного документа в черновики
     *
     * @returns {boolean}
     * @param selectedOrderId
     * @param selectedNumber
     */
    publishDocument(selectedOrderId, selectedNumber) {
        const data = {
            action: 'textru_publish_document',
            orderId: selectedOrderId,
            number: selectedNumber,
        };

        const $ceil = this.$(`#${OrdersManager.getDocumentNodeId(selectedOrderId, selectedNumber)}`)
            .find('td')
            .last();

        let $loader = $ceil.find('span.spinner');
        if ($loader.length > 0) {
            return false;
        }

        $loader = this.$('<span class="spinner is-active"></span>');
        const $button = $ceil.find('a');

        $ceil.append($loader);
        $button.addClass('disabled');

        this.$.post(ajaxurl, data, (response) => {
            const { url } = JSON.parse(response);

            if (url === undefined) {
                return;
            }

            const $link = this.getDocumentLink(url);

            $button
                .remove()
                .end()
                .append($link);

            $loader.remove();
        });

        return false;
    }

    /**
     * Возвращает ссылку на сохранённый ранее черновик
     *
     * @param url
     * @returns {*}
     */
    getDocumentLink(url) {
        return this.$('<a>')
            .text(`Сохранено: ${url}`)
            .attr('href', url)
            .attr('target', '_blank');
    }

    /**
     * Возвращает идентификатор строки таблицы документов с указанным документом
     *
     * @param {number} orderId
     * @param {number} number
     *
     * @returns {string}
     */
    static getDocumentNodeId(orderId, number) {
        return `textru_document_${orderId}_${number}`;
    }

    /**
     * Возвращает идентификатор ноды с заголовком заказа
     *
     * @param {number} orderId
     *
     * @returns {string}
     */
    static getTitleNodeId(orderId) {
        return `textru_title_${orderId}`;
    }
}

export default OrdersManager;
