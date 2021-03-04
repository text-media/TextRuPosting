<?php

/**
 * Класс для взаимодействия с АПИ биржи контента text.ru
 *
 */
class TextRuApi
{
    const ENDPOINT = 'https://exchange.text.ru/api/customer';

    private $token;

    /**
     * Конструктор класса
     *
     * @param $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Добавляет сайт по указанному токену
     *
     * @return array
     */
    public function addSite(): array
    {
        $params = ['url' => parse_url(get_site_url(), PHP_URL_HOST)];

        return $this->request('posting.addSite', $params);
    }

    /**
     * Возвращает страницу постов пользователя
     *
     * @param $page
     * @return array|string[][]
     */
    public function getPosts($page): array
    {
        if ($page <= 0) {
            return [];
        }

        $customerId = (int)get_option(TextRuAdminPage::OPTION_CUSTOMER_ID_NAME);
        if (!$customerId) {
            return [];
        }

        return $this->request('order.getList', [
            'customerId' => $customerId,
            'sort' => [
                'name' =>'Order.finishedAt',
                'dir' =>'DESC'
            ],
            'state' => [9, 12],
            'limit' => TextRuAdminPage::ORDERS_PER_PAGE,
            'offset' => ($page - 1) * TextRuAdminPage::ORDERS_PER_PAGE,
            'site' => (int)get_option(TextRuAdminPage::OPTION_SITE_ID_NAME),
        ]);
    }

    /**
     * Возвращает список документов, прикреплённых к указанному заказу
     *
     * @param int $orderId
     * @return array
     */
    public function getDocuments(int $orderId): array
    {
        return $this->request('document.getList', ['orderId' => $orderId]);
    }

    /**
     * Возвращает документ по его идентификатору
     *
     * @param int $orderId
     * @param int $number
     * @return array
     */
    public function getDocument(int $orderId, int $number): array
    {
        return $this->request('document.getOne', [
            'orderId' => $orderId,
            'number' => $number
        ]);
    }

    /**
     * Отправляет запрос к exchange.text.ru с указанным методом и набором параметров
     *
     * @param $method
     * @param $params
     *
     * @return array
     */
    private function request($method, $params): array
    {
        $content = sprintf(
            '[{"jsonrpc":"2.0","method":"%s","id":0,"params":%s}]',
            $method,
            str_replace(' ', '', json_encode($params))
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Key: ' . $this->token,
            'X-Sign: ' . hash('sha256', $content . $this->token),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        return $result[0]['result'] ?? ['state' => 'error', 'message' => 'Ошибка связи с text.ru', 'data' => $result];
    }
}
