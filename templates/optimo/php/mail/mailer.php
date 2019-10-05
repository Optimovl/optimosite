<?php

$subject = htmlspecialchars($_POST["Optimo"]);
$name = htmlspecialchars($_POST["name"]);
$phone = htmlspecialchars($_POST["phone"]);
$from = htmlspecialchars($_POST["email"]);
$message = htmlspecialchars($_POST["message"]);
$file = $_FILES["file"];
$to = 'nikita.khovanskiy@gmail.com';


//sender
$fromName = 'WebSite';

//email subject
//email body content
$htmlContent = " Name: $name; <br/> Phone: $phone; <br/> E-mail: $from; <br/> Message: $message <br/>";

//header for sender info
$headers = "From: $name"." <".$from.">";


//boundary 
$semi_rand = md5(time()); 
$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

//headers for attachment 
$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 

//multipart boundary 
$message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
"Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n"; 


            $message .= "--{$mime_boundary}\n";
            
        if ($file != 'null') {
        $fp =    @fopen($file,"rb");
        $data =  @fread($fp,$file['size']);

        @fclose($fp);
        $data = chunk_split(base64_encode($data));
        $message .= "Content-Type: application/octet-stream; name=\"".$file['name']."\"\n" . 
        "Content-Description: ".$file['name']."\n" .
        "Content-Disposition: attachment;\n" . " filename=\"".$file['name']."\"; size=".$file['size'].";\n" . 
        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
    }

$message .= "--{$mime_boundary}--";

        
$returnpath = "-f" . $from;
//send email
if ($mail = @mail($to, $subject, $message, $headers, $returnpath)) {
    echo 'success';
}  else {
    echo 'fail';
}

?>