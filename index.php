<?php

try {
    $pdo = new PDO("mysql:host=localhost;dbname=covid19", "root", "");
} catch(PDOException $e) {
    die("can not connect to db");
}

$sql = "SELECT countries.name, 
last_two_days_data.today_active,
last_two_days_data.today_recovered,
last_two_days_data.today_deaths,
last_two_days_data.today_confirmed,
last_two_days_data.yesterday_active,
last_two_days_data.yesterday_recovered,
last_two_days_data.yesterday_deaths,
last_two_days_data.yesterday_confirmed
FROM (
    SELECT * FROM
    (
      SELECT 
        cases.active as today_active, 
        cases.recovered as today_recovered, 
        cases.deaths as today_deaths, 
        cases.confirmed as today_confirmed, 
        cases.country_id,
        (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) row_number_today
      FROM cases
    ) AS today_data
    JOIN (
      SELECT 
        cases.active as yesterday_active, 
        cases.recovered as yesterday_recovered, 
        cases.deaths as yesterday_deaths, 
        cases.confirmed as yesterday_confirmed, 
        cases.country_id as c_id,
        (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) row_number_yesterday
      FROM cases
    ) as yesterday_data

     ON 
        today_data.country_id = yesterday_data.c_id 
        AND yesterday_data.row_number_yesterday = today_data.row_number_today + 1
        AND today_data.row_number_today <> 1 
     GROUP BY today_data.country_id
) as last_two_days_data 
JOIN countries ON last_two_days_data.country_id = countries.id";
$stmt = $pdo->query($sql);
$stmt1 = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <title>Covid-19 Tracker</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
          <a class="navbar-brand">Covid Tracker</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
              <li class="nav-item me-2">
                <a class="nav-link" aria-current="page" href="#all/total">Home</a>
              </li>
              <li class="nav-item" id="sync">
              <button type="button" id="syncdata" class="btn ">Sync Data</button>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <h1 class="text-center my-5 main-title ">Covid-19 Tracker</h1>
<div class="container">
    <form method="post">
    <div class="row">
        <div class="col-md-4 text-center d-grid gap-2"><input class="btn btn-danger" type="submit" name="dailyAll" value="Daily cases"/></div>
        <div class="col-md-4 text-center d-grid gap-2"><input class="btn btn-danger" type="submit" name="monthlyAll" value="Monthly cases"/></div>
        <div class="col-md-4 text-center d-grid gap-2"><input class="btn btn-danger" type="submit" name="90dayAll" value="Quartely cases"/></div>
        </div>
        </form>
</div>
<br>
<h3 class="text-center fw-bold text-uppercase m-5 text-shadow1">Coronavirus Cases</h3>
    <?php
    if ((isset($_POST['dailyAll']))||(!isset($_POST['dailyAll']) && !isset($_POST['monthlyAll']) && !isset($_POST['90dayAll']))) {
        $sqlToday = "SELECT * 
FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
INNER JOIN countries ON countries.id = partitioned_table.country_id
WHERE partitioned_table.row_num = 1";
        $stmt5 = $pdo->query($sqlToday);
        if ($stmt5->rowCount()) {
            $dataToday = $stmt5->fetch();
            $date = $dataToday['date'];
            $sql = "SELECT sum(confirmed),sum(deaths),sum(recovered),sum(active) FROM cases WHERE date='$date'";
            $stmtSum = $pdo->query($sql);
            while ($country = $stmtSum->fetch()) {
                echo "  <h2 class='text-center mt-3'>Confirmed:</h2>";
                echo "<h3  class='text-center text-info font-weight-bold'>" . number_format($country['sum(confirmed)']) . "</h3 >";
                echo "<h2 class='text-center'>Deaths:</h2>";
                echo "<h3  class='text-center text-secondary font-weight-bold'>" . number_format($country['sum(deaths)']) . " </h3 >";
                echo "<h2  class='text-center'>Recovered:</h2>";
                echo "<h3  class='text-center text-success font-weight-bold'>" . number_format($country['sum(recovered)']) . "</h3 >";
                echo "<h2  class='text-center'>Active:</h2>";
                echo "<h3  class='text-center text-danger font-weight-bold'>" . number_format($country['sum(active)']) . "</h3 >";
            }
        }
      
      }

    
    if (isset($_POST['monthlyAll'])) {
        $sqlToday = "SELECT * 
FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
INNER JOIN countries ON countries.id = partitioned_table.country_id
WHERE partitioned_table.row_num = 1";
        $sqlLastMonth = "SELECT * 
FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
INNER JOIN countries ON countries.id = partitioned_table.country_id
WHERE partitioned_table.row_num = 30";
        $stmt5 = $pdo->query($sqlToday);
        $stmtLast = $pdo->query($sqlLastMonth);
        if ($stmt5->rowCount()) {
            $dataToday = $stmt5->fetch();
            $date = $dataToday['date'];
        }
        if ($stmtLast->rowCount()) {
            $dataLast = $stmtLast->fetch();
            $dateLastMonth = $dataLast['date'];
        }
        // echo $dateLastMonth;
        $sql = "SELECT sum(confirmed),sum(deaths),sum(recovered),sum(active) FROM cases WHERE date BETWEEN '$dateLastMonth' AND '$date'";
        $stmtSum = $pdo->query($sql);
        while ($country = $stmtSum->fetch()) {
            echo "  <h2 class='text-center mt-3'>Confirmed:</h2>";
            echo "<h3  class='text-center text-info font-weight-bold'>" . number_format($country['sum(confirmed)']) . "</h3 >";
            echo "<h2 class='text-center'>Deaths:</h2>";
            echo "<h3  class='text-center text-secondary font-weight-bold'>" . number_format($country['sum(deaths)']) . " </h3 >";
            echo "<h2  class='text-center'>Recovered:</h2>";
            echo "<h3  class='text-center text-success font-weight-bold'>" . number_format($country['sum(recovered)']) . "</h3 >";
            echo "<h2  class='text-center'>Active:</h2>";
            echo "<h3  class='text-center text-danger font-weight-bold'>" . number_format($country['sum(active)']) . "</h3 >";
        }
    }
    if (isset($_POST['90dayAll'])) {
        $sqlToday = "SELECT * 
   FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
   INNER JOIN countries ON countries.id = partitioned_table.country_id
   WHERE partitioned_table.row_num = 1";
        $sqlLast3Months = "SELECT * 
   FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
   INNER JOIN countries ON countries.id = partitioned_table.country_id
   WHERE partitioned_table.row_num = 90";
        $stmt5 = $pdo->query($sqlToday);
        $stmtLast3 = $pdo->query($sqlLast3Months);
        if ($stmt5->rowCount()) {
            $dataToday = $stmt5->fetch();
            $date = $dataToday['date'];
        }
        if ($stmtLast3->rowCount()) {
            $dataLast3 = $stmtLast3->fetch();
            $dateLast3Months = $dataLast3['date'];
        }
        // echo $dateLastMonth;
        $sql = "SELECT sum(confirmed),sum(deaths),sum(recovered),sum(active) FROM cases WHERE date BETWEEN '$dateLast3Months' AND '$date'";
        $stmtSum = $pdo->query($sql);
        while ($country = $stmtSum->fetch()) {
            echo "  <h2 class='text-center mt-3'>Confirmed:</h2>";
            echo "<h3  class='text-center text-info font-weight-bold'>" . number_format($country['sum(confirmed)']) . "</h3 >";
            echo "<h2 class='text-center'>Deaths:</h2>";
            echo "<h3  class='text-center text-secondary font-weight-bold'>" . number_format($country['sum(deaths)']) . " </h3 >";
            echo "<h2  class='text-center'>Recovered:</h2>";
            echo "<h3  class='text-center text-success font-weight-bold'>" . number_format($country['sum(recovered)']) . "</h3 >";
            echo "<h2  class='text-center'>Active:</h2>";
            echo "<h3  class='text-center text-danger font-weight-bold'>" . number_format($country['sum(active)']) . "</h3 >";
        }
    }
   
    ?>
<?php
 set_time_limit(500);

 try {
     $pdo = new PDO("mysql:host=localhost;dbname=covid19", "root", "");
 } catch(PDOException $e) {
     die("can not connect to db");
 }
                        $query = $pdo->query("SELECT name FROM countries ");
                        $rowCount = $query->rowCount();
                        ?>
                        <div class=" container">
                        <div class="row">
                        
        <form method="post">
    <div class="row">
        <div class="col-md-2 text-center d-grid gap-2"><input class="btn btn-dark" type="submit" name="daily" value="Daily cases"/></div>
        <div class="col-md-2 text-center d-grid gap-2"><input class="btn btn-dark" type="submit" name="monthly" value="Monthly cases"/></div>
        <div class="col-md-2 text-center d-grid gap-2"><input class="btn btn-dark" type="submit" name="quartely" value="Quartely cases"/></div>
        <div class="col-md-2 text-center d-grid gap-2"><input list="country" id="search" placeholder="Select Country...">
        </div>
        </form>
        </div><br>
    </div>

                        <datalist class="p-3" name="country" id="country">
                            <option class="end" value="">Select Country...</option>
                            <?php
                            if ($rowCount > 0) {
                                while ($row = $query->fetch()) {
                                   
                                  echo '<option class="fw-bold ' . $row['name'] . '" value="' . $row['name'] . '">' . $row['name'] .'</option>';
                                }
                            } else {
                                echo '<option value="">Country not available</option>';
                            }
                    
                            ?>
                        </datalist>
<table class="table table-hover container bg-light" id="countryTable">
                    <tr class="tHead">
                        <th class="fw-bold">Country</th>
                        <th>Total Cases</th>
                        <th>Active Cases</th>
                        <th>Deaths</th>
                        <th>Recovered</th>
                        <th>New cases</th>
                        <th>New deaths</th>
                        <th>New recovered</th>
                    </tr>
        <?php
        //daily
        while($country = $stmt->fetch()) {
            $todayConfirmed = $country['today_confirmed'];
            $todayActive = $country['today_active'];
            $todayDeaths = $country['today_deaths'];
            $todayRecovered = $country['today_recovered'];

            $yesterdayConfirmed = $country['yesterday_confirmed'];
            $yesterdayDeaths = $country['yesterday_deaths'];
            $yesterdayRecovered = $country['yesterday_recovered'];
            
            if(isset($_POST['daily'])) {
            echo "<tr>
                <td>{$country['name']}</td>
                <td>{$todayConfirmed}</td>
                <td>{$todayActive}</td>
                <td>{$todayDeaths}</td>
                <td>{$todayRecovered}</td>
                <td>".($todayConfirmed - $yesterdayConfirmed)."</td>
                <td>".($todayDeaths - $yesterdayDeaths)."</td>
                <td>".($todayRecovered - $yesterdayRecovered)."</td>
            </tr>";
           }
           else if (!isset($_POST['daily']) && !isset($_POST['monthly']) && !isset($_POST['quartely'])) {
            echo "<tr>
            <td>{$country['name']}</td>
            <td>{$todayConfirmed}</td>
            <td>{$todayActive}</td>
            <td>{$todayDeaths}</td>
            <td>{$todayRecovered}</td>
            <td>".($todayConfirmed - $yesterdayConfirmed)."</td>
            <td>".($todayDeaths - $yesterdayDeaths)."</td>
            <td>".($todayRecovered - $yesterdayRecovered)."</td>
        </tr>";
           }
          }
      //monthly
$sqlToday = "SELECT * 
    FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
    INNER JOIN countries ON countries.id = partitioned_table.country_id
    WHERE partitioned_table.row_num = 1";
        $stmt = $pdo->query($sqlToday);
        while($country = $stmt->fetch()) {
        $todayTotal = $country['confirmed'];
        $todayActive = $country['active'];
        $todayDeaths = $country['deaths'];
        $todayRecovered = $country['recovered'];
        $dateToday = $country['date'];
        $newCases = '/';
        $newDeaths = '/';
        $newRecovered = '/';
        $sqlLastDayOfMonth= "SELECT * FROM cases WHERE country_id = {$country['id']} ORDER BY `date` DESC LIMIT 1 OFFSET 30";
        $stmtLastDayOfMonth = $pdo->query($sqlLastDayOfMonth);
        if($stmtLastDayOfMonth->rowCount()) {
            $dataLastDayOfMonth = $stmtLastDayOfMonth->fetch();
            $dateLastMonth = $dataLastDayOfMonth['date'];
            $newCases = $todayTotal - $dataLastDayOfMonth['confirmed'];
            $newDeaths = $todayDeaths - $dataLastDayOfMonth['deaths'];
            $newRecovered = $todayRecovered - $dataLastDayOfMonth['recovered'];
        }
        $id = $country['id'];
        $sqlForLastMonth = "SELECT sum(confirmed) as sum_confirmed,sum(deaths) as sum_deaths,
        sum(recovered) as sum_recovered,sum(active)  as sum_active
        FROM cases WHERE country_id = $id and date BETWEEN  '$dateLastMonth' AND  '$dateToday'";
         $stmtSumLastMonth = $pdo->query($sqlForLastMonth);
         if($stmtSumLastMonth->rowCount()) {
            $dataLastMonth = $stmtSumLastMonth->fetch();
            $sum_confirmed = $dataLastMonth['sum_confirmed'];
            $sum_deaths = $dataLastMonth['sum_deaths'];
            $sum_recovered =  $dataLastMonth['sum_recovered'];
            $sum_active =  $dataLastMonth['sum_active'];
        }
        if(isset($_POST['monthly'])) {
          echo "<tr class='row_graph'>
          <td>".utf8_encode($country['name']) ."</td>
          <td>{$sum_confirmed}</td>
          <td>{$sum_active}</td>
          <td>{$sum_deaths}</td>
          <td>{$sum_recovered}</td>
          <td>{$newCases}</td>
          <td>{$newDeaths}</td>
          <td>{$newRecovered}</td>
          </tr>";
      }
    }
    //quartely
   $sqlToday = "SELECT * 
       FROM ( SELECT *, (ROW_NUMBER() OVER (PARTITION BY country_id ORDER BY date DESC)) as row_num FROM cases ) partitioned_table 
       INNER JOIN countries ON countries.id = partitioned_table.country_id
       WHERE partitioned_table.row_num = 1";
           $stmt = $pdo->query($sqlToday);
           while($country = $stmt->fetch()) {
           $todayTotal = $country['confirmed'];
           $todayActive = $country['active'];
           $todayDeaths = $country['deaths'];
           $todayRecovered = $country['recovered'];
           $dateToday = $country['date'];
           $newCases = '/';
           $newDeaths = '/';
           $newRecovered = '/';
           $sqlLastDayOfMonth= "SELECT * FROM cases WHERE country_id = {$country['id']} ORDER BY `date` DESC LIMIT 1 OFFSET 90";
           $stmtLastDayOfMonth = $pdo->query($sqlLastDayOfMonth);
           if($stmtLastDayOfMonth->rowCount()) {
               $dataLastDayOfMonth = $stmtLastDayOfMonth->fetch();
               $dateLastMonth = $dataLastDayOfMonth['date'];
               $newCases = $todayTotal - $dataLastDayOfMonth['confirmed'];
               $newDeaths = $todayDeaths - $dataLastDayOfMonth['deaths'];
               $newRecovered = $todayRecovered - $dataLastDayOfMonth['recovered'];
           }
           $id = $country['id'];
           $sqlForLastMonth = "SELECT sum(confirmed) as sum_confirmed,sum(deaths) as sum_deaths,
           sum(recovered) as sum_recovered,sum(active)  as sum_active
           FROM cases WHERE country_id = $id and date BETWEEN  '$dateLastMonth' AND  '$dateToday'";
            $stmtSumLastMonth = $pdo->query($sqlForLastMonth);
            if($stmtSumLastMonth->rowCount()) {
               $dataLastMonth = $stmtSumLastMonth->fetch();
               $sum_confirmed = $dataLastMonth['sum_confirmed'];
               $sum_deaths = $dataLastMonth['sum_deaths'];
               $sum_recovered =  $dataLastMonth['sum_recovered'];
               $sum_active =  $dataLastMonth['sum_active'];
           }
           if(isset($_POST['quartely'])) {
             echo "<tr class='row_graph'>
             <td>".utf8_encode($country['name']) ."</td>
             <td>{$sum_confirmed}</td>
             <td>{$sum_active}</td>
             <td>{$sum_deaths}</td>
             <td>{$sum_recovered}</td>
             <td>{$newCases}</td>
             <td>{$newDeaths}</td>
             <td>{$newRecovered}</td>
             </tr>";
         }
       }

        ?>
         </table>
    

    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
    <script src="java.js"></script>
    <script src="sync.js"></script>
   

</body>
</html>