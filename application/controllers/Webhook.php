<?php defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

class Webhook extends CI_Controller {

  private $bot;
  private $events;
  private $signature;
  private $user;

  function __construct()
  {
    parent::__construct();
    $this->load->model('tebakkode_m');

    // create bot object
    $httpClient = new CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
    $this->bot  = new LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
  }

  public function index()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo "Hello Coders!";
      header('HTTP/1.1 400 Only POST method allowed');
      exit;
    }

    // get request
    $body = file_get_contents('php://input');
    $this->signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : "-";
    $this->events = json_decode($body, true);

    // log every event requests
    $this->tebakkode_m->log_events($this->signature, $body);

    if(is_array($this->events['events'])){
      foreach ($this->events['events'] as $event){
        // your code here
        // skip group and room event
        if(! isset($event['source']['userId'])) continue;
 
        // get user data from database
        $this->user = $this->tebakkode_m->getUser($event['source']['userId']);
 
        // if user not registered
        if(!$this->user) $this->followCallback($event);
        else {
          // respond event
          if($event['type'] == 'message'){
            if(method_exists($this, $event['message']['type'].'Message')){
              $this->{$event['message']['type'].'Message'}($event);
            }
          } else {
            if(method_exists($this, $event['type'].'Callback')){
              $this->{$event['type'].'Callback'}($event);
            }
          }
        }

      }
    }

  } // end of index.php

  private function followCallback($event){
     $res = $this->bot->getProfile($event['source']['userId']);
    if ($res->isSucceeded())
    {
      $profile = $res->getJSONDecodedBody();
 
      // create welcome message
      $message  = "Salam kenal, " . $profile['displayName'] . "!\n";
      $message .= "Silakan kirim pesan \"MULAIKUIS\" atau MULAIQURAN untuk memulai kuis.";
      $textMessageBuilder = new TextMessageBuilder($message);
 
      // create sticker message
      $stickerMessageBuilder = new StickerMessageBuilder(1, 3);
 
      // merge all message
      $multiMessageBuilder = new MultiMessageBuilder();
      $multiMessageBuilder->add($textMessageBuilder);
      $multiMessageBuilder->add($stickerMessageBuilder);
 
      // send reply message
      $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);
 
      // save user data
      $this->tebakkode_m->saveUser($profile);
    }
  }

  private function textMessage($event)
  {
    $userMessage = $event['message']['text'];
    // if($this->user['number'] == 0 && strtolower($userMessage) !== '/kick')
    if(strtolower($userMessage) !== '/kick')
    {
      if(strtolower($userMessage) == 'mulaikuis') {
        // reset score
        $this->tebakkode_m->setScore($this->user['user_id'], 0);
        // update number progress
        $this->tebakkode_m->setUserProgress($this->user['user_id'], 1);
        // send question no.1
        $this->sendQuestion($event['replyToken'], 1);
      }  else if(strtolower($userMessage) == 'mulaiquran') {
         // $question = $this->tebakkode_m->getQuran();
         // $append  = $question['word']. "\n";
         // $append .= $question['id'] . ' ' .$question['no_surat']. ' '. $question['trans'];
         // $textMessageBuilder = new TextMessageBuilder($append);

        // reset score
        // $this->tebakkode_m->setScore($this->user['user_id'], 0);
        // update number progress
        // $this->tebakkode_m->setUserProgress($this->user['user_id'], 1);
        // send question no.1
        $this->sendQuranQuest($event['replyToken'], 1, 78); 
             
         
         
         // // send message
         // $response = $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
      } else {
        $message = 'Silakan kirim pesan "MULAI" untuk memulai kuis. by rizqy';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
      }
    // if user already begin test
    } else if(strtolower($userMessage) == '/kick') {
          if ($event['source']['type'] == 'room') {
            $this->bot->leaveRoom($event['source']['roomId']);
          } else if($event['source']['type'] == 'group') {
            $this->bot->leaveGroup($event['source']['groupId']);
          } else if($event['source']['type'] == 'user') {
            $message = 'Maaf Anda tidak bisa melakukan perintah kick';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
          }
    } else {
      // $this->checkAnswer($userMessage, $event['replyToken']);
    }
  }


 private function stickerMessage($event)
  {
    // create sticker message
    $stickerMessageBuilder = new StickerMessageBuilder(1, 106);
 
    // create text message
    $message = 'Silakan kirim pesan "MULAI" untuk memulai kuis.';
    $textMessageBuilder = new TextMessageBuilder($message);
 
    // merge all message
    $multiMessageBuilder = new MultiMessageBuilder();
    $multiMessageBuilder->add($stickerMessageBuilder);
    $multiMessageBuilder->add($textMessageBuilder);
 
    // send message
    $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);
  }

   public function sendQuestion($replyToken, $questionNum=1)
  {
    // get question from database
    $question = $this->tebakkode_m->getQuestion($questionNum);
 
    // prepare answer options
    for($opsi = "a"; $opsi <= "d"; $opsi++) {
        if(!empty($question['option_'.$opsi]))
            $options[] = new MessageTemplateActionBuilder($question['option_'.$opsi], $question['option_'.$opsi]);
    }
 
    // prepare button template
    $buttonTemplate = new ButtonTemplateBuilder($question['number']."/10", $question['text'], $question['image'], $options);
 
    // build message
    $messageBuilder = new TemplateMessageBuilder("Gunakan mobile app untuk melihat soal", $buttonTemplate);
 
    // send message
    $response = $this->bot->replyMessage($replyToken, $messageBuilder);
  }

  public function sendQuranQuest($replyToken, $questionNum=1, $userMessage)
  {
    if ($userMessage >= 78 && $userMessage <= 114 && is_numeric($userMessage)) {
      
      // get question from database
      $info_surat = $this->tebakkode_m->getInfoSurat($userMessage);
      $start_ayat = random(1,$info_surat['ayat_surat']);
      $start_rowlabel = random(1,$info_surat['rowlabel']-5);
      
      $quranQuest = $this->tebakkode_m->getQuranQuest($start_ayat, $start_rowlabel);
      // $quranQuest['word'];

      $textMessageBuilder = new TextMessageBuilder($quranQuest['word']);
   
    //   // prepare answer options
    //   for($opsi = "a"; $opsi <= "d"; $opsi++) {       
    //           $options[] = new MessageTemplateActionBuilder($quranQuest[++$start_rowlabel], $start_rowlabel);
    //   }
   
    //   // prepare button template
    //   $buttonTemplate = new ButtonTemplateBuilder($question['number']."/10", $quranQuest['word'], $options);
   
    //   // build message
    //   $messageBuilder = new TemplateMessageBuilder("Gunakan mobile app untuk melihat soal", $buttonTemplate);
   
    //   // send message
    //   $response = $this->bot->replyMessage($replyToken, $messageBuilder);

    // } else {
    //   // create text message
    //   $message = 'Maaf inputan harus 78 hingga 114 (berupa angka saja)';
      // $textMessageBuilder = new TextMessageBuilder($message);

      // send message
      $response = $this->bot->replyMessage($replyToken, $textMessageBuilder);
    }
    
  }

  public function insert_info_surat()
  {

   $firstId = 75124;
   $nextId = 75125;

  $i=1;
while($nextId !== 77431) {
 

 // $this->tebakkode_m->saveRowLabel($i, $firstId);
 $i += 1;
  
  if ($nextId == 77431) {
    break;
  } else if ($this->tebakkode_m->getSurat($firstId)['no_surat'] !== $this->tebakkode_m->getNextSurat($nextId)['no_surat'] ) {
    
    $banyak_ayat = $this->tebakkode_m->getSurat($firstId)['ayat_surat'];
    $no_surat = $this->tebakkode_m->getSurat($firstId)['no_surat'];
    $this->tebakkode_m->saveInfoSurat($i, $banyak_ayat, $no_surat);
    $i=1;
   
  }
  
 $firstId += 1;
 $nextId += 1;
 
}
  }

  public function insert()
  {
       $firstId = 75124;
       $nextId = 75125;

      $i=1;
    while($nextId !== 77431) {
     

     // $this->tebakkode_m->saveRowLabel($i, $firstId);
     $i += 1;
      
      if ($nextId == 77431) {
        break;
      } else if ($this->tebakkode_m->getSurat($firstId)['no_surat'] !== $this->tebakkode_m->getNextSurat($nextId)['no_surat'] ) {
        $this->tebakkode_m->saveRowLabel($i, $firstId);
        $i=1;
       
      }
      
     $firstId += 1;
     $nextId += 1;
     
   }
  }

   private function checkAnswer($message, $replyToken)
  {
    // if answer is true, increment score
    if($this->tebakkode_m->isAnswerEqual($this->user['number'], $message)){
      $this->user['score']++;
      $this->tebakkode_m->setScore($this->user['user_id'], $this->user['score']);
    }
 
    if($this->user['number'] < 10)
    {
      // update number progress
     $this->tebakkode_m->setUserProgress($this->user['user_id'], $this->user['number'] + 1);
 
      // send next question
      $this->sendQuestion($replyToken, $this->user['number'] + 1);
    }
    else {
      // create user score message
      $message = 'Skormu '. $this->user['score'];
      $textMessageBuilder1 = new TextMessageBuilder($message);
 
      // create sticker message
      $stickerId = ($this->user['score'] < 8) ? 100 : 114;
      $stickerMessageBuilder = new StickerMessageBuilder(1, $stickerId);
 
      // create play again message
      $message = ($this->user['score'] < 8) ?
'Wkwkwk! Nyerah? Ketik "MULAI" untuk bermain lagi!':
'Great! Mantap bro! Ketik "MULAI" untuk bermain lagi!';
      $textMessageBuilder2 = new TextMessageBuilder($message);
 
      // merge all message
      $multiMessageBuilder = new MultiMessageBuilder();
      $multiMessageBuilder->add($textMessageBuilder1);
      $multiMessageBuilder->add($stickerMessageBuilder);
      $multiMessageBuilder->add($textMessageBuilder2);
 
      // send reply message
      $this->bot->replyMessage($replyToken, $multiMessageBuilder);
      $this->tebakkode_m->setUserProgress($this->user['user_id'], 0);
    }
  }

}
