<?php

/**
 * Класс для работы с данными таблицы wp_textru_orders
 *
 */
class TextRuDataMapper
{
    private $table = 'textru_orders';

    /**
     * Возвращает документы указанного заказа
     *
     * @param int $orderId
     *
     * @return array
     */
    public function getDocuments(int $orderId): array
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}{$this->table} WHERE `order_id`={$orderId}";

        $result = $wpdb->get_results($sql, ARRAY_A);

        $result = [
            'totalCount' => count($result),
            'list' => $result,
        ];

        return $result;
    }

    /**
     * Сохраняет новый документ в БД
     *
     * @param int $orderId
     * @param int $documentNumber
     * @param string $title
     * @param string $text
     * @param $createdAt
     * @return int
     */
    public function saveOrderDocument(int $orderId, int $documentNumber, string $title, string $text, $createdAt): int
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . $this->table,
            [
                'order_id' => $orderId,
                'number' => $documentNumber,
                'title' => $title,
                'text' => $text,
                'created_at' => $createdAt,
            ]
        );

        return $wpdb->insert_id;
    }

    /**
     * Возвращает сохранённый ранее документ по указанному заказу и номеру документа в заказе
     *
     * @param int $orderId
     * @param int $number
     *
     * @return array|object|void|null
     */
    public function getDocument(int $orderId, int $number): ?array
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}{$this->table} WHERE `order_id`={$orderId} AND `number`={$number}";

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Помечает ранее созданный документ как опубликованный в черновики
     *
     * @param int $id
     * @param int $postId
     *
     * @return bool|int
     */
    public function setDocumentPublished(int $id, int $postId): bool
    {
        global $wpdb;

        return (bool)$wpdb->update(
            $wpdb->prefix . $this->table,
            ['post_id' => $postId],
            ['id' => $id]
        );
    }
}
