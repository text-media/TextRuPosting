<?php

class TextRuAntiplagiat
{
    /*
    2 функции для взаимодействия с API Text.ru посредством POST-запросов.
    Ответы с сервера приходят в формате JSON.
    */

    //-----------------------------------------------------------------------

    /**
     * Добавление текста на проверку
     *
     * @param string $text - проверяемый текст
     * @param string $userkey - пользовательский ключ
     *
     * @return string $textUid - uid добавленного текста
     * @return int $errorCode - код ошибки
     * @return string $errorDesc - описание ошибки
     */
    public static function addText(): string
    {
        $postQuery = array();
        $post = get_post($_GET['post']);
        $postQuery['text'] = wp_strip_all_tags($post->post_content);
        $postQuery['userkey'] = get_option('textru_token');
        $postQuery = http_build_query($postQuery);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.text.ru/post');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
        $json = curl_exec($ch);
        $errno = curl_errno($ch);
        $textUid = '';
        $errorCode = '';
        $errorDesc = '';
        if (!$errno) {
            $resAdd = json_decode($json);
            if (isset($resAdd->text_uid)) {
                $textUid = $resAdd->text_uid;
            } else {
                $errorCode = $resAdd->error_code;
                $errorDesc = $resAdd->error_desc;
            }
        } else {
            return curl_error($ch);
        }
        curl_close($ch);
        if ($textUid != null) {
            add_post_meta( $_GET['post'], 'text_uid', $textUid);
            return $textUid;
        } else {
            echo 'Ошибка ', $errorCode, ' - ', $errorDesc;
            return 0;
        }
    }

    /**
     * Получение статуса и результатов проверки текста в формате json
     *
     * @param string $uid - uid проверяемого текста
     * @param string $userkey - пользовательский ключ
     *
     * @return float $unique - уникальность текста (в процентах)
     * @return int $errorCode - код ошибки
     * @return string $errorDesc - описание ошибки
     */
    public static function showRes(): string
    {
        if ( empty( $_GET[ 'action' ] )  || $_GET[ 'action' ] != 'showRes') {
            return 0;
        }
        $postQuery = array();
        $postQuery['uid'] = get_post_meta($_GET['post'], 'text_uid', true);
        if ($postQuery['uid'] === null) {
            self::addText();
            $postQuery['uid'] = get_post_meta($_GET['post'], 'text_uid', true);
        }
        $postQuery['userkey'] = get_option('textru_token');
        $postQuery = http_build_query($postQuery, '', '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.text.ru/post');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
        $json = curl_exec($ch);
        $errno = curl_errno($ch);
        $textUnique = '';
        $errorCode = '';
        $errorDesc = '';
        if (!$errno) {
            $resCheck = json_decode($json);
            if (isset($resCheck->text_unique)) {
                $textUnique = $resCheck->text_unique;
            } else {
                $errorCode = $resCheck->errorCode;
                $errorDesc = $resCheck->errorDesc;
            }
        } else {
            return curl_error($ch);
        }
        curl_close($ch);
        if ($textUnique != null) {
            add_post_meta( $_GET['post'], 'textUnique', $textUnique);
            echo 'Уникальность текста: ', $textUnique ,'%';
            return $textUnique;
        } else {
            echo 'Ошибка ', $errorCode, ' - ', $errorDesc;
            return 0;
        }
    }
}