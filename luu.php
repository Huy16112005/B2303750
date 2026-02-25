<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "✅ Đã vào luu.php<br>";
echo "<pre>"; print_r($_POST); echo "</pre>";
//exit;
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qlbanhang";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$date = date_create($_POST["birthday"]);

$sql = "INSERT INTO customers (fullname, email, birthday, password) 
	VALUES ('".$_POST["name"] ."', '".$_POST["email"] ."', '".$date ->format('Y-m-d') ."','".md5($_POST["password"])."' )";

if ($conn->query($sql) == TRUE) {
  echo "Them sinh vien thanh cong";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
