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

// Retrieve date range and campaign from form submission
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$campaign = isset($_GET['campaign']) ? $_GET['campaign'] : '';

// Fetch campaigns for the dropdown list
$campaigns_result = $conn->query("SELECT campaign_id, campaign_name FROM vicidial_campaigns");

$sql = "
    SELECT
        SUBSTRING(vl.phone_number, 1, 3) AS area_code,
        COUNT(*) AS call_count,
        CEIL(COUNT(*) / (6 * 50)) AS dids_needed
    FROM
        vicidial_list vl
    JOIN
        vicidial_campaigns vc ON vl.campaign_id = vc.campaign_id
    WHERE
        LENGTH(vl.phone_number) = 10
        AND vl.last_local_call_time BETWEEN '$start_date' AND '$end_date'
";

if ($campaign != '') {
    $sql .= " AND vl.campaign_id = '$campaign'";
}

$sql .= " GROUP BY area_code";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Prepare CSV file
    $csv_file = fopen('report.csv', 'w');
    fputcsv($csv_file, ['Area Code', 'Call Count', 'DIDs Needed']);

    echo "<form method='GET' action=''>
            <label for='start_date'>Start Date:</label>
            <input type='date' id='start_date' name='start_date' value='$start_date'>
            <label for='end_date'>End Date:</label>
            <input type='date' id='end_date' name='end_date' value='$end_date'>
            <label for='campaign'>Campaign:</label>
            <select id='campaign' name='campaign'>
                <option value=''>All Campaigns</option>";

    while ($campaign_row = $campaigns_result->fetch_assoc()) {
        $selected = ($campaign_row['campaign_id'] == $campaign) ? 'selected' : '';
        echo "<option value='" . $campaign_row['campaign_id'] . "' $selected>" . $campaign_row['campaign_name'] . "</option>";
    }
    
    echo "</select>
            <input type='submit' value='Filter'>
          </form>";

    echo "<table border='1'>
            <tr>
                <th>Area Code</th>
                <th>Call Count</th>
                <th>DIDs Needed</th>
            </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["area_code"] . "</td>
                <td>" . $row["call_count"] . "</td>
                <td>" . $row["dids_needed"] . "</td>
              </tr>";
        
        // Write to CSV file
        fputcsv($csv_file, [$row["area_code"], $row["call_count"], $row["dids_needed"]]);
    }

    echo "</table>";
    fclose($csv_file);

    echo "<a href='report.csv' download>Download CSV Report</a>";
} else {
    echo "0 results";
}

$conn->close();
?>
