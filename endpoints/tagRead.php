<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$router->addRoute("/tagRead", function ($method) {
    $casZPHP = false;
    if ($method === "POST") {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['tagId'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing tagId", "received" => $data]);
            return;
        }

        if (!isset($data['timestamp'])) {
            $currentTime = new DateTime();
            $casZPHP = true;
        } else {
            $currentTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $data['timestamp']);
        }

        $tagId = $data['tagId'];
        $tagId = str_replace(' ', '', $tagId);
        if (!preg_match('/^[0-9A-Fa-f]{24}$/', $tagId)) {
            http_response_code(417);
            echo json_encode(["error" => "Invalid tagId"]);
            return;
        }

        $ch = curl_init();
        $url = "http://127.0.0.1:8000/isInRace?tagId=$tagId";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($response, true);

        if ($httpCode === 208) {
            http_response_code(208);
            echo json_encode(["message" => "Athlete has finished the race"]);
            return;
        }
        if ($httpCode === 404) {
            http_response_code(404);
            echo json_encode(["error" => "Tag not found"]);
            return;
        }
        if ($httpCode != 200) {
            http_response_code($httpCode);
            echo json_encode(["error" => "Failed to call isInRace API", "response" => $response["error"] ?? "Unknown error"]);
            return;
        }

        if ($response["isInRace"] === false) {
            http_response_code(400);
            echo json_encode(["error" => "Athlete not in race"]);
            return;
        }

        if (isset($response["startTime"])) {
            $startTime = $response["startTime"];
            $startTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $startTime);
            if ($startTime === false) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid start time format"]);
                return;
            }
        }

        if (isset($startTime)) {
            $interval = $currentTime->getTimestamp() - $startTime->getTimestamp();
            if ($interval < 60) {
                http_response_code(400);
                echo json_encode(["currentTime" => $currentTime->format('Y-m-d H:i:s.u'), "startTime" => $startTime]);
                echo json_encode(["error" => "At least one minute has to pass after the start time"]);
                return;
            }
        }

        $sql = "INSERT INTO result (tag, time) VALUES (?, ?)";
        $stmt = $GLOBALS['conn']->prepare($sql);
        $timestampStr = $currentTime->format("Y-m-d H:i:s.u");
        $stmt->bind_param("ss", $tagId, $timestampStr);
        if (!$stmt->execute()) {
            error_log("Chyba DB: " . $stmt->error);  // vypíše se do error logu (v konzoli u built-in serveru)
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $stmt->error]);
            return;
        }
        $stmt->close();
        http_response_code(200);
        echo json_encode(["success" => true, "tagId" => $tagId, "timestamp" => $currentTime->getTimestamp(), "casZPHP" => $casZPHP]);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}, false);