<?php
function syncData()
{
    set_time_limit(0);
    try {
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        $pdo = new PDO('mysql:host=localhost;dbname=covid19;', 'root', '', $options);
    } catch (PDOException $e) {
        die("can not connect to db");
    }
    $sqlA = "SELECT * FROM countries";
    $stmt = $pdo->query($sqlA);
    $sql = "SELECT MAX(date) as old_date FROM `cases` WHERE 1";
    $stmt2 = $pdo->query($sql);
    $oldDate = $stmt2->fetch();
    $oldDate = $oldDate["old_date"];
    $oldDate = date("Y-m-d", strtotime($oldDate . '+1 day'));
    $currentDate = date("Y-m-d");
    while ($country = $stmt->fetch()) {
        $data1 = file_get_contents("https://api.covid19api.com/country/{$country['name']}?from=" . $oldDate . "T00:00:00Z&to=" . $currentDate . "T00:00:00Z");
        $data1 = json_decode($data1, true);
        $sqlA = "INSERT INTO `cases` (`country_id`, `active`, `deaths`, `recovered`, `confirmed`, date)
        VALUES (:country_id, :active, :deaths, :recovered, :confirmed, :date)";
        $stmtInsertCases = $pdo->prepare($sqlA);
        $stmtInsertCases->bindParam('country_id', $countryId);
        $stmtInsertCases->bindParam('active', $active);
        $stmtInsertCases->bindParam('recovered', $recovered);
        $stmtInsertCases->bindParam('deaths', $deaths);
        $stmtInsertCases->bindParam('confirmed', $confirmed);
        $stmtInsertCases->bindParam('date', $date);

        foreach ($data1 as $case) {
            $active = $case['Active'];
            $recovered = $case['Recovered'];
            $deaths = $case['Deaths'];
            $confirmed = $case['Confirmed'];
            $date = date("Y-m-d", strtotime($case['Date']));
            $countryId = $country['id'];
            
            $stmtInsertCases->execute();
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    syncData();
}
?>