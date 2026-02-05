<?php
$mysqli = new mysqli('localhost', 'root', '', 'sikoper3');
$result = $mysqli->query('DESCRIBE tbsimpanan');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
$mysqli->close();
