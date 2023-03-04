<?php

// Connect to the database
$db_host = 'localhost';
$db_name = 'website_data';
$db_user = 'username';
$db_pass = 'password';
$db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);

// Create tables for images and documents if they don't already exist
$db->exec('CREATE TABLE IF NOT EXISTS images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            url VARCHAR(255),
            data LONGBLOB
        )');

$db->exec('CREATE TABLE IF NOT EXISTS documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            url VARCHAR(255),
            data LONGBLOB
        )');

// Specify the URL of the website to scrape
$url = 'internship.html';

// Send a request to the URL using cURL
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
curl_close($curl);

// Parse the HTML content of the response using DOMDocument
$dom = new DOMDocument();
$dom->loadHTML($response);

// Find all images on the page and save them to the database
$image_tags = $dom->getElementsByTagName('img');
foreach ($image_tags as $image_tag) {
    $image_url = $image_tag->getAttribute('src');
    $image_data = file_get_contents($image_url);
    $image_title = basename($image_url);
    $db->prepare('INSERT INTO images (title, url, data)
                  VALUES (:title, :url, :data)')
       ->execute(['title' => $image_title, 'url' => $image_url, 'data' => $image_data]);
}

// Find all links on the page that lead to doc files and save them to the database
$doc_links = $dom->getElementsByTagName('a');
foreach ($doc_links as $doc_link) {
    $doc_url = $doc_link->getAttribute('href');
    if (preg_match('/\.doc$/', $doc_url)) {
        $doc_data = file_get_contents($doc_url);
        $doc_title = basename($doc_url);
        $db->prepare('INSERT INTO documents (title, url, data)
                      VALUES (:title, :url, :data)')
           ->execute(['title' => $doc_title, 'url' => $doc_url, 'data' => $doc_data]);
    }
}

// Close the database connection
$db = null;

?>
