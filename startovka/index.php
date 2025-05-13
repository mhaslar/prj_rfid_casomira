<!DOCTYPE html>
<html lang="cz" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Startovka <?php echo "V1 K" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/partial/header.php" ?>
    <div class="container">
        <h1 id="categoryTitle" class="text-center">Startovka</h1>
        <div id="startListContainer" class="text-center mt-4">
        </div>
    </div>
    <script src="startovka/index.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', fetchStartList(<?php echo $_GET["category"] ?>));
    </script>
</body>

</html>