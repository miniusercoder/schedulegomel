<?php
$root = __DIR__;
$data = json_decode(file_get_contents('php://input'));
include "$root/config/vars.php";

if ($data->type == "confirmation") die($confirmation);
if ($data->secret != $secret) die("Key error");
echo("ok");
$id = $data->object->from_id;
$peer = $data->object->peer_id;
$text = $data->object->text;
$text = explode(" ", $text);

include "$root/core/vk.api.class.php";
include "$root/core/bot.api.class.php";
include "$root/core/gopt.api.class.php";

$vkApi = new vkApi($access_token, "5.95");
$gopt = new goptApi("https://gopt.by/gomel", "gomel");
$botApi = new botApi($vkApi, $peer, $gopt);
do {
    $init = $gopt->init();
} while (!$init);

if (!file_exists("$root/database/profiles/$id.json")) {
    $user = [
        "status" => "",
        "route" => "",
        "tt" => "",
        "r" => "",
        "direct" => "",
        'v' => 0
    ];
    $result = file_put_contents("$root/database/profiles/$id.json", json_encode($user));
    if (!$result) $botApi->error("Произошла ошибка во время создания профиля\r\nСвяжитесь с [id280790787|администратором]");
    $botApi->sendMessage("Профиль был успешно создан.\r\nВы можете начать получать расписание траспорта прямо сейчас", "default");
    die();
}

if (isset($data->object->payload)) {
    if (isset(json_decode($data->object->payload)->action)) {
        $payload = json_decode($data->object->payload)->action;
        if ($payload == "bus" or $payload == "trolleybus" or $payload == "routetaxi") {
            $routes = $gopt->method("Data/routeList", ["tt" => $payload])->Routes;
            $message = "";
            foreach ($routes as $key => $route) {
                if ($key % 20 == 0) {
                    $botApi->sendMessage($message);
                    $message = "";
                }
                if ($route->IsWorkToday)
                    $message .= $route->Number . ":&#8195;" . $route->Name . PHP_EOL;
            }
            $botApi->sendMessage($message);
            $botApi->setProfile("tt", $payload);
            $botApi->setProfile("status", "waitRoute");
            $botApi->sendMessage("Введите номер нужного маршрута (напр. 5б)", "cancel");
        } elseif ($payload == "AB" or $payload == "BA") {
            $route = $gopt->method("Data/Route", ["tt" => $botApi->getProfile("tt"), "r" => $botApi->getProfile('r')])->Trips;
            if ($route == null) {
                $botApi->setProfile("status", "");
                $botApi->sendMessage("Ошибка на сервере gopt.by", "default");
                die();
            }
            if ($payload == "AB")
                $stopList = $route->StopNamesA;
            else
                $stopList = $route->StopNamesB;
            $message = "";
            foreach ($stopList as $key => $item) {
                if ($key + 1 % 26 == 0) {
                    $botApi->sendMessage($message);
                    $message = "";
                }
                $message .= $key + 1 . ". " . $item . PHP_EOL;
            }
            $botApi->setProfile("direct", $payload);
            $botApi->setProfile("status", "waitStation");
            $botApi->sendMessage($message);
            $botApi->sendMessage("Введите номер остановки (прим. 3)", "cancel");
            die();
        } elseif ($payload == "cancel") {
            $botApi->setProfile("status", null);
            $botApi->sendMessage("Выберите действие из меню ниже", "default");
        }
        die();
    }
}

if ($botApi->getProfile("status") != "") {
    $status = $botApi->getProfile("status");
    if ($status == "waitRoute") {
        $route = $gopt->method("Data/Route", ["tt" => $botApi->getProfile("tt"), "r" => $text[0]])->Trips;
        if ($route == null) {
            $botApi->sendMessage("Не могу найти такой маршрут", "cancel");
            die();
        }
        $routes = $gopt->method("Data/routeList", ["tt" => $botApi->getProfile("tt")])->Routes;
        $message = "";
        foreach ($routes as $key => $route_array) {
            if ($route_array->Number == $text[0]) {
                if (!$route_array->IsWorkToday) {
                    $botApi->sendMessage("Этот рейс не работает сегодня", "cancel");
                    die();
                }
            }
        }
        $keyboard = [];
        $button[0]["action"]["type"] = "text";
        $button[0]["action"]["label"] = $route->NameA;
        $button[0]["action"]["payload"] = json_encode(["action" => "AB"]);
        $button[0]["color"] = "default";
        if ($route->NameA !== null)
            array_push($keyboard, $button);
        $button[0]["action"]["type"] = "text";
        $button[0]["action"]["label"] = $route->NameB;
        $button[0]["action"]["payload"] = json_encode(["action" => "BA"]);
        $button[0]["color"] = "default";
        array_push($keyboard, $button);
        $botApi->sendMessage("Выберите направление движения", "custom", $keyboard);
        $botApi->setProfile("status", "waitDirection");
        $botApi->setProfile("r", $text[0]);
        die();
    } elseif ($status == "waitStation") {
        $route = $gopt->method("Data/Route", ["tt" => $botApi->getProfile("tt"), "r" => $botApi->getProfile('r')])->Trips;
        if ($route == null) {
            $botApi->sendMessage("Ошибка на сервере gopt.by", "cancel");
            die();
        }
        if ($botApi->getProfile("direct") == "AB") {
            $d = 0;
            $stops = $route->StopsA;
            $stopList = $route->StopNamesA;
        } else {
            $d = 1;
            $stops = $route->StopsB;
            $stopList = $route->StopNamesB;
        }
        $message = "";
        $stop_id = 0;
        foreach ($stopList as $key => $item) {
            if ($key + 1 == (int)$text[0]) {
                $stop_id = $stops[$key]->Id;
            }
        }
        if ($stop_id == 0) {
            $botApi->sendMessage("Введите номер остановки", "cancel");
            die();
        }
        $message .= $key + 1 . ". " . $item . PHP_EOL;
        $schedule = $gopt->method("Data/Schedule", [
            "s" => $stop_id,
            "d" => $d,
            "tt" => $botApi->getProfile("tt"),
            "r" => $botApi->getProfile("r")
        ])->Schedule->HourLines;
        $this_hour = (int)date("G");
        $this_minute = (int)date("i");
        $times = [];
        foreach ($schedule as $key => $item) {
            if ($item->Hour == $this_hour) {
                $minutes = explode(" ", $item->Minutes);
                foreach ($minutes as $key1 => $minute) {
                    if ($this_minute > $minute) {
                        continue;
                    } elseif ($this_minute <= $minute) {
                        $times[0] = $item->Hour . ':' . $minute;
                        $message = "Ближайший рейс в " . $times[0];
                        if ($times[0] == "")
                            $botApi->sendMessage("Сегодня больше не будет рейсов", "default");
                        else
                            $botApi->sendMessage($message, "default");
                        $botApi->setProfile("status", "");
                        die();
                    }
                }
            } elseif ($item->Hour < $this_hour)
                continue;
            elseif ($item->Hour > $this_hour) {
                $minutes = explode(" ", $item->Minutes);
                $times[0] = $item->Hour . ':' . $minutes[0];
                break;
            }
        }
        $message = "Ближайший рейс в " . $times[0];
        if ($times[0] == "")
            $botApi->sendMessage("Сегодня больше не будет рейсов", "default");
        else
            $botApi->sendMessage($message, "default");
    } elseif ($status == "waitDirection") {
        $botApi->sendMessage("Выберите направление движения");
        die();
    }
    $botApi->setProfile("status", "");
    die();
}

if ($peer - 2000000000 < 0)
    $botApi->error("Я не знаю такой команды, воспользуйтесь кнопками ниже");

