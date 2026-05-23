<?php
$conn = mysqli_connect('localhost', 'root', '', 'mis_shop');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
