<?php
//----------------------------------- Export a table from a database -----------------------------------//

// database record to be exported
$db_record = '*********';
// optional where query
$where = '*********';
// filename for export
$txt_filename = '*********'.'.txt';
// database variables
$hostname = "localhost";
$user = "*******";
$password = "********";
$database = "********";
$port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect($hostname, $user, $password, $database, $port);
if (mysqli_connect_errno()) {
	die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// query to get data from database for all (Запрос на получение данных из базы данных за весь период)
$query = mysqli_query($conn, "SELECT `****` FROM ".$db_record." ".$where);

// query to get data from database for yesterday (Запрос на получение данных из базы данных за вчерашний день)
// $query = mysqli_query($conn, "SELECT `******` FROM `********` WHERE `********` >= (CURDATE()-1) AND `*******` < CURDATE()");

$field = mysqli_field_count($conn);


// newline (seems to work both on Linux & Windows servers)
$txt_export.= '';

// loop through database query and fill export variable
while($row = mysqli_fetch_array($query)) {
	// create line with field values
	for($i = 0; $i < $field; $i++) {
		$txt_export.= ''.$row[mysqli_fetch_field_direct($query, $i)->name].'';
	}
	$txt_export.= '
';
}
// Write txt result to file
file_put_contents($txt_filename, $txt_export);
$check_filename = 'check_'.$txt_filename;


//-------------------------------------- Request for verification --------------------------------------//

// Run the list through GNU netcat
echo shell_exec("nc hash.cymru.com 43 < $txt_filename > $check_filename");


//------------------------------------- Deleting unnecessary lines -------------------------------------//

//set source file name and path
$list = array();
//read raw text as array
$list = file($check_filename) or die("Cannot read file (Удаление не нужных строк)");

function keep_no_data($line) {
	if (strpos($line, 'NO_DATA') !== false) {
		return false;
	}
	return true;
	$txt_export.= '
';
}
$arr_filtered = array_filter($list, "keep_no_data");


//-------------------------------------- Removing duplicate lines --------------------------------------//

$lines = array_unique($arr_filtered);


//----------------------------------------- Shortens the lines -----------------------------------------//

$yourstring = preg_replace('/(\b.{1,30}\s)/', "\n", $lines);
$yourstring = preg_replace( '~\n{2,}~si', "\r\n" , str_replace("\r","\n",$yourstring) );
$yourstring = preg_replace("~^(?:[\w\h\pP]('?!$')?)+~m", '\'$0\'', $yourstring);
file_put_contents('final_file.txt', $yourstring);
$final_file = $yourstring;


//-------------------------------------.Sampling from the database.-------------------------------------//

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$final_file = file('final_file.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$final_file = implode(',', $final_file);
$conn = mysqli_connect($hostname, $user, $password, $database, $port);

// create empty variable to be filled with export data
$csv_export = '';

// query to get data from database
$query = mysqli_query($conn, "SELECT `******` as '*******', `*******` as '******', `*******` as '*******', `********` as '********', REPLACE(`*******`, ';', ' :') as '*******' FROM `*******` JOIN `********` ON `********`.`*******`=`*******`.`*******` WHERE `*******` IN ($final_file)");

$field = mysqli_num_fields($query);


// create line with field names
for($i = 0; $i < $field; $i++) {
  $csv_export.= mysqli_fetch_field_direct($query, $i)->name.';';
}

// newline (seems to work both on Linux & Windows servers)
$csv_export.= '
';

// loop through database query and fill export variable
while($row = mysqli_fetch_array($query)) {
  // create line with field values
  for($i = 0; $i < $field; $i++) {
    $csv_export.= ''.$row[mysqli_fetch_field_direct($query,$i)->name].';';
  }
  $csv_export.= '
';
}

file_put_contents('Report.csv', $csv_export);

//--------------------------------------------- Send file ----------------------------------------------//

$file = "Report.csv"; // file
$mailTo = "*****@****"; // to whom
$from = "*****@****"; // from whom
$subject = "Report"; // message subject
$message = "<h1 style(font-size = 100px) >A letter was sent with an attachment $file</h1>"; // message body
$r = sendMailAttachment($mailTo, $from, $subject, $message, $file); // sending a letter with an attachment
echo ($r)?'<center><h2 class="action_title">Letter sent!<h2></center>':'Error. The letter has not been sent!';


function sendMailAttachment($mailTo, $from, $subject, $message, $file = false){
    $separator = "---"; // separator in the letter
    // Headings for the letter
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: $from\nReply-To: $from\n"; // asking who the letter is from
    $headers .= "Content-Type: multipart/mixed; boundary=\"$separator\""; // in the header specify the delimiter
    // If a letter with an attachment
    if($file){
        $bodyMail = "--$separator\n"; // the beginning of the message body, output the delimiter
        $bodyMail .= "Content-Type:text/html; charset=\"utf-8\"\n"; // letter encoding
        $bodyMail .= "Content-Transfer-Encoding: 7bit\n\n"; // Set the letter conversion
        $bodyMail .= $message."\n"; // add the text of the letter
        $bodyMail .= "--$separator\n";
        $fileRead = fopen($file, "r"); // open the file
        $contentFile = fread($fileRead, filesize($file)); // read it all the way through
        fclose($fileRead); // close the file
        $bodyMail .= "Content-Type: application/octet-stream; name==?utf-8?B?".base64_encode(basename($file))."?=\n";
        $bodyMail .= "Content-Transfer-Encoding: base64\n"; // file encoding
        $bodyMail .= "Content-Disposition: attachment; filename==?utf-8?B?".base64_encode(basename($file))."?=\n\n";
        $bodyMail .= chunk_split(base64_encode($contentFile))."\n"; // encode and attach the file
        $bodyMail .= "--".$separator ."--\n";
    // unattached letter
    }else{
        $bodyMail = $message;
    }
    $result = mail($mailTo, $subject, $bodyMail, $headers); // send mail
    return $result;
}

?>
