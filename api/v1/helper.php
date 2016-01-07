<?php

function init() {
    try {
        $config = include(dirname(__FILE__).'/../config.php');
        $mysql = new PDO(
            'mysql:host='.$config['host'].';dbname='.$config['dbname'].';charset=utf8',
            $config['user'],
            $config['password']
        );
        return $mysql;
    } catch(PDOException $ex) {
        die('connection error');
    }
}

function execute($stmt, $params = null) {
    return $stmt->execute($params);
}

function getAll($stmt, $params = null) {
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFetch($stmt, $params = null) {
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCount($mysql, $sub_query, $parameters = []) {
    $stmt = $mysql->prepare(
        "SELECT COUNT(*) AS 'result'
        FROM $sub_query"
    );
    $stmt->execute($parameters);
    return $stmt->fetch(PDO::FETCH_ASSOC)['result'];
}

function createResponse($response, $data, $status = 200) {
    $response = $response->withStatus($status);
    $response = $response->withHeader('content-type', 'application/json');
    $response = $response->withHeader('charset', 'iso-8859-1');

    $response->write( json_encode($data, JSON_NUMERIC_CHECK) );
    return $response;
}


// ---------

function validateActivationToken($mysql, $token) {
	return (getCount($mysql, "users WHERE activationtoken = ?", [$token]) > 0);
}

function fetchUserDetails($mysql, $token = null, $email = null) {
    $stmt = $mysql->prepare(
	    "SELECT *
	    FROM users
	    WHERE email = :email
	        OR activationtoken = :token"
	);
	$stmt->bindValue(':email', sanitize($email));
	$stmt->bindValue(':token', sanitize($token));

	return getFetch($stmt);
}

function fetchUserDetailsByMail($mysql, $email) {
    return fetchUserDetails($mysql, null, $email);
}

function fetchUserDetailsByToken($mysql, $token) {
    return fetchUserDetails($mysql, $token, null);
}

function flagLostpasswordRequest($mysql, $username, $value) {
    $stmt = $mysql->prepare(
        "UPDATE users
        SET LostpasswordRequest = :request
        WHERE username_clean = :username
        LIMIT 1"
    );
    $stmt->bindValue(':request', $value);
    $stmt->bindValue(':username', sanitize($username));
    return execute($stmt);
}


// --------

function getURL() {
    $protocol = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, 5)) == 'https://' ? 'https://' : 'http://';
    return $protocol.$_SERVER['HTTP_HOST'].'/';
}

function sanitize($str) {
    return strtolower(strip_tags(trim($str)));
}

function generateHash($plainText, $salt = null) {
    if ($salt === null) {
        $salt = md5(uniqid(rand(), true));
    }
    $salt = substr($salt, 0, 25);
	return $salt.sha1($salt.$plainText);
}

function getUniqueCode($length = "") {
    $code = md5(uniqid(rand(), true));
    if ($length != '') {
        return substr($code, 0, $length);
    }
    return $code;
}

function generateActivationToken($mysql) {
    $gen;
    do {
        $gen = md5(uniqid(mt_rand(), false));
    } while (getCount($mysql, "users WHERE activationtoken = ?", [$gen]) > 0);

    return $gen;
}



// ------ Mail ------

function sendTemplateMail($template, $destination, $subject, $additionalHooks, $senderName = 'Crucio', $senderMail = 'noreply@crucio-leipzig.de') {
    $emailDate = date('l \\t\h\e jS');
    $message = file_get_contents('../mail-templates/'.$template);
    $message = str_replace(array("#WEBSITENAME#", "#WEBSITEURL#", "#DATE#"), array('Crucio', $website_url, $emailDate), $message);
    $message = str_replace($additionalHooks['searchStrs'], $additionalHooks['subjectStrs'], $message);

    return sendMail($destination, $subject, $message, $senderName, $senderMail);
}

function sendMail($destination, $subject, $message, $senderName, $senderMail) {
    $header = "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: text/html\r\n";
    $header .= 'FROM: '.$senderName.' <'.$senderMail.'>';

    $data['status'] = mail($destination, $subject, $message, $header);
    $data['sender'] = $senderName;
    $data['destination'] = $destination;
    return $data;
}

?>