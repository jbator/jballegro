<?php

include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/Allegrowebapi.class.php');

/**
 * Klasa do pobierania danych dla modułu jballegro
 * 
 * @author jbator.pl
 */
class Jb extends Module {

  protected $testmode;
  protected $aid;
  protected $alogin;
  protected $apass;
  protected $akey;
  protected $atemplate;
  protected $acountry;
  protected $allegro;
  protected $webapierror;

  public function getCategories($idParent = 0) {
    $cats = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'allegro_cat where parent=' . $idParent . ' ORDER BY position;');
    return $cats;
  }

  public function buildCategoriesHtml($categories) {
    $html = '<option value="">---</option>';

    foreach ($categories as $category) {
      $html .= "<option value='" . $category['id'] . "'>" . $category['name'] . "</option>";
    }

    return $html;
  }

  public function getProduct($id) {

    if ($id) {
      $query = 'SELECT p.price, p.id_tax_rules_group, l.name, l.description FROM ' . _DB_PREFIX_ . 'product p LEFT JOIN ' . _DB_PREFIX_ . 'product_lang l ON p.id_product = l.id_product where p.id_product = ' . $id . ' and l.id_lang = 6;';
      $prod = Db::getInstance()->ExecuteS($query);

      // check provision
      $this->provision = htmlentities(Configuration::get('JB_APROVISION'), ENT_QUOTES, 'UTF-8');
      
      // TODO
      $id_currency = 4;
      $specific_price = null;
      $price = Product::priceCalculation(1, $id, null, 14, 0, 0, $id_currency, _PS_DEFAULT_CUSTOMER_GROUP_, 1, true, 6, false, true, true, $specific_price, true);
      
      if ($this->provision && is_numeric($this->provision)) 
      {
        $old_price = $price;
        $price = $price * (1 + ($this->provision/100));
      }
      if ($prod[0]['description'] == '')
        $desc = $prod[0]['name'];
      else
        $desc = $prod[0]['description'];

      $data = array(
          'name' => $prod[0]['name'],
          'price' => round($price, 2),
          'desc' => '<h1>'.$prod[0]['name'].'</h1><br />'.$desc
      );

      if ($this->provision && is_numeric($this->provision))
      {
        $data['old_price'] = $old_price;
      }
      
      return $data;
    }
  }

  public function initAllegro() {

    $this->testmode = htmlentities(Configuration::get('JB_ALLEGRO_TESTMODE'), ENT_QUOTES, 'UTF-8');
    $this->aid = htmlentities(Configuration::get('JB_ALLEGRO_ID'), ENT_QUOTES, 'UTF-8');
    $this->alogin = htmlentities(Configuration::get('JB_ALLEGRO_LOGIN'), ENT_QUOTES, 'UTF-8');
    $this->apass = htmlentities(Configuration::get('JB_ALLEGRO_PASS'), ENT_QUOTES, 'UTF-8');
    $this->akey = htmlentities(Configuration::get('JB_ALLEGRO_KEY'), ENT_QUOTES, 'UTF-8');
    $this->atemplate = base64_decode(Configuration::get('JB_ALLEGRO_TEMPLATE'));
    $this->acountry = htmlentities(Configuration::get('JB_ALLEGRO_COUNTRY'), ENT_QUOTES, 'UTF-8');

    try {
      $this->allegro = new Allegrowebapi($this->aid, $this->akey, $this->alogin, $this->apass, $this->acountry);

      $this->allegro->login(true);

      return true;
    }
    catch (SoapFault $fault) {

      $this->webapierror = $fault->faultstring;
      return false;
    }
  }

  public function closeAuction($id) {

    $query = 'SELECT * FROM ' . _DB_PREFIX_ . 'allegro WHERE allegro_id = ' . $id . ';';
    $result = Db::getInstance()->ExecuteS($query);

    if (count($result) > 0) {
      $hid = $result[0]['id'];
      try {
        $this->initAllegro();
        $this->allegro->finishItem(number_format($result[0]['allegro_id'], 0, '.', ''));
        $query = 'UPDATE ' . _DB_PREFIX_ . 'allegro SET status = 0 WHERE id = ' . $hid . ';';
        Db::getInstance()->Execute($query);
        $msg = 'Aukcja ' . $id . ' została zakończona';
        return array('status' => 'success', 'msg' => $msg);
      }
      catch (SoapFault $e) {
        $msg = 'Wystąpił błąd: ' . $e->faultstring;
        return array('status' => 'fail', 'msg' => $msg);
      }
    }
  }

  public function checkByQuantity() {
    $query = 'SELECT a.*, p.active, p.quantity FROM ' . _DB_PREFIX_ . 'allegro a, ' . _DB_PREFIX_ . 'product p, ' . _DB_PREFIX_ . 'product_lang l where a.id_product = p.id_product and p.id_product = l.id_product and a.status = 1 and id_lang = 6 order by a.date_add ASC;';
    $result = Db::getInstance()->ExecuteS($query);
    $this->initAllegro();
    $errors = array();
    $i = 0;

    foreach ($result as $row) {
      if ($row['quantity'] == 0) {
        try {
          $this->allegro->finishItem(number_format($row['allegro_id'], 0, '.', ''));

          $query = 'UPDATE ' . _DB_PREFIX_ . 'allegro SET status = 0, date_upd = NOW() WHERE id = ' . $row['id'] . ';';
          Db::getInstance()->Execute($query);

          $this->writeLog('Zamkniecie aukcji ' . $row['id'] . '-' . $row['allegro_id'] . '-' . $row['id_product']);

          $i++;
        }
        catch (SoapFault $e) {
          $errors[] = $row['id'] . ': ' . $e->faultstring;
          $this->writeLog('Blad: ' . $row['id'] . '-' . $row['allegro_id'] . '-' . $row['id_product'] . ': ' . $e->faultstring);
        }
      }
    }
    
    return array('closed' => $i, 'errors' => $errors);
  }

  public function checkByAllegro() {
    $query = 'SELECT a.*, p.active, p.quantity FROM ' . _DB_PREFIX_ . 'allegro a, ' . _DB_PREFIX_ . 'product p, ' . _DB_PREFIX_ . 'product_lang l where a.id_product = p.id_product and p.id_product = l.id_product and a.status = 1 and id_lang = 6 order by a.date_add ASC;';
    $result = Db::getInstance()->ExecuteS($query);
    $this->initAllegro();
    $errors = array();
    $i = 0;
    $j = 0;

    foreach ($result as $row) {
      try {
        // pobranie informacji z allegro o danej aukcji
        $data = $this->allegro->getMyAccountData('sell', array($row['allegro_id']));
        $closed = false;
        if (!$data) {
          // sprawdz czy sprzedane
          $data = $this->allegro->getMyAccountData('sold', array($row['allegro_id']));
          if (!$data) $data = $this->allegro->getMyAccountData('not_sold', array($row['allegro_id']));
          $closed = true;
        }
        //echo 'cl ' . $closed . '<br />';
        //echo '<pre>';
        //print_r($data);
        //echo '</pre>';

        if ($data) {
          $data = $this->allegro->objectToArray($data);
          $data = $data[0]['my-account-array'];

          // liczba aktualnie sprzedanych
          $sold = $data[17];
          // liczba dostepnych na aukcji
          $avail = $data[5];

          // jesli liczba wczesniej sprzedanych sie zmenila to zrob aktualizacje stanów oraz historii auckji
          if ($sold != $row['sold']) {
            // aktualina liczba produktu na stanie
            $current_q = $row['quantity'] - ($sold - $row['sold']);

            if ($closed || $sold == $avail) {
              // jesli liczba sprzedanych jest taka sama jak liczba wystawionych to zmien status aukcji w bazie na zakonczoną
              $query = 'UPDATE ' . _DB_PREFIX_ . 'allegro SET sold = ' . $sold . ', status = 0, date_upd = NOW() WHERE id = ' . $row['id'] . ';';
              Db::getInstance()->Execute($query);
            }
            else {
              // aktualizacja liczby sprzedanych
              $query = 'UPDATE ' . _DB_PREFIX_ . 'allegro SET sold = ' . $sold . ', date_upd = NOW() WHERE id = ' . $row['id'] . ';';
              Db::getInstance()->Execute($query);

              if ($current_q <= 0) {
                // gdy liczba na stanie = 0 to zakoncz aukcje allegro
                $this->allegro->finishItem(number_format($row['allegro_id'], 0, '.', ''));
                $this->writeLog('Zamkniecie aukcji ' . $row['id'] . '-' . $row['allegro_id'] . '-' . $row['id_product']);
              }
            }

            // aktualziacja stanu produktu na magazynie
            $query = 'UPDATE ' . _DB_PREFIX_ . 'product SET quantity = ' . $current_q . ', date_upd = NOW() WHERE id_product = ' . $row['id_product'] . ';';
            Db::getInstance()->Execute($query);

            $j++;

            $this->writeLog('Aktualizacja aukcji ' . $row['id'] . '-' . $row['allegro_id'] . '-' . $row['id_product'] . ' | bylo:' . $row['quantity'] . ' | jest:' . $current_q . ' | sprzed:' . $sold);
          }
          else if ($closed) {
            // jesli liczba sprzedanych jest taka sama jak liczba wystawionych to zmien status aukcji w bazie na zakonczoną
            $query = 'UPDATE ' . _DB_PREFIX_ . 'allegro SET status = 0, date_upd = NOW() WHERE id = ' . $row['id'] . ';';
            Db::getInstance()->Execute($query);
          }
        }
      }
      catch (SoapFault $e) {
        $errors[] = $row['id'] . ': ' . $e->faultstring;
        $this->writeLog('Blad: ' . $row['id'] . '-' . $row['allegro_id'] . '-' . $row['id_product'] . ': ' . $e->faultstring);
        $i++;
      }
    }

    return array('closed' => $i, 'updated' => $j, 'errors' => $errors);
  }

  private function writeLog($string) {
    $log = fopen("allegro-cron.log", 'a+');
    fwrite($log, '[' . date('Y-m-d H:i:s') . ']' . $string . "\n");
    fclose($log);
  }

}

?>
