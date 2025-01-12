<?php
$servername = "localhost";
$username = "cron";
$password = "1234";
$dbname = "asterisk";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve date range from form submission
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$sql = "
    SELECT
        campaign_id,
        SUBSTRING(phone_number, 1, 3) AS area_code,
        COUNT(*) AS call_count
    FROM
        vicidial_log
    WHERE
        LENGTH(phone_number) = 10
        AND call_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY
        campaign_id,
        area_code
    ORDER BY
        campaign_id,
        area_code;
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Prepare CSV file
    $csv_file = fopen('report.csv', 'w');
    fputcsv($csv_file, ['Campaign ID', 'Area Code', 'Call Count']);

    echo "<form method='GET' action=''>
            <label for='start_date'>Start Date:</label>
            <input type='date' id='start_date' name='start_date' value='$start_date'>
            <label for='end_date'>End Date:</label>
            <input type='date' id='end_date' name='end_date' value='$end_date'>
            <input type='submit' value='Filter'>
          </form>";

    echo "<table border='1'>
            <tr>
                <th>Campaign ID</th>
                <th>Area Code</th>
                <th>Call Count</th>
            </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["campaign_id"] . "</td>
                <td>" . $row["area_code"] . "</td>
                <td>" . $row["call_count"] . "</td>
              </tr>";

        // Write to CSV file
        fputcsv($csv_file, [$row["campaign_id"], $row["area_code"], $row["call_count"]]);
    }

    echo "</table>";
    fclose($csv_file);

    echo "<a href='report.csv' download>Download CSV Report</a>";
} else {
    echo "0 results";
}

$conn->close();
?>
