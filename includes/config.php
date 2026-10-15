<?php
session_start();

const APP_NAME = 'WAVES';
const APP_VERSION = '1.0.0';
const DB_HOST = 'localhost';
const DB_NAME = 'waves';
const DB_USER = 'root';
const DB_PASS = 'Oswald11.?';
const BASE_URL = 'http://localhost/WAVES';

function redirect($path) {
    header('Location: ' . $path);
    exit;
}
