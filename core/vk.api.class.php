<?php

class vkApi
{
    private $access = "";
    private $v = "";
    private $api_url = "https://api.vk.com/method/";

    /**
     * VkApi constructor.
     *
     * @param string $access_token Access token группы
     * @param string $v Версия vk api
     */
    function __construct(string $access_token, $v = "5.95")
    {
        $this->access = $access_token;
        $this->v = $v;
    }

    /**
     * Выполняет метод vk api
     *
     * @param string $name Имя метода
     * @param array $data Массив параметров
     * @return mixed Json объект, который вернул vk
     */
    function method(string $name, array $data)
    {
        $data["access_token"] = $this->access;
        $data["v"] = $this->v;
        $data = http_build_query($data);
        $url = "{$this->api_url}/$name?$data";
        $response = $this->get($url);
        $response = json_decode($response);
        return ($response);
    }

    /**
     * Функция выполняет get запрос
     *
     * @param string $url Url для запроса
     * @return string Строка, которую вернул сервер
     */
    function get(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        return (curl_exec($ch));
    }
}
