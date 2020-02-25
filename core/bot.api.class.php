<?php

class botApi
{
    private $vkApi = null;
    private $peer = 0;
    private $gopt = null;

    /**
     * botApi constructor.
     *
     * @param object $vkApi Экземпляр класса vkApi
     * @param int $peer_id ID беседы (пользователя)
     * @param object $gopt
     */
    function __construct(object $vkApi, int $peer_id, object $gopt)
    {
        $this->vkApi = $vkApi;
        $this->peer = $peer_id;
        $this->gopt = $gopt;
    }


    /**
     * Отправляет сообщение и прекращает выполнение скрипта
     *
     * @param string $message Сообщение
     */
    function error(string $message)
    {
        $this->sendMessage($message, "default");
        die();
    }

    /**
     * Отправляет сообщение
     *
     * @param string $message Сообщение
     * @param string $keyboard_type Тип клавиатуры
     * @param array $custom_keyboard
     * @param string $attachment Приложение к сообщению
     */
    function sendMessage(string $message, string $keyboard_type = "", array $custom_keyboard = [], string $attachment = "")
    {
        $data = [];
        if ($keyboard_type != "") {
            $keyboard["one_time"] = false;
            if ($keyboard_type == "default") {
                $keyboard["buttons"][0][0]["action"]["type"] = "text";
                $keyboard["buttons"][0][0]["action"]["label"] = "Автобус";
                $keyboard["buttons"][0][0]["action"]["payload"] = json_encode(["action" => "bus"]);
                $keyboard["buttons"][0][0]["color"] = "default";
                $keyboard["buttons"][1][0]["action"]["type"] = "text";
                $keyboard["buttons"][1][0]["action"]["label"] = "Троллейбус";
                $keyboard["buttons"][1][0]["action"]["payload"] = json_encode(["action" => "trolleybus"]);
                $keyboard["buttons"][1][0]["color"] = "default";
                $keyboard["buttons"][2][0]["action"]["type"] = "text";
                $keyboard["buttons"][2][0]["action"]["label"] = "Маршрутка";
                $keyboard["buttons"][2][0]["action"]["payload"] = json_encode(["action" => "routetaxi"]);
                $keyboard["buttons"][2][0]["color"] = "default";
                $keyboard["buttons"][3][0]["action"]["type"] = "text";
                $keyboard["buttons"][3][0]["action"]["label"] = "Сохранённые маршруты";
                $keyboard["buttons"][3][0]["action"]["payload"] = json_encode(["action" => "saveList"]);
                $keyboard["buttons"][3][0]["color"] = "primary";
            } elseif ($keyboard_type == "save") {
                $keyboard["buttons"][0][0]["action"]["type"] = "text";
                $keyboard["buttons"][0][0]["action"]["label"] = "Сохранить этот маршрут";
                $keyboard["buttons"][0][0]["action"]["payload"] = json_encode(["action" => "save"]);
                $keyboard["buttons"][0][0]["color"] = "positive";
                $keyboard["buttons"][1][0]["action"]["type"] = "text";
                $keyboard["buttons"][1][0]["action"]["label"] = "Отмена";
                $keyboard["buttons"][1][0]["action"]["payload"] = json_encode(["action" => "cancel"]);
                $keyboard["buttons"][1][0]["color"] = "negative";
            } elseif ($keyboard_type == "delete") {
                $keyboard["buttons"][0][0]["action"]["type"] = "text";
                $keyboard["buttons"][0][0]["action"]["label"] = "Удалить этот маршрут";
                $keyboard["buttons"][0][0]["action"]["payload"] = json_encode(["action" => "delete"]);
                $keyboard["buttons"][0][0]["color"] = "secondary";
                $keyboard["buttons"][1][0]["action"]["type"] = "text";
                $keyboard["buttons"][1][0]["action"]["label"] = "Отмена";
                $keyboard["buttons"][1][0]["action"]["payload"] = json_encode(["action" => "cancel"]);
                $keyboard["buttons"][1][0]["color"] = "negative";
            } elseif ($keyboard_type == "custom") {
                $keyboard['buttons'] = $custom_keyboard;
            } elseif ($keyboard_type == "cancel") {
                $keyboard["buttons"][0][0]["action"]["type"] = "text";
                $keyboard["buttons"][0][0]["action"]["label"] = "Отмена";
                $keyboard["buttons"][0][0]["action"]["payload"] = json_encode(["action" => "cancel"]);
                $keyboard["buttons"][0][0]["color"] = "negative";
            }
            $data["keyboard"] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        }
        $rand = rand();
        $data['random_id'] = $rand;
        $data['disable_mentions'] = 1;
        $data['peer_id'] = $this->peer;
        $data['message'] = $message;
        $data['dont_parse_links'] = 1;
        $data['attachment'] = $attachment;
        $this->vkApi->method("messages.send", $data);
    }
/// 108766
/// 58631
    function getProfile($item)
    {
        $profile = json_decode(file_get_contents("database/profiles/{$this->peer}.json"));
        return $profile->$item;
    }

    function setProfile($item, $value)
    {
        $profile = json_decode(file_get_contents("database/profiles/{$this->peer}.json"));
        $profile->$item = $value;
        file_put_contents("database/profiles/{$this->peer}.json", json_encode($profile));
    }

    /**
     * Получает информацию о пользователе vk
     *
     * @param int $user_id ID пользователя
     * @param string $name_case Падеж имени
     * @return object Результат
     */
    function getUser(int $user_id, string $name_case = "nom")
    {
        return $this->vkApi->method("users.get", [
            "name_case" => $name_case,
            "user_ids" => $user_id
        ])->response[0];
    }
}