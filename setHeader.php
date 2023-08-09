<?php
$consid = 23892;
$secret = '5vKB4C5C9C';
$dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
$timestamp = (string)$dateTime->getTimestamp();
$data = $consid. '&' . $timestamp;
$signature = hash_hmac('sha256', $data, $secret, true);
$encodedSignature = base64_encode($signature);
$a = "X-cons-id: ".$consid;
$b = "X-timestamp: ".$timestamp;
$c = "X-signature: ".$encodedSignature;
$d = "user_key: 84a25986e67e111e8a822b21ef346976";
echo $a."<br/>".$b."<br/>".$c."<br/>".$d."<br/>";
