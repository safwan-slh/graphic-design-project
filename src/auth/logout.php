<?php
session_start();
session_unset();
session_destroy();
header("Location: /graphic-design/src/client/index.php");
exit();
