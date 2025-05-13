<?php
require $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$router->addRoute("/getResults", function ($method) {
    if ($method === "GET") {
        if (!isset($_GET["category"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing category parameter"]);
            return;
        } else {
            $kategorie = $_GET["category"];
        }
        
        $sql = "SELECT p.bib, p.first_name, p.last_name, p.date_of_birth, p.gender, p.tag, r.id, r.time as lap_time, c.description, c.startTime, c.laps 
                FROM participants p
                LEFT JOIN result r ON REPLACE(r.tag, ' ', '') = REPLACE(p.tag, ' ', '')
                LEFT JOIN categories c ON c.category_id = p.category_id
                WHERE c.category_id = $kategorie
                ORDER BY p.tag, r.time ASC;";
                
        $result = $GLOBALS['conn']->query($sql);

        $racers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tag = $row["tag"];
                if (!isset($racers[$tag])) {
                    $racers[$tag] = [
                        "bib"             => $row["bib"],
                        "first_name"      => $row["first_name"],
                        "last_name"       => $row["last_name"],
                        "date_of_birth"   => $row["date_of_birth"],
                        "gender"          => $row["gender"],
                        "startTime"       => $row["startTime"],
                        "expected_laps"   => (int) $row["laps"],
                        "lap_times"       => []
                    ];
                }
                
                if (!empty($row["lap_time"])) {
                    $racers[$tag]["lap_times"][] = ["id" => $row["id"], "time" => $row["lap_time"]];
                }
            }
        } else {
            http_response_code(200);
            echo json_encode([]);
            $GLOBALS['conn']->close();
            return;
        }
        
        foreach ($racers as $tag => &$racer) {
            if (count($racer['lap_times']) === 0) {
                $racer['dnf'] = true;
                $racer['total_time'] = 0;
                $racer['total_time_formatted'] = "DNF";
            } else {
                usort($racer['lap_times'], function($a, $b) {
                    return $a['id'] <=> $b['id'];
                });

                $startTime = isset($racer['startTime']) ? new DateTime($racer['startTime']) : new DateTime();
                $lapDurations = [];
                $previousTime = $startTime;

                foreach ($racer['lap_times'] as $lapEntry) {
                    $lapTime = new DateTime($lapEntry['time']);
                    $duration = $lapTime->getTimestamp() - $previousTime->getTimestamp();
                    $lapDurations[] = $duration;
                    $previousTime = $lapTime;
                }
                $racer['lap_durations'] = $lapDurations;
                $totalTime = array_sum($lapDurations);
                $racer['total_time'] = $totalTime;

                if ($totalTime >= 86400) {
                    $days = floor($totalTime / 86400);
                    $remainder = $totalTime % 86400;
                    $racer['total_time_formatted'] = $days . "d " . gmdate("H:i:s", $remainder);
                } else {
                    $racer['total_time_formatted'] = gmdate("H:i:s", $totalTime);
                }

                if (count($racer['lap_times']) > $racer['expected_laps']) {
                    $racer['first_name'] = "!!!" . $racer['first_name'];
                }
            }
        }
        unset($racer);
        
        usort($racers, function($a, $b) {
            $a_dnf = isset($a['dnf']) && $a['dnf'] === true;
            $b_dnf = isset($b['dnf']) && $b['dnf'] === true;
            if ($a_dnf && !$b_dnf) {
                return 1;
            } else if (!$a_dnf && $b_dnf) {
                return -1;
            } elseif ($a_dnf && $b_dnf) {
                return 0;
            } else {
                return $a['total_time'] <=> $b['total_time'];
            }
        });
        
        $rank = 1;
        foreach ($racers as &$racer) {
            if (isset($racer['dnf']) && $racer['dnf'] === true) {
                $racer['order'] = "DNF";
            } else {
                $racer['order'] = $rank++;
            }
        }
        unset($racer);
        
        $GLOBALS['conn']->close();
        echo json_encode($racers);
        
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
}, false);