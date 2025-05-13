<?php
require $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$router->addRoute("/getStartList", function ($method) {
    if ($method === "GET") {
        if (!isset($_GET["category"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing category parameter"]);
            return;
        } else {
            $kategorie = $_GET["category"];
        }
        $sql = "SELECT participant_id, bib, first_name, last_name, date_of_birth, tag FROM participants WHERE category_id = ? ORDER BY bib ASC";
        $stmt = $GLOBALS['conn']->prepare($sql);
        $stmt->bind_param("i", $kategorie);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $stmt->error]);
            return;
        }
        $result = $stmt->get_result();
        $nazevKategorie = "SELECT description FROM categories WHERE category_id = ?";
        $stmtNazev = $GLOBALS['conn']->prepare($nazevKategorie);
        $stmtNazev->bind_param("i", $kategorie);
        if (!$stmtNazev->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $stmtNazev->error]);
            return;
        }
        $resultNazev = $stmtNazev->get_result();
        if ($result->num_rows > 0) {
            $startList = [];
            while ($row = $result->fetch_assoc()) {
                $startList[] = [
                    "id" => $row["participant_id"],
                    "bib" => $row["bib"],
                    "firstName" => $row["first_name"],
                    "lastName" => $row["last_name"],
                    "dateOfBirth" => $row["date_of_birth"],
                    "tag" => $row["tag"]
                ];
            }
            http_response_code(200);
            $nazev = $resultNazev->fetch_assoc();
            $response = [
                "category" => $nazev["description"],
                "startList" => $startList
            ];
            echo json_encode($response);
        } else {
            http_response_code(200);
            echo json_encode([]);
        }

    } else {
        http_response_code(200);
        echo json_encode([]);
    }
    $GLOBALS['conn']->close();
}, false);