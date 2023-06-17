<?php

    function validate_POST($data)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }
        else{
            die('You should not temper with the form method.');
        }
    }
// echo $_POST["rating"];
// exit;

$name = validate_POST($_POST["name"]);
$email = validate_POST($_POST["email"]);
$city = validate_POST($_POST["city"]);
$rating = validate_POST($_POST["rating"]);
$comments = validate_POST($_POST["comments"]);

if (!isset($name) || !isset($email) || !isset($city) || empty($rating) || !isset($comments))
{
    die('Please set the values completely.'); 
}
global $wpdb,$table_prefix;
$table = $table_prefix."reviews";
$q = "INSERT INTO `$table` VALUES ('$name', '$email', '$city', '$rating', '$comments')";
$wpdb->query($q);

?>
<script>alert("Thanks for your review <?$_POST["name"]; ?> ");</script>
<!-- 
<html>
    <body>
    <h1>Thanks for your review</h1>
    </body>
</html> -->