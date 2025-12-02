<?php
session_start();
session_destroy();
header("location: ../public/views/landing-page.php");