<?php
/*
    sfe.donationreceipts extension for CiviCRM
    Copyright (C) 2011,2012 FoeBuD e.V.
    Copyright (C) 2012 Software fuer Engagierte e.V.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define('CUSTOM_TABLE_NAME', 'Bescheinigungen');

function get_custom_fields_meta()
{
  return array(
    'field_filetype' => array(
      'name' => 'Z_Dateityp',
      'label' => 'Z_Dateityp',
      'data_type' => 'String',
      'html_type' => 'Text',
      'weight' => '1',
      'is_active' => '1',
    ),
    'field_file' => array(
      'name' => 'Z_Datei',
      'label' => 'Z_Datei',
      'data_type' => 'File',
      'html_type' => 'File',
      'weight' => '2',
      'is_active' => '1',
    ),
    'field_date' => array(
      'name' => 'Z_Datum',
      'label' => 'Z_Datum',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '3',
      'is_active' => '1',
    ),
    'field_from' => array(
      'name' => 'Z_Datum_Von',
      'label' => 'Z_Datum_Von',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '4',
      'is_active' => '1',
    ),
    'field_to' => array(
      'name' => 'Z_Datum_Bis',
      'label' => 'Z_Datum_Bis',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '5',
      'is_active' => '1',
    ),
    'field_comment' => array(
      'name' => 'Z_Kommentar',
      'label' => 'Z_Kommentar',
      'data_type' => 'Memo',
      'html_type' => 'TextArea',
      'weight' => '6',
      'is_active' => '1',
    ),
  );
}

/* Create custom data group and field for the receipts, unless the group already exists. */
function setup_custom_data()
{
  $existing = civicrm_api("CustomGroup", "get", array('version' => '3', 'name' => CUSTOM_TABLE_NAME));
  if (!$existing['count']) {
    $fields = array_values(get_custom_fields_meta());    /* Strip symbolic keys, as these confuse the API. */
    $new = civicrm_api(
      "CustomGroup",
      "create",
      array(
        'version' => '3',
        'name' => CUSTOM_TABLE_NAME,
        'title' => 'Bescheinigungen',
        'extends' => 'Contact',
        'style' => 'Tab',
        'collapse_display' => '0',
        'is_active' => '1',
        'is_multiple' => '1',
        'created_date' => date('Y-m-d h:i:s'),
        'api.CustomField.create' => $fields    /* Chained API call, using custom_group_id created by outer call. */
      )
    );
    if (civicrm_error($new))
      throw new Exception($new['error_message']);
  }    /* if !$existing */
}    /* setup_custom_data() */

/**
 * Get custom table and field DB names for custom group "Bescheinigungen"
 */
function get_docs_table()
{
  /* Custom field mapping: symbolic keys we use locally to refer to fields => names by which the fields are known to CiviCRM. */
  $field_mappings = array_map(function ($field) { return $field['name']; }, get_custom_fields_meta());

  $result = civicrm_api(
    'CustomGroup',
    'get',
    array(
      'version' => '3',
      'name' => CUSTOM_TABLE_NAME,
      'api.CustomField.get' => array(    /* Chained API call, using custom_group_id retrieved by outer call. */
        'name' => array('IN' => $field_mappings)    /* Only get relevant fields, filtering by the custom field name. */
      )
    )
  );

  if (!$result['count'])
    die('Benuterdefinierte Feldgruppe "'.CUSTOM_TABLE_NAME.'" nicht gefunden.');

  $result_group = $result['values'][$result['id']];

  $docs = array();    /* Custom field mapping: local keys => DB table/field names. */
  $docs['table'] = $result_group['table_name'];

  foreach ($result_group['api.CustomField.get']['values'] AS $result_field) {
    $field_key = array_search($result_field['name'], $field_mappings);
    $docs[$field_key] = $result_field['column_name'];
  }

  $missing = array_diff_key($field_mappings, $docs);
  if ($missing)
    die('Benutzerdefiniertes Feldgruppe "'.CUSTOM_TABLE_NAME.'" unvollstaendig: "' . implode('", "', $missing) . '" nicht gefunden.');

  return $docs;
}

function generate_receipts($params)
{
  $docs = get_docs_table();

  $result = array();

  $from_date = CRM_Utils_Array::value('from_date',   $params);
  $to_date   = CRM_Utils_Array::value('to_date',     $params);
  $ids       = CRM_Utils_Array::value('contact_id',  $params);
  $comment   = CRM_Utils_Array::value('comment',     $params);

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);

  $date_range = date("d.m.Y", $from_ts)." - ".date("d.m.Y", $to_ts);

  // all contact IDs or only specified ones?
  if (!empty($ids)) {
    $and_contact_ids = "AND p.id IN (".join(",", (array)$ids).")";
  } else {
    // Only create receipts for contacts having an address.
    $and_contact_ids = "AND a.id IS NOT NULL";
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
	$result[$last_id] = render_beleg_pdf($last_id, $address, $total, $items, $from_date, $to_date, $comment);
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
    $result[$last_id] = render_beleg_pdf($last_id, $address, $total, $items, $from_date, $to_date, $comment);
  }

  return $result;
}

function render_beleg_pdf($contact_id, $address, $total, $items, $from_date, $to_date, $comment)
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
 
  $template_dir = __DIR__ . "/templates";

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
                      , $docs[field_comment]  = '$comment'
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

