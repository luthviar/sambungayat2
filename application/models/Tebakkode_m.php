<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tebakkode_m extends CI_Model {

  function __construct(){
    parent::__construct();
    $this->load->database();
  }

  // Events Log
  function log_events($signature, $body)
  {
    $this->db->set('signature', $signature)
    ->set('events', $body)
    ->insert('eventlog');

    return $this->db->insert_id();
  }

  // Users
  function getUser($userId)
  {
    $data = $this->db->where('user_id', $userId)->get('users')->row_array();
    if(count($data) > 0) return $data;
    return false;
  }
 
  function saveUser($profile)
  {
    $this->db->set('user_id', $profile['userId'])
      ->set('display_name', $profile['displayName'])
      ->set('pictureUrl', $profile['pictureUrl'])
      ->set('statusMessage', $profile['statusMessage'])
      ->insert('users');
 
    return $this->db->insert_id();
  }

  // Question
  function getQuestion($questionNum)
  {
    $data = $this->db->where('number', $questionNum)
      ->get('questions')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

  function getQuestQuran($questQuranNum)
  {
    $data = $this->db->where('number', $questQuranNum)
      ->get('questions')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

  function getMaxRow()
  {
    $data = $this->db->order_by('rowlabel', 'DESC')->limit(1)->row_array();
    if(count($data)>0) return $data;
    return false;
  }

  function randomQuran($surat, $rowlabel) {
    $data = $this->db->where('no_surat', $surat)
      ->where('rowlabel', $rowlabel)
      ->get('questions')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }
 
  function isAnswerEqual($number, $answer)
  {
    $this->db->where('number', $number)
      ->where('answer', $answer);
 
    if(count($this->db->get('questions')->row()) > 0)
      return true;
 
    return false;
  }
 
  function setUserProgress($user_id, $newNumber)
  {
    $this->db->set('number', $newNumber)
      ->where('user_id', $user_id)
      ->update('users');
 
    return $this->db->affected_rows();
  }
 
  function setScore($user_id, $score)
  {
    $this->db->set('score', $score)
      ->where('user_id', $user_id)
      ->update('users');
 
    return $this->db->affected_rows();
  }

  function getQuran() {
    $data = $this->db->where('id', 75124)
    ->get('qurans')->row_array();
    if(count($data) > 0) return $data;
    return false;
  }

  function saveRowLabel($id_rowlabel, $id_quran)
  {
    $this->db->set('rowlabel', $id_rowlabel)
      ->where('id', $id_quran)
      ->update('qurans');
 
    return $this->db->affected_rows();
  }
  
  function saveInfoSurat($rowlabel, $banyak_ayat, $no_surat)
  {
    $this->db->set('surat_ke', $no_surat)
    ->set('banyak_ayat', $banyak_ayat)
    ->set('max_rowlabel', $rowlabel)
    ->insert('info_surat');

    return $this->db->insert_id();
  }
  

  function getSurat($id_quran)
  {
    $data = $this->db->where('id', $id_quran)
      ->get('qurans')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

  function getSuratKe($no_surat)
  {
    $data = $this->db->where('no_surat', $no_surat)
      ->get('info_surat')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

  function getNextSurat($id_quran)
  {
    $data = $this->db->where('id', $id_quran)
      ->get('qurans')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

  function getInfoSurat($no_surat)
  {
    $data = $this->db->where('no_surat', $no_surat)
      ->get('qurans')
      ->order_by('no_surat','DESC')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

  function getQuranQuest($start_ayat, $start_rowlabel)
  {
    $data = $this->db->where('no_surat', $start_ayat)
      ->where('rowlabel', $start_rowlabel)
      ->get('qurans')
      ->row_array();
 
    if(count($data)>0) return $data;
    return false;
  }

}
