<?php

$argumen='python Tarik_Data.py "'.$_POST["filename"].'" "'.$_POST["keyword"].'" '.$_POST["limit"];

passthru($argumen);

$argumen='python csv_to_mysql.py "'.$_POST["filename"];

passthru($argumen);

echo "Tarik Data Selesai";
?>