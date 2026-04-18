<?php
$key = 'AIzaSyC2VD2idwJh_RhEe84ZFb_IgH37RmK8OJQ';
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . $key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
echo $result;
