<?php

include_once __DIR__ . '/TextRuApi.php';
include_once __DIR__ . '/TextRuDataMapper.php';

/**
 * Класс для взаимодействия с фронтендом админки посредством ajax
 *
 */
class TextruOrdersManager
{
    /**
     * @var TextRuApi
     */
    private $http;
    /**
     * @var TextRuDataMapper
     */
    private $mapper;

    /**
     * Конструктор класса
     *
     */
    public function __construct()
    {
        add_action('wp_ajax_textru_load_posts', [$this, 'loadPosts']);
        add_action('wp_ajax_textru_view_documents', [$this, 'viewDocuments']);
        add_action('wp_ajax_textru_publish_document', [$this, 'publishDocument']);

        $this->http = new TextRuApi(get_option(TextRuAdminPage::OPTION_NAME));
        $this->mapper = new TextRuDataMapper();
    }

    /**
     * Публикация выбранного документа в черновики
     *
     */
    public function publishDocument(): void
    {
        global $wpdb;

        $orderId = (int)($_POST['orderId'] ?? null);
        if ($orderId === null) {
            return;
        }

        $number = (int)($_POST['number'] ?? null);
        if ($number === null) {
            return;
        }

        $document = $this->mapper->getDocument($orderId, $number);
        if (!$document) {
            return;
        }

        if ($document['post_id'] !== null) {
            return;
        }

        $response = $this->http->getDocument($orderId, $number);

        $post = [
            'post_title' => $response['title'],
            'post_content' => $response['text'],
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        ];

        $postId = wp_insert_post($post);

        $this->mapper->setDocumentPublished((int)$document['id'], $postId);

        $result = [
            'url' => get_edit_post_link($postId, ''),
            'orderId' => $orderId,
            'number' => $number,
        ];

        echo json_encode($result);

        wp_die();
    }

    /**
     * Возвращает документы заказа.
     * Если документов нет в БД wordpress, то загружает документы с биржи контента
     * и сохраняет их представление в БД.
     *
     * @throws Exception
     */
    public function viewDocuments(): void
    {
        $orderId = (int)($_POST['orderId'] ?? null);
        if ($orderId === null) {
            return;
        }

        // Получим документы из БД
        $result = $this->mapper->getDocuments($orderId);
        $documents = $this->normalizeDocuments($orderId, $result);

        // Если из там нет, то загрузим с биржи контента
        if ($documents['totalCount'] === 0) {
            $result = $this->http->getDocuments($orderId);
            $documents = $this->normalizeDocuments($orderId, $result);

            // После загрузки сохраним в базу данных
            foreach ($documents['list'] as $document) {
                $this->mapper->saveOrderDocument(
                    $orderId,
                    $document['number'],
                    $document['title'],
                    $document['text'],
                    (new \DateTime($document['createdAt']))->format('Y-m-d H:i:s')
                );
            }
        }

        echo json_encode($documents);

        wp_die();
    }

    /**
     * Нормализует представление документов для выдачи на фронтенд
     *
     * @param $orderId
     * @param array $response
     *
     * @return array
     * @throws Exception
     */
    private function normalizeDocuments($orderId, array $response): array
    {
        return [
            'orderId' => $orderId,
            'totalCount' => $response['totalCount'],
            'list' => array_map(static function (array $order) {
                $text = strip_tags($order['text']);
                $title = strip_tags($order['title']) ?: 'Заголовок отсутствует';

                return [
                    'number' => $order['number'],
                    'title' => mb_substr($title, 0, 250) . (mb_strlen($title) > 250 ? '...' : ''),
                    'text' => mb_substr($text, 0, 250) . (mb_strlen($text) > 250 ? '...' : ''),
                    'createdAt' => (new \DateTime($order['createdAt']))->format('d.m.Y H:i:s'),
                    'url' => $order['post_id'] ? get_edit_post_link($order['post_id'], '') : null,
                ];
            }, $response['list'])
        ];
    }

    /**
     * Загружает список заказов
     *
     */
    public function loadPosts(): void
    {
        $page = (int)($_POST['page'] ?? 1);
        $response = $this->http->getPosts($page);

        $result = [
            'totalCount' => $response['totalCount'],
            'currentPage' => $page,
            'list' => array_map(static function (array $order) {
                return [
                    'id' => $order['id'],
                    'title' => $order['title'],
                    'finishedAt' => (new \DateTime($order['finishedAt']))->format('d.m.Y H:i:s')
                ];
            }, $response['list'])
        ];

        echo json_encode($result);

        wp_die();
    }

    /**
     * Регистрирует ресурсы плагина
     *
     */
    public static function registerAssets(): void
    {
        wp_register_script(
            'textru_script',
            plugins_url('dist/script.min.js', __FILE__),
            [],
            1,
            true
        );
        wp_enqueue_script('textru_script');

        wp_register_style('textru_style', plugins_url('dist/style.min.css', __FILE__));
        wp_enqueue_style('textru_style');
    }
}


