<?php
if (!isset($_GET["category"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing category parameter"]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="cz" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Výsledky</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="vysledky/index.js"></script>
</head>

<body>
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/partial/header.php" ?>
    <div class="container">
        <h1 class="text-center">Výsledky</h1>
        <div id="resultsContainer" class="text-center mt-4"></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => fetchResults(<?php echo $_GET["category"] ?>));
    </script>
</body>

</html>