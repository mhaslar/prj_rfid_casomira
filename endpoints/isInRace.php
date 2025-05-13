<?php
require $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$router->addRoute("/isInRace", function ($method) {
    if ($method === "GET") {
        if (!isset($_GET["tagId"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing tagId parameter"]);
            return;
        }

        $tagId = $_GET["tagId"];
        $tagId = str_replace(' ', '', $tagId);

        if (strlen($tagId) !== 24) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid tagId length"]);
            return;
        }

        $sql = "SELECT COUNT(*), COUNT(r.id) AS mezicasy, c.startTime, c.laps, c.status
            FROM participants p 
            LEFT JOIN categories c ON c.category_id = p.category_id 
            LEFT JOIN result r ON REPLACE(r.tag, ' ', '') = REPLACE(p.tag, ' ', '')
            WHERE REPLACE(p.tag, ' ', '') = '$tagId';";
        
        $result = $GLOBALS['conn']->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row["laps"] == 0) {
                http_response_code(404);
                echo json_encode(["error" => "Tag not found"]);
                return;
            }
            if ($row["mezicasy"] >= $row["laps"]) {
                http_response_code(208);
                echo json_encode(["message" => "Athlete has finished the race"]);
                return;
            }
            if ($row["status"] == "beforeStart") {
                http_response_code(200);
                echo json_encode(["isInRace" => false]);
                return;
            }
            $isInRace = $row["COUNT(*)"] > 0;
            $startTime = $row["startTime"];
            http_response_code(200);
            if ($row["status"] == "active") {
                echo json_encode(["isInRace" => true, "startTime" => $startTime]);
            } else {
                echo json_encode(["isInRace" => false]);
            }
        } else {
            http_response_code(200);
            echo json_encode(["isInRace" => false]);
        }
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}, false);