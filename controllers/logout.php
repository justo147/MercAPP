<?php
session_start();
session_destroy();
header("location: ../public/views/landing_page.php");