<?php

try {
    $pdo = new PDO("mysql:host=localhost;dbname=covid19", "root", "");
} catch(PDOException $e) {
    die("can not connect to db");
}
$data = file_get_contents("https://api.covid19api.com/countries");
$data = json_decode($data, true);

$sql = "INSERT INTO countries (name, slug) VALUES (:name, :slug)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam("name", $name);
$stmt->bindParam("slug", $slug);

foreach($data as $country) {
    $name = $country['Country'];
    $slug = $country['Slug'];
    $stmt->execute();
}

?>