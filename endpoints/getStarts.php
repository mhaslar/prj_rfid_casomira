<?php
require $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$router->addRoute("/getStarts", function ($method) {
    if ($method === "GET") {
        $sql = "SELECT category_id, name, description, status, startTime FROM categories";
        $result = $GLOBALS['conn']->query($sql);
        if ($result->num_rows > 0) {
            $starts = [];
            while ($row = $result->fetch_assoc()) {
                $starts[] = [
                    "id" => $row["category_id"],
                    "name" => $row["description"],
                    "status" => $row["status"],
                    "startTime" => $row["startTime"]
                ];
            }
            http_response_code(200);
            echo json_encode($starts);
        } else {
            http_response_code(200);
            echo json_encode([]);
        }
        $GLOBALS['conn']->close();
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}, false);