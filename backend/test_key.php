<?php
$key = 'AIzaSyBSxTZ5e-m9T1juAtEAi0IevWWyYwVu9R0';
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo $result;
}
curl_close($ch);
