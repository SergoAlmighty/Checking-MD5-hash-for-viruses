<?php
//----------------------------------- Export a table from a database -----------------------------------//

// database record to be exported
$db_record = 'user_info';
// optional where query
$where = 'md5';
// filename for export
$txt_filename = 'scanned-files/data_md5'.'.txt';
// database variables
$hostname = "localhost";

$user = "*******";
$password = "********";
$database = "********";

$port = 3306;

$conn = mysqli_connect($hostname, $user, $password, $database, $port);
if (mysqli_connect_errno()) {
   die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// query to get data from database for yesterday
$query = mysqli_query($conn, "SELECT `md5` FROM `user_info` WHERE `**********` BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE() - INTERVAL 1 SECOND");

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


// ------------------------------- Splitting a large file into small files of 1000 lines ------------------------------------- //

$handle = fopen($txt_filename,'r');  //open big file with fopen
$f = 1; //new file number

while(!feof($handle))
{
   $name_small_file = 'small_data_md5_'.$f.'.txt';  //create new file to write to with file number
   $small_data_file = fopen('/scanned-files/small_file/'.$name_small_file,'w'); // open new file to write to with file number
   fwrite($small_data_file,'BEGIN
');
   for($i = 1; $i <= 1000; $i++) //for 1000 lines
   {
      $import = fgets($handle);
      fwrite($small_data_file,$import);
      if(feof($handle))
      {break;} //If file ends, break loop
   }
   fwrite($small_data_file,'END');

   fclose($small_data_file);


//----------------------------------------- Sending files for virus scanning via api -----------------------------------------//

   $name_check_file = 'check_small_data_md5_'.$f.'.txt';
   $check_small_data_file = '/scanned-files/check_file/'.$name_check_file;
   echo shell_exec("netcat hash.cymru.com 43 < /scanned-files/small_file/$name_small_file > $check_small_data_file");  //Run the list through GNU netcat


//----------------------------------------- BEGIN Deleting hash without viruses -----------------------------------------//

//Set source file name and path
   $list = array();
//Read raw text as array
   $list = file('/scanned-files/check_file/'.$name_check_file) or die("Deletes the first 2 lines.");

   $list = array_slice($list, 2);  //Deletes the first 2 lines.
   file_put_contents('/scanned-files/check_file/'.$name_check_file, $list);
   // MySQL small_data_file insertion stuff goes here
   $f++; //Increment small_data_file number
}
fclose($handle);

$name_check_final_file = 'check_final_data_md5.txt';
$final_file = '/scanned-files/final_file/'.$name_check_final_file;
echo shell_exec("cat /scanned-files/check_file/*.txt > $final_file");  //Run the list through GNU netcat
$list = array();

//Read raw text as array
$list = file('/scanned-files/final_file/'.$name_check_final_file) or die("Cannot read file. (Deleting unnecessary lines)");

function keep_no_data($line) {
   if (strpos($line, 'NO_DATA') !== false) {  //Removes lines with "NO_DATA" in them.
      return false;
   }
   return true;
   $txt_export.= '
';
}
$arr_filtered = array_filter($list, "keep_no_data");


//-----------------------------------------END Deleting hash without viruses -----------------------------------------//

$lines = array_unique($arr_filtered);  //Removing duplicate lines


//----------------------------------------- Shortens the lines -----------------------------------------//

$yourstring = preg_replace('/(\b.{1,30}\s)/', "\n", $lines);  //We get the first 30 characters of the string (equal to the MD5 hash size).
// $yourstring = str_replace("d41d8cd98f00b204e9800998ecf8427e","", $yourstring);  // Ignore hash 'd41d8cd98f00b204e9800998ecf8427e'.
$yourstring = preg_replace('~\n{2,}~si', "\r\n" , str_replace("\r","\n",$yourstring));
$yourstring = preg_replace("~^(?:[\w\h\pP]('?!$')?)+~m", '\'$0\'', $yourstring);
$name_final_file = 'final_file_data_md5.txt';
file_put_contents('/scanned-files/' .$name_final_file, $yourstring);
$final_small_file = $yourstring;


//-------------------------------------.Sampling from the database.-------------------------------------//

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$final_file = file('/scanned-files/final_file_data_md5.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$final_file = implode(',', $final_file);
$conn = mysqli_connect($hostname, $user, $password, $database, $port);

// create empty variable to be filled with export data
$csv_export = '';

// query to get data from database
$query = mysqli_query($conn, "SELECT `******` as '******', `********` as '*******', `********` as '*******', `********` as '********', REPLACE(`*******`, ';', ' :') as '********' FROM `user_info` JOIN `********` ON `*******`.`*******`=`********`.`*******` WHERE `md5` IN ($final_file)");

$field = mysqli_num_fields($query);

// create line with field names
for($i = 0; $i < $field; $i++) {
   $csv_export.= mysqli_fetch_field_direct($query, $i)->name.',';
}

// newline (seems to work both on Linux & Windows servers)
$csv_export.= '
';

// loop through database query and fill export variable
while($row = mysqli_fetch_array($query)) {
   // create line with field values
   for($i = 0; $i < $field; $i++) {
      $csv_export.= ''.$row[mysqli_fetch_field_direct($query,$i)->name].',';
   }
   $csv_export.= '
';
}

$report = '/scanned-files/Report_columns '.date('Y.m.d').'.csv';
file_put_contents($report, $csv_export);


//----------------------------------------- Removing unnecessary txt files ---------------------------------------------//

$files = glob('/scanned-files/*.txt'); // get all file names
foreach($files as $file){ // iterate files
   if(is_file($file)) {
      unlink($file); // delete file
   }
}

$files = glob('/scanned-files/small_file/*.txt'); // get all file names
foreach($files as $file){ // iterate files
   if(is_file($file)) {
      unlink($file); // delete file
   }
}

$files = glob('/scanned-files/check_file/*.txt'); // get all file names
foreach($files as $file){ // iterate files
   if(is_file($file)) {
      unlink($file); // delete file
   }
}

$files = glob('/scanned-files/final_file/*.txt'); // get all file names
foreach($files as $file){ // iterate files
   if(is_file($file)) {
      unlink($file); // delete file
   }
}


/*
//--------------------------------------------- Send file ----------------------------------------------//

$file = "Report.csv"; // file
$mailTo = "****@*****"; // to whom
$from = "****@*****"; // from whom
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
*/
?>