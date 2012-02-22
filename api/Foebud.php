<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * File for the CiviCRM APIv2 Foebud custom functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Foebud
 *
 * @copyright Foebud e.V. 2011
 * @version $Id: PledgePayment.php 
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Utils/String.php';
require_once 'CRM/Utils/File.php';

/**
 * Get custom table and field names for custom group "Bescheinigungen"
 */
function get_docs_table()
{
  $query = "SELECT id, table_name
              FROM civicrm_custom_group
             WHERE name = 'Bescheinigungen'
           ";
  $res =  CRM_Core_DAO::executeQuery( $query );
  //  $num_rows = $res->numRows();
  $res->fetch();
  $group_id = $res->id;
  if (!$group_id) die("Benuterdefinierte Feldgruppe 'Bescheinigungen' nicht gefunden");

  $docs = array();
  $docs['table'] = $res->table_name;

  $query = "SELECT name, column_name 
              FROM civicrm_custom_field
             WHERE custom_group_id = $group_id
               AND name IN ('Z_Dateityp',
                            'Z_Datei',
                            'Z_Datum',
                            'Z_Datum_von',
                            'Z_Datum_bis',
                            'Z_Kommentar'
                           )";

  $res = CRM_Core_DAO::executeQuery( $query );
  while ($res->fetch()) {
    switch ($res->name) {
    case 'Z_Dateityp':   
      $docs['field_filetype'] = $res->column_name; 
      break;
    case 'Z_Datei':      
      $docs['field_file']     = $res->column_name; 
      break;
    case 'Z_Datum':      
      $docs['field_date']     = $res->column_name; 
      break;
    case 'Z_Datum_von':  
    case 'Z_Datum_Von':  
      $docs['field_from']     = $res->column_name; 
      break;
    case 'Z_Datum_bis':  
    case 'Z_Datum_Bis':  
      $docs['field_to']       = $res->column_name; 
      break;
    case 'Z_Kommentar':  
      $docs['field_comment']  = $res->column_name; 
      break;
    default: break;
    }
  }

  foreach(array( 'field_filetype' => 'Z_Dateityp'
               , 'field_file'     => 'Z_Datei'
               , 'field_date'     => 'Z_Datum'
               , 'field_from'     => 'Z_Datum_Von'
               , 'field_to'       => 'Z_Datum_Bis'
               , 'field_comment'  => 'Z_Kommentar') 
          as $key => $val) {
    if (!isset($docs[$key])) die("Benutzerdefiniertes Feld 'Bescheinigungen -> $val' nicht gefunden");
  }

  return $docs;
}

function civicrm_foebud_zuwendungsbescheinigung($params)
{
  _civicrm_initialize(true);

  $docs = get_docs_table();

  $result = array();

  $from_date = CRM_Utils_Array::value('from_date',   $params);
  $to_date   = CRM_Utils_Array::value('to_date',     $params);
  $ids       = CRM_Utils_Array::value('contact_id',  $params);

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);

  $date_range = date("d.m.Y", $from_ts)." - ".date("d.m.Y", $to_ts);

  // all contact IDs or only specified ones?
  if (!empty($ids)) {
    $and_contact_ids = "AND p.id IN (".join(",", (array)$ids).")";
  } else {
    $and_contact_ids = "";
  }

  $query = " SELECT p.id
                  , p.addressee_display
                  , p.display_name
                  , p.first_name
                  , p.last_name
                  , a.street_address
                  , a.supplemental_address_1
                  , a.supplemental_address_2
                  , a.postal_code
                  , a.city
                  , s.name as country
                  , DATE(c.receive_date) AS date
                  , c.total_amount
                  , if (contribution_type_id = 1, 'Geldzuwendung', 'Mitgliedsbeitrag') AS art
               FROM civicrm_contact      p
               JOIN civicrm_contribution c
                 ON p.id = c.contact_id
                AND c.contribution_status_id = 1
          LEFT JOIN civicrm_address      a
                 ON p.id = a.contact_id
                AND a.is_primary = 1
          LEFT JOIN civicrm_country s
                 ON a.country_id = s.id
          LEFT JOIN $docs[table] docs
                 ON p.id = docs.entity_id
                AND c.receive_date BETWEEN docs.$docs[field_from] AND docs.$docs[field_to]
              WHERE p.is_deleted = 0
                AND docs.id IS NULL
                AND c.receive_date BETWEEN '$from_date' AND '$to_date'
               $and_contact_ids
           ORDER BY c.contact_id
                  , c.receive_date
        
                  ";
  $last_id = -1;

  $res = CRM_Core_DAO::executeQuery( $query );
  $num_rows = $res->numRows();
  $row_count = 0;
  while ($res->fetch()) {
    $row_count++;
    if ($res->id != $last_id) {
      if ($last_id >= 0) {
        // contact_id different than last one -> render receipt for now completely
        // fetched last/previous contact
	$result[$last_id] = render_beleg_pdf($last_id, $address, $total, $items, $from_date, $to_date);
      }

      // fetch new contact/address data 
      $last_id = $res->id; 
      $total   = 0.0;
      $items   = array();
	
      $street_address = ""; 
      $tail = "";
      if (!empty($res->supplemental_address_1)) {
        $street_address .= $res->supplemental_address_1."<br/>";
      } else {
	$tail .= "<br/>";
      }
      if (!empty($res->supplemental_address_2)) {
        $street_address .= $res->supplemental_address_2."<br/>";
      } else {
        if ($res->country != "Germany") {
          if (empty($tail)) $tail .= "<br/>";
          $tail .= strtoupper($res->country);
        } else {
          $tail .= "<br/>";
        }
      }
      $street_address .= $res->street_address;

      $name = trim($res->addressee_display);
      if (empty($name)) $name =  trim($res->display_name);
      if (empty($name)) $name = trim($res->first_name)." ".trim($res->last_name);

      $address = array("contact_id"     => $res->id,
		       "street_address" => $street_address,
		       "city"           => $res->city.$tail,
		       "postal_code"    => $res->postal_code,
		       "name"           => $name
		       );
    }

    // update total sum for this contact
    $total += $res->total_amount;

    // update item list for this contact
    $items[] = array("date"   => $res->date, 
		     "amount" => $res->total_amount,
		     "art"    => $res->art
		     );
      
  }

  // all done? finish up last found contact
  if ($last_id >= 0) {
    $result[$last_id] = render_beleg_pdf($last_id, $address, $total, $items, $from_date, $to_date);
  }

  return $result;
}

function render_beleg_pdf($contact_id, $address, $total, $items, $from_date, $to_date)
{
  global $civicrm_root;

  $docs = get_docs_table();

  require_once("packages/dompdf/dompdf_config.inc.php");
  require_once("CRM/Core/DAO/File.php");
  require_once("CRM/Core/DAO/EntityFile.php");
  $config = CRM_Core_Config::singleton(true,true );

  // from date is the given date or the day following the last already
  // printed/confirmed date, whatever is later (no duplicate receipt items)
  $query = "SELECT GREATEST(MAX(DATE_ADD($docs[field_to], INTERVAL 1 DAY)), '$from_date' ) AS from_date
              FROM $docs[table]
             WHERE entity_id = $contact_id";

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);

  $res = CRM_Core_DAO::executeQuery( $query );
  $res->fetch();
  if ($res->from_date) {
    $from_date = $res->from_date;
  }

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);
 
  $template_dir = "$civicrm_root/../foebud_civicrm/templates";

  // select and set up template type
  if (count($items) > 1) {
    // more than one payment -> "Sammelbescheinigung" with itemized list
    $html = file_get_contents("$template_dir/sammel.html");

    $item_table = "<table class='grid'>\n";
    $item_table.= "<tr><th>Datum</th><th>Art der Zuwendung</th><th>Betrag der Zuwendung - in Ziffern -</th><th>- in Buchstaben -</th></tr>\n";
    foreach ($items as $item) {
      $amount = number_format($item["amount"],2,',','.');
      $date  = strtotime($item["date"]);
      $art   = $item["art"];
      $item_table.= "<tr align='right'><td>".date("j.n.Y", $date)." </td><td>$art</td><td>$amount Euro  </td><td>".num_to_text($item["amount"])." Euro </td></tr>\n";
    }
    $item_table.= "<tr align='right'><td colspan='2'></td><td><b>Summe: ".number_format($total,2,',','.')." Euro </td><td><b>".num_to_text($total)." Euro </b></td></tr>\n";
    $item_table.= "</table>\n";
    
    $html = str_replace("@items@", $item_table, $html);
  } else {
    // one payment only -> "Einzelbescheinigung"
    $html = file_get_contents("$template_dir/einzel.html");
    $html = str_replace("@date@", date("d.m.Y",strtotime($items[0]["date"])), $html);
  }

  // fill further template fields
  if (date("m-d",$from_ts) == "01-01" && date("m-d",$to_ts) == "12-31") {
    $daterange = date("Y",$from_ts);
  } else {
    $daterange = date("j.n.",$from_ts) . " bis " . date("j.n.Y",$to_ts);
  }
  $html = str_replace("@daterange@", $daterange, $html);
  $html = str_replace("@donor@", $address['name']."<br/>".$address["street_address"]."<br/>".$address["postal_code"]." ".$address["city"], $html);
  $html = str_replace("@total@", number_format($total,2,',','.'), $html);
  $html = str_replace("@totaltext@", num_to_text($total), $html);

  $html = str_replace("@today@", date("j.n.Y", time()), $html);

  if (date("m-d",$from_ts) == "01-01" && date("m-d",$to_ts) == "12-31") {
    $rangespec = date("Y",$from_ts);
  } else {
    $rangespec = date("Y-m-d",$from_ts) . "_" . date("m-d",$to_ts);
  }

  // set up file names
  $basename = CRM_Utils_File::makeFileName("Zuwendungen_".$rangespec."_".$contact_id.".pdf");
  $outfile = $config->customFileUploadDir;
  $outfile.= "/$basename";

  // render PDF receipt
  $dompdf = new DOMPDF();
  $dompdf->set_paper('a4');
  $dompdf->load_html($html);
  $dompdf->set_base_path($template_dir);
  $dompdf->render();
  $status = file_put_contents($outfile, $dompdf->output(array("compress" => 0)));

  // create CiviCRM file entity from created PDF file
  $file = new CRM_Core_DAO_File();
  $file->mime_type = 'application/pdf';
  $file->uri = $basename;
  $file->upload_date = date('Ymd');
  $file->save();
  $entityFile = new CRM_Core_DAO_EntityFile();
  $entityFile->file_id = $file->id;
  $entityFile->entity_id = $contact_id;
  $entityFile->entity_table = $docs['table'];
  $entityFile->save();

  // create custom field entry for generated file entity
  $query = "INSERT INTO $docs[table]
                    SET $docs[field_filetype] = 'Spendenbescheinigung'
                      , $docs[field_date]     = NOW()
                      , $docs[field_from]     = '$from_date'
                      , $docs[field_to]       = '$to_date'
                      , $docs[field_comment]  = ''
                      , $docs[field_file]     =  {$file->id}
                      , entity_id = $contact_id
           ";
  $res = & CRM_Core_DAO::executeQuery( $query );

  // return summary data and CiviCRM URL to generated file
  return array("contact_id"   => $contact_id, 
	       "file_id"      => $file->id, 
	       "from_date"    => $from_date, 
	       "to_date"      => $to_date, 
	       "total_amount" => $total,
	       "filename"     => "$basename",
	       "url"          => $config->userFrameworkBaseURL."/index.php?q=civicrm/file&reset=1&id=".$file->id."&eid=$contact_id");
}	

/**
 * single digit to text
*/
function num_to_text_digits($num)
{
  $digit_text = array("null","eins","zwei","drei","vier","fünf","sechs","sieben","acht","neun");

  $digits = array();

  $num = floor($num);

  while ($num > 0) {
    $rest = $num % 10;
    $num = floor($num / 10);

    echo "$rest, $num\n";

    $digits[] = $digit_text[$rest];
  }

  $digits = array_reverse($digits);

  $result =  "- ".join(" - ", $digits)." - ";

  return $result;
}

/**
* 0-999 to text
*/
function _num_to_text($num)
{
  $hundert = floor($num / 100);
  $zehn    = floor(($num - $hundert *100 ) / 10);
  $eins    = $num % 10;

  $digit_1 = array("","ein","zwei","drei","vier","fünf","sechs","sieben","acht","neun");
  $digit_10 = array("","zehn","zwanzig","dreißig","vierzig","fünfzig","sechzig","siebzig","achtzig","neunzig");

  $str = "";

  if ($hundert > 0) {
    $str .= $digit_1[$hundert]."hundert ";
  }

  if ($zehn == 0) {
    $str .= $digit_1[$eins];
  } else if ($zehn == 1) {
    if ($eins == 0) {
      $str .= "zehn";
    } else if ($eins == 1) {
      $str .= "elf";
    } else if ($eins == 2){
      $str .= "zwölf";
    } else {
      $str .= $digit_1[$eins]."zehn";
    }
  } else {
    if ($eins == 0) {
      $str .= $digit_10[$zehn];
    } else {
      $str .= $digit_1[$eins]."und".$digit_10[$zehn];
    }
  }

  return $str;
}

/** 
* general number to text conversion 
*/
function num_to_text($num)
{
  static $max_len = 1;

  $strs = array();

  while ($num > 0) {
    $strs[] = _num_to_text($num % 1000);
    $num = floor($num / 1000);
  }

  $str = "";

  if (isset($strs[2])) {
    $str .= $strs[2] . " millionen ";
  }
  if (isset($strs[1])) {
    $str .= $strs[1] . " tausend ";
  }
  if (isset($strs[0])) {
    $str .= $strs[0];
  }

  $result =  $str == "" ? "null" : trim($str);

  $len = strlen($result);
  if ($len > $max_len) {
    $max_len = $len;
  }

  return $result;
}

