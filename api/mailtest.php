<?php
$to = "breanna@glyphstone.net";
$now = date('Y-m-d H:i:s') ;
$subject = "This is a test mail";
$message = "Another test mail sent $now";
$from = "admin@dottify.org";
$headers = "From:" . $from;
$res = mail($to,$subject,$message,$headers);
echo "Mail Sent: $res";
?>