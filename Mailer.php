<?php
namespace modules\mailer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    /**
     * @var $setFrom = The email address of sender. Example: youremail@gmail.com
     * @var $setFromName = The name of sender. Example: Your name here
     * @var $wordWrap = Auto wrap the email body. Default is 50.
     * @var $addAddress = The email address of destination. Example: someone1@gmail.com,someone2@gmail.com,someone3@gmail.com
     * @var $addCC = Add more cc email address. Example: someone1@gmail.com,someone2@gmail.com,someone3@gmail.com
     * @var $addBCC = Add more bcc email address. Example: someone1@gmail.com,someone2@gmail.com,someone3@gmail.com
     * @var $addAttachment = Add more attachment in email. Example: C:\book.doc,C:\image.jpg,C:\video.mp4
     * @var $subject = The subject of email
     * @var $body = The body of email
     * @var $isHtml = Email will using plain text or html. Default is true.
     * @var $charSet = Set the char message. Default is using UTF-8.
     * @var $encoding = Set the encoding of message. Default is base64.
     */
    var $setFrom,$setFromName,$wordWrap=50,$addAddress,$addCC,$addBCC,$addAttachment,$subject,$body,$isHtml=true,$charSet='UTF-8',$encoding='base64';

    function __construct($settings=null) {
        if(!empty($settings)){
            $c = $settings['smtp'];
            $this->smtpHost = $c['host'];
            $this->smtpAuth = filter_var($c['auth'], FILTER_VALIDATE_BOOLEAN);
            $this->smtpSecure = $c['secure'];
            $this->smtpUsername = $c['username'];
            $this->smtpPassword = $c['password'];
            $this->smtpPort = (int)$c['port'];
            $this->smtpAutoTLS = filter_var($c['autotls'], FILTER_VALIDATE_BOOLEAN);
            $this->smtpDebug = (int)$c['debug'];
            $this->defaultNameFrom = $c['defaultnamefrom'];
        }
    }

    /**
     * Determine if string is contains matched text
     * 
     * @param match is the text to match
     * @param string is the source text
     * 
     * @return bool
     */
    public function isContains($match,$string){
        if(strpos($string,$match) !== false){
            return true;
        }
        return false;
    }

    public function send(){
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = $this->smtpDebug;        // Enable verbose debug output
            $mail->isSMTP();                            // Set mailer to use SMTP
            $mail->Host = $this->smtpHost;              // Specify main and backup SMTP servers
            $mail->SMTPAuth = $this->smtpAuth;          // Enable SMTP authentication
            $mail->SMTPAutoTLS = $this->smtpAutoTLS;    // SMTP will send using tls protocol as default
            $mail->Username = $this->smtpUsername;      // SMTP username
            $mail->Password = $this->smtpPassword;      // SMTP password
            $mail->SMTPSecure = $this->smtpSecure;      // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $this->smtpPort;              // TCP port to connect to
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        
            //Recipients
            $mail->setFrom(filter_var((empty($this->setFrom)?$this->smtpUsername:$this->setFrom), FILTER_SANITIZE_EMAIL), filter_var((empty($this->setFromName)?$this->defaultNameFrom:$this->setFromName), FILTER_SANITIZE_STRING));
            $mail->addReplyTo(filter_var((empty($this->setFrom)?$this->smtpUsername:$this->setFrom), FILTER_SANITIZE_EMAIL), filter_var((empty($this->setFromName)?$this->defaultNameFrom:$this->setFromName), FILTER_SANITIZE_STRING));
            if (!empty($this->addAddress)){
                if(is_array($this->addAddress)){
                    $address = $this->addAddress;
                } else {
                    $address = preg_split( "/[;,#]/", preg_replace('/\s+/', '', $this->addAddress) );
                }
                foreach ($address as $value) {
                    if(!empty($value)){
                        $mail->addAddress(filter_var($value, FILTER_SANITIZE_EMAIL));
                    }
                }
            }
            if (!empty($this->addCC)){
                if(is_array($this->addCC)) {
                    $cc = $this->addCC;
                } else {
                    $cc = preg_split( "/[;,#]/", preg_replace('/\s+/', '', $this->addCC) );
                }
                foreach ($cc as $value) {
                    if(!empty($value)){
                        $mail->addCC(filter_var($value, FILTER_SANITIZE_EMAIL));
                    }
                }
            }
            if (!empty($this->addBCC)){
                if(is_array($this->addBCC)){
                    $bcc = $this->addBCC;
                } else {
                    $bcc = preg_split( "/[;,#]/", preg_replace('/\s+/', '', $this->addBCC) );
                }
                foreach ($bcc as $value) {
                    if(!empty($value)){
                        $mail->addBCC(filter_var($value, FILTER_SANITIZE_EMAIL));
                    }
                }
            }
        
            //Attachments
            if (!empty($this->addAttachment)){
                if(is_array($this->addAttachment)){
                    $attachment = $this->addAttachment;
                } else {
                    $attachment = preg_split( "/[;,#]/", preg_replace('/\s+/', '', $this->addAttachment) );
                }
                foreach ($attachment as $value) {
                    if(!empty($value)){
                        $mail->addAttachment(filter_var($value, FILTER_SANITIZE_STRING));
                    }
                }
            }
        
            //Content
            $mail->CharSet = $this->charSet;
            $mail->Encoding = $this->encoding;
            $mail->WordWrap = $this->wordWrap;
            $mail->isHTML($this->isHtml);                                           // Set email format to HTML
            $mail->Subject = filter_var($this->subject, FILTER_SANITIZE_STRING);
            $mail->Body = $this->body;
            $mail->AltBody = filter_var($this->body, FILTER_SANITIZE_STRING);
            
            if($this->isContains('localhost',$this->setFrom) || $this->isContains('localhost',$this->setFromName)){
                return [
                    'status' => 'error',
                    'message' => 'Sending message from localhost is not allowed!'
                ];
            }

            if($mail->send()){
                return [
                    'status' => 'success',
                    'message' => 'Message has been sent!'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to send the message. Error: '. $mail->ErrorInfo
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Message could not be sent. Mailer Error: '. $e->getMessage()
            ];
        }
    }
}