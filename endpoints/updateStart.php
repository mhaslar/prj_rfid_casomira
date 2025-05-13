<?php
require $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$router->addRoute("/updateStart", function($method) {
    if ($method === "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['startId']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing startId or status"]);
            return;
        }

        if (!is_numeric($data['startId'])) {
            http_response_code(400);
            echo json_encode(["error" => "startId must be a number"]);
            return;
        }

        if (!in_array($data['status'], ["active", "inactive", "finished", "beforeStart", "start"])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid status"]);
            return;
        }

        $startId = $data['startId'];
        $status = $data['status'];
        $startTime = $data['startTime'];

        if ($status === "start") {
            $sql = "UPDATE categories SET status = ?, startTime = NOW() WHERE category_id = ?";
        } else {
            $sql = "UPDATE categories SET status = ? WHERE category_id = ?";
        }
        $stmt = $GLOBALS['conn']->prepare($sql);
        if ($status === "start") {
            $status = "active";
            $stmt->bind_param("si", $status, $startId);
        } else {
            $stmt->bind_param("si", $status, $startId);
        }
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $stmt->error]);
            return;
        }
        $stmt->close();

        http_response_code(200);
        print_r($data);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}, false);