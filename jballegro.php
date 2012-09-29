<?php

error_reporting(E_ALL);

set_time_limit(36000);
// TODO: lepsze zarzadzanie sciezkami
require_once getcwd() . '/../modules/jballegro/Allegrowebapi.class.php';
require_once getcwd() . '/../modules/jballegro/Jb.class.php';

/**
 * Klasa do obsługi modułu integracji z allegro w sklepie internetowym Prestashop
 * 
 * @author jbator.pl
 */
class Jballegro extends Module
{

    protected $testmode;
    protected $aid;
    protected $alogin;
    protected $apass;
    protected $akey;
    protected $atemplate;
    protected $acountry;
    protected $aFields = array();
    protected $aCategories = array();
    // informacja czy konfiguracja allegro jest poprawna
    protected $allegro;
    protected $error;
    protected $errors = array();
    protected $webapierror;
    protected $aprzes1;
    protected $aprzes2;
    protected $aprzes3;
    protected $aprzes4;
    protected $aprzes5;
    protected $aprzes6;
    protected $aprzes7;
    protected $aprzes8;
    protected $aprzes9;
    protected $aprzes10;
    // prowizja allegro
    protected $provision;
    protected $province;

    /**
     * Inicjacja modułu
     */
    public function __construct()
    {

        $this->name = 'jballegro';
        $this->tab = '';
        $this->version = 0.6;
        $this->author = 'jbator.pl';
        $this->need_instance = 0;
        $this->error = false;

        parent::__construct();

        $this->displayName = $this->l('Jballegro');
        $this->description = $this->l('Integracja prestashop z allegro');

        $this->testmode = htmlentities(Configuration::get('JB_ALLEGRO_TESTMODE'), ENT_QUOTES, 'UTF-8');
        $this->aid = htmlentities(Configuration::get('JB_ALLEGRO_ID'), ENT_QUOTES, 'UTF-8');
        $this->alogin = htmlentities(Configuration::get('JB_ALLEGRO_LOGIN'), ENT_QUOTES, 'UTF-8');
        $this->apass = htmlentities(Configuration::get('JB_ALLEGRO_PASS'), ENT_QUOTES, 'UTF-8');
        $this->akey = htmlentities(Configuration::get('JB_ALLEGRO_KEY'), ENT_QUOTES, 'UTF-8');
        $this->atemplate = base64_decode(Configuration::get('JB_ALLEGRO_TEMPLATE'));
        $this->acountry = htmlentities(Configuration::get('JB_ALLEGRO_COUNTRY'), ENT_QUOTES, 'UTF-8');
        $this->province = htmlentities(Configuration::get('JB_APROVINCE'), ENT_QUOTES, 'UTF-8');

        $this->aprzes1 = htmlentities(Configuration::get('JB_APRZES1'), ENT_QUOTES, 'UTF-8');
        $this->aprzes2 = htmlentities(Configuration::get('JB_APRZES2'), ENT_QUOTES, 'UTF-8');
        $this->aprzes3 = htmlentities(Configuration::get('JB_APRZES3'), ENT_QUOTES, 'UTF-8');
        $this->aprzes4 = htmlentities(Configuration::get('JB_APRZES4'), ENT_QUOTES, 'UTF-8');
        $this->aprzes5 = htmlentities(Configuration::get('JB_APRZES5'), ENT_QUOTES, 'UTF-8');
        $this->aprzes6 = htmlentities(Configuration::get('JB_APRZES6'), ENT_QUOTES, 'UTF-8');
        $this->aprzes7 = htmlentities(Configuration::get('JB_APRZES7'), ENT_QUOTES, 'UTF-8');
        $this->aprzes8 = htmlentities(Configuration::get('JB_APRZES8'), ENT_QUOTES, 'UTF-8');
        $this->aprzes9 = htmlentities(Configuration::get('JB_APRZES9'), ENT_QUOTES, 'UTF-8');
        $this->aprzes10 = htmlentities(Configuration::get('JB_APRZES10'), ENT_QUOTES, 'UTF-8');

        $this->provision = htmlentities(Configuration::get('JB_APROVISION'), ENT_QUOTES, 'UTF-8');

        if (!Tools::isSubmit('submitConf'))
            $this->initAllegro();
    }

    /**
     * Próba ustanowienia połączenia z allegro
     */
    public function initAllegro()
    {
        
        if ($this->aid != '' && $this->alogin && $this->apass && $this->akey && $this->acountry)
        {
            try
            {
                $this->allegro = new Allegrowebapi($this->aid, $this->akey, $this->alogin, $this->apass, $this->acountry);

                $this->allegro->login(true);

                $this->buildAllegroData();
                $this->aCategories = $this->getCategories();
                return true;
            }
            catch (SoapFault $fault)
            {
                die($fault->faultstring);
                $this->webapierror = $fault->faultstring;
                return false;
            }
        }
        else
            return false;
    }

    /**
     * Pobranie kategori allegro z lokalnej bazy danych (cache)
     * @param type $idParent
     * @return type 
     */
    public function getCategories($idParent = 0)
    {
        $cats = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'allegro_cat where parent=' . $idParent . ' ORDER BY position;');
        return $cats;
    }

    /**
     * Pobiera dane allegro do bazy (kategorie) oraz do pliku (pola formularza)
     * w celu przyśpieszenia działania - coś na wzór lokalnego cache
     */
    public function buildAllegroData()
    {
        $catCount = Db::getInstance()->ExecuteS('SELECT count(*) as n FROM ' . _DB_PREFIX_ . 'allegro_cat;');
        if ($catCount[0]['n'] == 0)
        {
            $cats = Allegrowebapi::objectToArray($this->allegro->getCatsData());
            foreach ($cats['cats-list'] as $cat)
            {
                Db::getInstance()->Execute('INSERT INTO ' . _DB_PREFIX_ . 'allegro_cat VALUES (' . $cat['cat-id'] . ', \'' . addslashes($cat['cat-name']) . '\', ' . $cat['cat-parent'] . ', ' . $cat['cat-position'] . ')');
            }
        }

        $fieldCount = Db::getInstance()->ExecuteS('SELECT count(*) as n FROM ' . _DB_PREFIX_ . 'allegro_field;');
        if ($fieldCount[0]['n'] == 0)
        {
            $fields = Allegrowebapi::objectToArray($this->allegro->getSellFormFieldsExt());
            //die('<pre>'.print_r($fields, true).'</pre>');
            foreach ($fields['sell-form-fields'] as $field)
            {

                $query = 'INSERT INTO ' . _DB_PREFIX_ . 'allegro_field VALUES (
          ' . $field['sell-form-id'] . ', 
          \'' . $field['sell-form-title'] . '\', 
          ' . $field['sell-form-cat'] . ', 
          ' . $field['sell-form-type'] . ',
          ' . $field['sell-form-res-type'] . ',
          ' . $field['sell-form-def-value'] . ',
          ' . $field['sell-form-opt'] . ',
          ' . $field['sell-form-pos'] . ',
          ' . $field['sell-form-length'] . ',
          \'' . $field['sell-min-value'] . '\',
          \'' . $field['sell-max-value'] . '\',
          \'' . addslashes($field['sell-form-desc']) . '\',
          \'' . $field['sell-form-opts-values'] . '\',
          \'' . '' . '\',
          ' . $field['sell-form-param-id'] . ',
          \'' . $field['sell-form-param-values'] . '\',
          ' . $field['sell-form-parent-id'] . ',
          \'' . $field['sell-form-parent-value'] . '\',
          \'' . $field['sell-form-unit'] . '\',
          ' . $field['sell-form-options'] . '
        );';
                //echo $query.'<br>';
                Db::getInstance()->Execute($query);
            }
        }

        // przydzielenie pól formularza allegro do tymczasowej tablicy w celu obsługi formularza
        $i = 0;

        $fields = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'allegro_field LIMIT 50;');

        $tmp = array();
        foreach ($fields as $fkey => $fval)
        {
            $key = $fval['sell-form-id'];
            $tmp[$key] = $fval;
            $i++;
            if ($i == 50)
                break;
        }

        $this->aFields = $tmp;
    }

    /**
     * Resetowanie danych allegro
     */
    public function clearAllegroData()
    {
        //echo 'cz';
        Db::getInstance()->Execute('TRUNCATE TABLE ps_allegro_cat;');
        $fileFields = getcwd() . '/../modules/jballegro/allegro_fields.json';

        if (file_exists($fileFields))
        {
            unlink($fileFields);
        }

        $this->buildAllegroData();
    }

    /**
     * Walidacja formularza i aukcji allegro
     * @param int $quantity
     * @return type 
     */
    public function auctionValidation($quantity)
    {

        //tytul
        $tytul = Tools::getValue('fid_1');
        if (!$tytul || strlen($tytul) > 50)
            $this->errors['fid_1'] = "Podaj tytuł (maks 50 znaków)";

        //kategoria
        $kat = Tools::getValue('fid_2');
        if (!$kat)
            $this->errors['fid_2'] = "Wybierz kategorię";

        //sztuki
        $fid5 = Tools::getValue('fid_5');
        if (!$fid5)
            $this->errors['fid_5'] = "Podaj liczbę sztuk produktu do wystawienia";
        if ($fid5 > $quantity)
            $this->errors['fid_5'] = "Maksymalna ilość produktów możliwa do wystawienia: " . $quantity;
        //cena kup teraz
        $fid8 = Tools::getValue('fid_8');
        if (!$fid8 || !is_numeric($fid8))
            $this->errors['fid_8'] = "Podaj poprawną cenę kup teraz np: 13.49";

        //wojewodztwo
        $fid10 = Tools::getValue('fid_10');
        if (!$fid10)
            $this->errors['fid_10'] = "Wybierz województwo";

        //miejscowosc
        $fid11 = Tools::getValue('fid_11');
        if (!$fid11)
            $this->errors['fid_11'] = "Podaj miejscowość";

        //zdjecie 1
        if (isset($_FILES["fid_16"]) && $_FILES["fid_16"]["error"] > 0)
        {
            $this->errors['fid_16'] = "Błąd przesyłania pliku: " . $_FILES["fid_16"]["error"];
        }

        //kod pocztowy
        $fid32 = Tools::getValue('fid_32');
        if (!$fid32 || !preg_match('/^[0-9]{2}-[0-9]{3}$/', $fid32))
            $this->errors['fid_32'] = "Podaj kod pocztowy w odpowiednim formacie np: 22-101";

        //ceny dostaw
        for ($i = 36; $i < 46; $i++)
        {

            $fid = Tools::getValue('fid_' . $i);
            if ($fid != null && !is_numeric($fid))
                $this->errors['fid_' . $i] = "Podaj poprawną cenę np: 13.49";
        }

        if (count($this->errors) > 0)
        {
            $this->error = true;
            return false;
        }

        return true;
    }

    /**
     * Inicjacja nowej aukcji
     * 
     * @return boolean
     */
    public function createNewAuction()
    {

        // sprawdzenie dostepnej ilosci
        $query = 'SELECT quantity FROM ' . _DB_PREFIX_ . 'product where id_product = ' . Tools::getValue('id_product') . ' LIMIT 1;';
        $result = Db::getInstance()->ExecuteS($query);

        if (!$this->auctionValidation($result[0]['quantity']))
            return false;

        $fields = array();

        $field = array();
        $field['fvalue-string'] = '';
        $field['fvalue-int'] = 0;
        $field['fvalue-float'] = 0;
        $field['fvalue-image'] = '';
        $field['fvalue-datetime'] = 0;
        $field['fvalue-date'] = '';

        $field['fvalue-range-int'] = array(
            'fvalue-range-int-min' => 0,
            'fvalue-range-int-max' => 0
        );
        $field['fvalue-range-float'] = array(
            'fvalue-range-float-min' => 0,
            'fvalue-range-float-max' => 0
        );

        $field['fvalue-range-date'] = array(
            'fvalue-range-date-min' => '',
            'fvalue-range-date-max' => ''
        );

        // tytul - string
        $f = $field;
        $f['fid'] = 1;
        $f['fvalue-string'] = Tools::getValue('fid_1');
        $fields[1] = $f;
        $title = Tools::getValue('fid_1');

        // kategoria - int
        $cat_id = Tools::getValue('fid_2');
        $f = $field;
        $f['fid'] = 2;
        $f['fvalue-int'] = $cat_id;
        $fields[2] = $f;

        // czas trwania
        $f = $field;
        $f['fid'] = 4;
        $f['fvalue-int'] = Tools::getValue('fid_4');
        $fields[4] = $f;

        // liczba sztuk
        $f = $field;
        $f['fid'] = 5;
        $f['fvalue-int'] = Tools::getValue('fid_5');
        $fields[5] = $f;

        // cena kup teraz
        $f = $field;
        $f['fid'] = 8;
        $f['fvalue-float'] = Tools::getValue('fid_8');
        $fields[8] = $f;

        // kraj
        $f = $field;
        $f['fid'] = 9;
        $f['fvalue-int'] = Tools::getValue('fid_9');
        $fields[9] = $f;

        // woj
        $f = $field;
        $f['fid'] = 10;
        $f['fvalue-int'] = Tools::getValue('fid_10');
        $fields[10] = $f;

        // miejscowosc
        $f = $field;
        $f['fid'] = 11;
        $f['fvalue-string'] = Tools::getValue('fid_11');
        $fields[11] = $f;

        // transport
        $f = $field;
        $f['fid'] = 12;
        $f['fvalue-int'] = Tools::getValue('fid_12');
        $fields[12] = $f;

        // forma platnosci
        $f = $field;
        $f['fid'] = 14;
        $f['fvalue-int'] = Tools::getValue('fid_14');
        $fields[14] = $f;

        // zdjecie
        $di = Tools::getValue('prod_image');
        if ($di == 1)
            $image = $this->getProductImage(Tools::getValue('id_product'));
        else
        {
            // uploaded image
            $image = file_get_contents($_FILES["fid_16"]["tmp_name"]);
        }

        if ($image)
        {
            $f = $field;
            $f['fid'] = 16;
            $f['fvalue-image'] = $image;
            $fields[16] = $f;
        }

        // opis
        if ($this->atemplate != '')
        {
            $content = preg_replace('/\{\{allegro\}\}/', Tools::getValue('fid_24'), $this->atemplate);
        }
        else
            $content = '<div style="padding: 5px 10px;"' . Tools::getValue('fid_24') . '</div>';

        $content = $this->parseAuctionContent($content);

        
        $f = $field;
        $f['fid'] = 24;
        $f['fvalue-string'] = $content;
        $fields[24] = $f;

        // sztuki/pary
        $f = $field;
        $f['fid'] = 28;
        $f['fvalue-int'] = Tools::getValue('fid_28');
        $fields[28] = $f;

        // rodzaj aukcji
        $f = $field;
        $f['fid'] = 29;
        $f['fvalue-int'] = Tools::getValue('fid_29');
        $fields[29] = $f;

        // kod pocztowy
        $f = $field;
        $f['fid'] = 32;
        $f['fvalue-string'] = Tools::getValue('fid_32');
        $fields[32] = $f;

        // wysylka free
        $f = $field;
        $f['fid'] = 35;
        $f['fvalue-int'] = Tools::getValue('fid_35');
        $fields[35] = $f;

        // wysylka poczta eko
        if (Tools::getValue('fid_36'))
        {
            $f = $field;
            $f['fid'] = 36;
            $f['fvalue-float'] = Tools::getValue('fid_36');
            $fields[36] = $f;
        }

        if (Tools::getValue('fid_37'))
        {
            $f = $field;
            $f['fid'] = 37;
            $f['fvalue-float'] = Tools::getValue('fid_37');
            $fields[37] = $f;
        }

        // wysylka poczta prior
        if (Tools::getValue('fid_38'))
        {
            $f = $field;
            $f['fid'] = 38;
            $f['fvalue-float'] = Tools::getValue('fid_38');
            $fields[38] = $f;
        }

        // wysylka pobranie
        if (Tools::getValue('fid_39'))
        {
            $f = $field;
            $f['fid'] = 39;
            $f['fvalue-float'] = Tools::getValue('fid_39');
            $fields[39] = $f;
        }

        // kurier
        if (Tools::getValue('fid_40'))
        {
            $f = $field;
            $f['fid'] = 40;
            $f['fvalue-float'] = Tools::getValue('fid_40');
            $fields[40] = $f;
        }

        if (Tools::getValue('fid_41'))
        {
            $f = $field;
            $f['fid'] = 41;
            $f['fvalue-float'] = Tools::getValue('fid_41');
            $fields[41] = $f;
        }

        if (Tools::getValue('fid_42'))
        {
            $f = $field;
            $f['fid'] = 42;
            $f['fvalue-float'] = Tools::getValue('fid_42');
            $fields[42] = $f;
        }

        if (Tools::getValue('fid_43'))
        {
            $f = $field;
            $f['fid'] = 43;
            $f['fvalue-float'] = Tools::getValue('fid_43');
            $fields[43] = $f;
        }

        if (Tools::getValue('fid_44'))
        {
            $f = $field;
            $f['fid'] = 44;
            $f['fvalue-float'] = Tools::getValue('fid_44');
            $fields[44] = $f;
        }

        if (Tools::getValue('fid_45'))
        {
            $f = $field;
            $f['fid'] = 45;
            $f['fvalue-float'] = Tools::getValue('fid_45');
            $fields[45] = $f;
        }
        
        // sprawdzenie czy wybrana kategoria wymaga parametru stanu
        //$cat_query = 'SELECT * FROM ' . _DB_PREFIX_ . 'allegro_field where `sell-form-title` = "Stan" and `sell-form-cat` = ' . $cat_id . ';';
        //$cat_data = Db::getInstance()->ExecuteS($cat_query);
        
        $jb = new Jb();
        $cat_data = $jb->getStateSelectForCategory($cat_id);
        
        if ($cat_data)
        {
            $state = $cat_data;
            $state_fid = $state['sell-form-id'];
            $state_val = Tools::getValue('fid_'.$state_fid);
            
            $f = $field;
            $f['fid'] = $state_fid;
            $f['fvalue-int'] = $state_val;
            $fields['stan'] = $f;
        }

        try
        {

            $ret = $this->allegro->newAuctionExt($fields);

            if (isset($ret['item-id']))
                $this->storeAuctionHistory($ret, $title, Tools::getValue('id_product'));

            return true;
        }
        catch (SoapFault $fault)
        {
            $this->webapierror = $fault->faultstring;
            $this->error = true;
            return false;
        }
    }

    /**
     * Zapisanie danych o aukcji w bazie danych
     * 
     * @param array $ret Tablica z danymi zwróconymi po poprawnym dodaniu aukcji na allegro
     * @param integer $prodId 
     */
    public function storeAuctionHistory($ret, $title, $prodId)
    {
        $durations = array(3, 5, 7, 10, 14, 30);

        $query = "INSERT INTO ps_allegro VALUES (null, {$ret['item-id']}, '$title', $prodId, " . $durations[Tools::getValue('fid_4')] . ", " . Tools::getValue('fid_5') . ", 0, " . Tools::getValue('fid_8') . ", '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "', 1);";

        Db::getInstance()->Execute($query);

        for ($i = 0; $i < 50; $i++)
        {
            if (array_key_exists('fid_' . $i, $_POST))
                unset($_POST['fid_' . $i]);
        }
    }

    /**
     * Instalacja modułu w sklepie prestashop
     * @return boolean
     */
    public function install()
    {

        // globalne zmienne konfiguracyjne modułu 
        Configuration::updateValue('JB_ALLEGRO_TESTMODE', 1);
        Configuration::updateValue('JB_ALLEGRO_ID', '');
        Configuration::updateValue('JB_ALLEGRO_LOGIN', '');
        Configuration::updateValue('JB_ALLEGRO_PASS', '');
        Configuration::updateValue('JB_ALLEGRO_KEY', '');
        Configuration::updateValue('JB_ALLEGRO_TEMPLATE', '');
        Configuration::updateValue('JB_ALLEGRO_COUNTRY', '');
        Configuration::updateValue('JB_APROVISION', '');

        // utworzenie potrzebnych dla modulu baz danych
        if (!parent::install() OR
                !Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'allegro` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `allegro_id` varchar(255) NOT NULL,
              `title` varchar(64) NOT NULL,
              `id_product` int(11) NOT NULL,
              `duration` int(11) NOT NULL,
              `quantity` int(11) NOT NULL,
              `sold` int(11) NOT NULL,
              `price` decimal(20,6) NOT NULL,
              `date_add` datetime NOT NULL,
              `date_upd` datetime NOT NULL,
              `status` integer NOT NULL DEFAULT 1,
              PRIMARY KEY (`id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' default CHARSET=utf8;') OR
                !Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'allegro_cat` (
              `id` int(11) NOT NULL,
              `name` varchar(255) NOT NULL,
              `parent` int(11) NOT NULL,
              `position` int(11) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `parent` (`parent`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;') OR
                !Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'allegro_field` (
              `sell-form-id` int(11) NOT NULL,
              `sell-form-title` varchar(255) NOT NULL,
              `sell-form-cat` int(11),
              `sell-form-type` int(11),
              `sell-form-res-type` int(11),
              `sell-form-def-value` int(11),
              `sell-form-opt` int(11),
              `sell-form-pos` int(11),
              `sell-form-length` int(11),
              `sell-min-value` double,
              `sell-max-value` double,
              `sell-form-desc` varchar(255),
              `sell-form-opts-values` varchar(255),
              `sell-form-field-desc` text,
              `sell-form-param-id` int(11),
              `sell-form-param-values` varchar(255),
              `sell-form-parent-id` int(11),
              `sell-form-parent-value` varchar(255),
              `sell-form-unit` varchar(255),
              `sell-form-options` int(11),
              PRIMARY KEY (`sell-form-id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;')
        )
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() OR
                !Configuration::deleteByName('JB_ALLEGRO_TESTMODE') OR
                !Configuration::deleteByName('JB_ALLEGRO_ID') OR
                !Configuration::deleteByName('JB_ALLEGRO_LOGIN') OR
                !Configuration::deleteByName('JB_ALLEGRO_PASS') OR
                !Configuration::deleteByName('JB_ALLEGRO_KEY') OR
                !Configuration::deleteByName('JB_ALLEGRO_TEMPLATE') OR
                !Configuration::deleteByName('JB_ALLEGRO_COUNTRY') OR
                !Configuration::deleteByName('JB_APROVISION') OR
                !Configuration::deleteByName('JB_APROVINCE') OR
                !Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'allegro_cat`, `' . _DB_PREFIX_ . 'allegro_field` , `' . _DB_PREFIX_ . 'allegro`'))
            return false;
        return true;
    }

    /**
     * Przetworzenie danych przesłanych metodą GET
     * 
     * @global Cookie $cookie Obiekt reprezentujacy globalne cookie prestashop
     * @return array
     */
    public function getProcess()
    {
        global $cookie;

        // zwrócenie danych produktu z bazy prestashop
        if (isset($_GET['id_prod']))
        {
            $id = $_GET['id_prod'];
            $query = 'SELECT p.price, p.id_tax_rules_group, l.name, l.description FROM ' . _DB_PREFIX_ . 'product p LEFT JOIN ' . _DB_PREFIX_ . 'product_lang l ON p.id_product = l.id_product where p.id_product = ' . $id . ' and l.id_lang = 6;';
            $prod = Db::getInstance()->ExecuteS($query);
            $price = Product::getPriceStatic($id);

            if ($this->provision && is_numeric($this->provision))
                $price = $price * (1 + ($this->provision / 100));

            if ($prod[0]['description'] == '')
                $desc = $prod[0]['name'];
            else
                $desc = $prod[0]['description'];

            $data = array(
                'id_product' => $id,
                'name' => $prod[0]['name'],
                'price' => round($price, 2),
                'desc' => '<h1>' . $prod[0]['name'] . '</h1><br />' . $desc
            );

            return $data;
        }

        if (isset($_GET['closeauction']))
        {
            $id = $_GET['closeauction'];

            $query = 'SELECT * FROM ' . _DB_PREFIX_ . 'allegro WHERE allegro_id = ' . $id . ';';
            $result = Db::getInstance()->ExecuteS($query);

            if (count($result) > 0)
            {
                $hid = $result[0]['id'];
                try
                {
                    $this->allegro->finishItem(number_format($result[0]['allegro_id'], 0, '.', ''));
                    $query = 'UPDATE ' . _DB_PREFIX_ . 'allegro SET status = 0 WHERE id = ' . $hid . ';';
                    Db::getInstance()->Execute($query);
                }
                catch (SoapFault $e)
                {
                    $this->webapierror = $e->faultstring;
                    $this->error = true;

                    $c = '<span id="error-list">';
                    $c .= '<h3>Wystąpiły problemy z zakończeniem aukcji!</h3>';
                    $c .= $this->getErrorHtmlList();
                    $c .= '</span><br />';

                    return $c;
                }
            }
        }
    }

    /**
     * Przetworzenie danych przesłanych metodą POST
     * @return void
     */
    public function postProcess()
    {
        global $currentIndex;

        $errors = '';

        // przetwarzanie przesłanych danych konfiguracyjnych
        if (Tools::isSubmit('submitConf'))
        {

            $clear = false;

            if (Tools::getValue('testmode'))
                $testmode = 1;
            else
                $testmode = 0;

            // zmiana trybu pracy - czyczenie cache/danych allegro
            if ($testmode != $this->testmode)
                $clear = true;

            Configuration::updateValue('JB_ALLEGRO_TESTMODE', $testmode);
            $this->testmode = htmlentities($testmode, ENT_QUOTES, 'UTF-8');

            // aktualizacja wartosci pol formularza kofiguracyjnego modulu

            if ($aid = Tools::getValue('aid'))
            {
                Configuration::updateValue('JB_ALLEGRO_ID', $aid);
                $this->aid = htmlentities($aid, ENT_QUOTES, 'UTF-8');
            }

            if ($alogin = Tools::getValue('alogin'))
            {
                Configuration::updateValue('JB_ALLEGRO_LOGIN', $alogin);
                $this->alogin = htmlentities($alogin, ENT_QUOTES, 'UTF-8');
            }

            if ($apass = Tools::getValue('apass'))
            {
                Configuration::updateValue('JB_ALLEGRO_PASS', $apass);
                $this->apass = htmlentities($apass, ENT_QUOTES, 'UTF-8');
            }

            if ($akey = Tools::getValue('akey'))
            {
                Configuration::updateValue('JB_ALLEGRO_KEY', $akey);
                $this->akey = htmlentities($akey, ENT_QUOTES, 'UTF-8');
            }

            if ($acountry = Tools::getValue('acountry'))
            {
                Configuration::updateValue('JB_ALLEGRO_COUNTRY', $acountry);
                $this->acountry = htmlentities($acountry, ENT_QUOTES, 'UTF-8');
            }

            if ($atemplate = $_POST['atemplate'])
            {
                $atemplate = stripslashes($atemplate);
                Configuration::updateValue('JB_ALLEGRO_TEMPLATE', base64_encode($atemplate));
                $this->atemplate = $atemplate;
            }


            if ($aprzes10 = $_POST['aprzes10'])
            {
                Configuration::updateValue('JB_APRZES10', $aprzes10);
                $this->aprzes10 = htmlentities($aprzes10, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes1 = $_POST['aprzes1'])
            {
                Configuration::updateValue('JB_APRZES1', $aprzes1);
                $this->aprzes1 = htmlentities($aprzes1, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes2 = $_POST['aprzes2'])
            {
                Configuration::updateValue('JB_APRZES2', $aprzes2);
                $this->aprzes2 = htmlentities($aprzes2, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes3 = $_POST['aprzes3'])
            {
                Configuration::updateValue('JB_APRZES3', $aprzes3);
                $this->aprzes3 = htmlentities($aprzes3, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes4 = $_POST['aprzes4'])
            {
                Configuration::updateValue('JB_APRZES4', $aprzes4);
                $this->aprzes4 = htmlentities($aprzes4, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes5 = $_POST['aprzes5'])
            {
                Configuration::updateValue('JB_APRZES5', $aprzes5);
                $this->aprzes5 = htmlentities($aprzes5, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes6 = $_POST['aprzes6'])
            {
                Configuration::updateValue('JB_APRZES6', $aprzes6);
                $this->aprzes6 = htmlentities($aprzes6, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes7 = $_POST['aprzes7'])
            {
                Configuration::updateValue('JB_APRZES7', $aprzes7);
                $this->aprzes7 = htmlentities($aprzes7, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes8 = $_POST['aprzes8'])
            {
                Configuration::updateValue('JB_APRZES8', $aprzes8);
                $this->aprzes8 = htmlentities($aprzes8, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes9 = $_POST['aprzes9'])
            {
                Configuration::updateValue('JB_APRZES9', $aprzes9);
                $this->aprzes9 = htmlentities($aprzes9, ENT_QUOTES, 'UTF-8');
            }

            if ($aprzes10 = $_POST['aprzes10'])
            {
                Configuration::updateValue('JB_APRZES10', $aprzes10);
                $this->aprzes10 = htmlentities($aprzes10, ENT_QUOTES, 'UTF-8');
            }

            if ($provision = $_POST['provision'])
            {
                Configuration::updateValue('JB_APROVISION', $provision);
                $this->provision = htmlentities($provision, ENT_QUOTES, 'UTF-8');
            }

            if ($province = $_POST['province'])
            {
                Configuration::updateValue('JB_APROVINCE', $province);
                $this->province = htmlentities($province, ENT_QUOTES, 'UTF-8');
            }
            
            $c = '<span id="error-list">';
            if ($this->initAllegro())
            {
                $c .= '<h3>Konfiguracja zapisana poprawnie!</h3>';
                //if ($clear) $this->clearAllegroData();
            }
            else
            {
                $c .= '<h3>Wystąpiły problemy konfiguracją!</h3>';
                $c .= $this->getErrorHtmlList();
            }
            $c .= '</span><br />';
            return $c;
        }
        // przetwarzanie danych formularza allegro
        else if (Tools::isSubmit('submitAllegro'))
        {
            $c = '<span id="error-list">';
            if ($this->createNewAuction())
            {
                $c .= '<h3>Aukcja dodana pomyślnie!</h3>';
                unset($_POST['id_product']);
            }
            else
            {
                $c .= '<h3>Wystąpiły problemy z utworzeniem aukcji!</h3>';
                $c .= $this->getErrorHtmlList();
            }
            $c .= '</span><br />';
            return $c;
        }
    }

    /**
     * Proste wyświetlenie list błedów api allegro
     * 
     * @return string 
     */
    public function getErrorHtmlList()
    {
        $l = '';
        if ($this->webapierror)
            $l = '<p>' . $this->webapierror . '</p>';
        if (count($this->errors) > 0)
        {
            $l .= '<ul>';
            foreach ($this->errors as $e)
                $l .= '<li>' . $e . '</li>';
            $l .= '</ul>';
        }
        return $l;
    }

    /**
     * Wyświetlenie strony modulu w panelu admina Prestashop
     * TODO: wrzucic kod html do template'a smarty
     * @return void
     */
    public function getContent()
    {
        global $protocol_content;

        $output = '';

        // przechwycenie danych przesłanych w requescie
        $data = $this->getProcess();
        if (is_array($data))
            $startProduct = $data;
        else
        {
            $output = $data;
            $startProduct = null;
        }
        $postprocess = $this->postProcess();

        // przygotowanie kodu Html dla modułu w panelu admina Prestashop
        $output .= '
    <link href="' . $this->_path . 'css/jballegro.css" rel="stylesheet" type="text/css">
    <script src="/js/jquery/jquery.autocomplete.js" type="text/javascript"></script>
    <!-- TinyMCE -->
    <script type="text/javascript" src="' . $this->_path . 'tiny_mce/tiny_mce.js"></script>
    <script type="text/javascript">
        tinyMCE.init({
        mode : "exact",
        elements : "allegroDesc",
        theme : "advanced",
        plugins : "autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

        // Theme options
        theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
        theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,
        
        width: "550",
        height: "400"
        });
    </script>
    <!-- /TinyMCE -->
    <script type="text/javascript" src="' . $this->_path . 'js/jballegro.js"></script>';

        // w przypadku gdy modul jest poprawnie skonfigurowany pokaż formularza aukcji allegro
        if ($this->allegro)
        {
            $output .= '</br><fieldset><legend>' . $this->l('Nowa aukcja') . '</legend>';
            $output .= $postprocess;
            $output .= $this->getAuctionForm($startProduct);
            $output .= '</fieldset>';

            $output .= '</br><fieldset><legend>' . $this->l('Dodane aukcje') . ' <a title="Rozwiń/Zwiń" id="toggler2" href="#aukcje"><img alt="Rozwiń/Zwiń" src="../img/admin/more.png" /></a></legend>';
            $output .= $this->getAuctionsList();
            $output .= '
        </fieldset>
        <script type="text/javascript">
        $("a#toggler2").click(function (event) {
          $("#auction-list").toggle();
        });
        </script>';
        }

        // formularz konfiguracyjny
        $output .= $this->getConfigForm();

        return $output;
    }

    /**
     * Przygotowanie formularza do wystawiania aukcji allegro
     * TODO: wrzucic kod html do template'a smarty
     * 
     * @param string $startProduct
     * @return string 
     */
    public function getAuctionForm($startProduct = null)
    {

        // domyslnie uzywaj obrazka z bazy produktu
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && Tools::isSubmit('submitAllegro'))
        {
            $image = Tools::getValue('prod_image');
            $startProduct = null;
        }
        else
            $image = 1;

        // pobranie danych produktu z bazy Prestashop
        $id_product = Tools::getValue('id_product') ? Tools::getValue('id_product') : (is_array($startProduct) ? $startProduct['id_product'] : null);
        $price = Tools::getValue('fid_' . $this->aFields[8]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[8]['sell-form-id']) : ($id_product ? $startProduct['price'] : '');
        $title = Tools::getValue('fid_' . $this->aFields[1]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[1]['sell-form-id']) : ($id_product ? $startProduct['name'] : '');
        $desc = Tools::getValue('fid_' . $this->aFields[24]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[24]['sell-form-id']) : ($id_product ? $startProduct['desc'] : '');
        $form = '
      <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data">
        <table id="loadProduct">
        <tr>
          <th>Wybierz produkt: </th>
          <td>
            <input id="id_product" type="hidden" name="id_product" value="' . $id_product . '"></input>
            <input class="inputForm" id="product_autocomplete_input" type="text" name="produkt" value=""></input></td>
          <script type="text/javascript">
          $(function() {
            $("#product_autocomplete_input")
            .autocomplete("ajax_products_list.php", {
              minChars: 1,
              autoFill: true,
              max:20,
              matchContains: true,
              mustMatch:true,
              scroll:false,
              cacheLength:0,
              formatItem: function(item) {
                return item[1]+" - "+item[0];
              }
            });
          }).result( function(event, data, formatted){ 
            $("#id_product").val(data[1]);
            $("#atitle").val(data[0]);
            loadProductData(data[1],"' . $this->_path . 'ajax.php");
            $("#allegro-form").show("fast");
          });
          </script>
        </tr>
      </table>
      
      <table id="allegro-form" style="' . ($this->error ? '' : ($id_product ? '' : 'display:none;')) . '">
        <tr>
          <th>' . $this->aFields[1]['sell-form-title'] . '</th>
          <td><input class="inputForm" id="atitle" maxlength="' . $this->aFields[1]['sell-form-length'] . '" type="text" name="fid_' . $this->aFields[1]['sell-form-id'] . '" value="' . $title . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[2]['sell-form-title'] . '</th>
          <td id="categories_select">
            <input id="category" type="hidden" name="fid_' . $this->aFields[2]['sell-form-id'] . '" value="" />
            <span class="level_0" level="0">
            <select id="select_category">
            ' . $this->buildCategoriesHtml($this->aCategories) . '
            </select>
            <script type="text/javascript">
             $(document).ready(function(){
               $("#select_category").live("change", function(event){
                 if ($(this).val() != "")
                 {
                   $("#category").val($(this).val());
                   loadProductStateSelect($(this).val());
                   $.ajax({
                      url: \'/modules/jballegro/ajax.php\',
                      data: \'&level=0&getsubcategory=\' + $(this).val(),
                      type: "POST",
                      success: function(data) {
                        $(".levels").remove();
                        $(\'#categories_select\').append(data);
                      }
                   });
                 }
               });
             });
            </script>
            </span>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[4]['sell-form-title'] . '</th>
          <td>
            <select name="fid_' . $this->aFields[4]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[4]['sell-form-desc']);
        $values = explode('|', $this->aFields[4]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[4]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[4]['sell-form-id']) : $this->aFields[4]['sell-form-def-value'];
        foreach ($options as $k => $o)
        {
            $form .= '<option value="' . $values[$k] . '" ' . ($def == $values[$k] ? 'selected="selected"' : '') . '>' . $o . ' dni</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[5]['sell-form-title'] . '<!-- liczba sztuk --></th>
          ';

        $def = Tools::getValue('fid_' . $this->aFields[5]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[5]['sell-form-id']) : 1;

        $form .= '
          <td><input class="inputForm" type="text" name="fid_' . $this->aFields[5]['sell-form-id'] . '" value="' . $def . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[8]['sell-form-title'] . '<!-- cena kup teraz --></th>
          <td><input id="allegroPrice" class="inputForm" type="text" name="fid_' . $this->aFields[8]['sell-form-id'] . '" value="' . $price . '"></input></td>
        </tr>';

        if ($this->provision && is_numeric($this->provision))
        {
            $form .= '
        <tr>
          <th class="help">' . $this->l('Cena bez prowizji') . ':</th>
          <td class="help old_price">' . round($price / (1 + ($this->provision / 100)), 2) . '</td>
        </tr>
      ';
        }

        $form .= '
        <tr style="display: none;">
          <th>' . $this->aFields[9]['sell-form-title'] . '<!-- kraj --></th>
          <td><input class="inputForm" type="text" name="fid_' . $this->aFields[9]['sell-form-id'] . '" value="' . $this->acountry . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[10]['sell-form-title'] . '<!-- Województwo --></th>
          <td>
          <select name="fid_' . $this->aFields[10]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[10]['sell-form-desc']);
        $values = explode('|', $this->aFields[10]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[10]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[10]['sell-form-id']) : $this->aFields[10]['sell-form-def-value'];

        foreach ($options as $k => $o)
        {
            if (strtolower($o) == strtolower($this->province)) $def = $values[$k];
            $form .= '<option value="' . $values[$k] . '" ' . ($values[$k] == $def ? 'selected="selected"' : '') . '>' . $o . '</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[11]['sell-form-title'] . '<!-- Miejscowość --></th>
          <td><input class="inputForm" type="text" name="fid_' . $this->aFields[11]['sell-form-id'] . '" value="' . Configuration::get('PS_SHOP_CITY') . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[12]['sell-form-title'] . '<!-- transp --></th>
          <td>
            <select name="fid_' . $this->aFields[12]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[12]['sell-form-desc']);
        $values = explode('|', $this->aFields[12]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[12]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[12]['sell-form-id']) : $this->aFields[12]['sell-form-def-value'];
        foreach ($options as $k => $o)
        {
            $form .= '<option value="' . $values[$k] . '" ' . ($def == $values[$k] ? 'selected="selected"' : '') . '>' . $o . '</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[14]['sell-form-title'] . '<!-- Formy płatności --></th>
          <td>
            <select name="fid_' . $this->aFields[14]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[14]['sell-form-desc']);
        $values = explode('|', $this->aFields[14]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[14]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[14]['sell-form-id']) : $this->aFields[14]['sell-form-def-value'];
        foreach ($options as $k => $o)
        {
            $form .= '<option value="' . $values[$k] . '" ' . ($values[$k] == $def ? 'selected="selected"' : '') . '>' . $o . '</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[16]['sell-form-title'] . '<!-- Zdjęcie --></th>
          <td style="font-size: 10px;">
            <input id="allegro-send-img" ' . ($image == 1 ? 'disabled="disabled"' : '') . ' type="file" name="fid_' . $this->aFields[16]['sell-form-id'] . '" value=""></input>
            (Użyj okładki produktu jako zdjęcie aukcji? <input id="use-default-img" type="checkbox" ' . ($image == 1 ? 'checked="checked"' : '') . ' name="prod_image" value="1" />)
            <script type="text/javascript">
             $(document).ready(function(){
               $("#use-default-img").change(function(){
               if ($(this).is(\':checked\')) $("#allegro-send-img").attr("disabled", "disabled");
               else $("#allegro-send-img").removeAttr("disabled");
               });
             });
            </script>
          </td>  
        </tr>
        <tr>
          <th>' . $this->aFields[24]['sell-form-title'] . '<!-- Opis --></th>
          <td><textarea id="allegroDesc" name="fid_' . $this->aFields[24]['sell-form-id'] . '">' . $desc . '</textarea>
        </tr>
        <tr id="state-row" style="display: none;">
            <th>Stan</th>
            <td></td>
        </tr>
        <tr>
          <th>' . $this->aFields[28]['sell-form-title'] . '<!-- Sztuki --></th>
          <td>
            <select name="fid_' . $this->aFields[28]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[28]['sell-form-desc']);
        $values = explode('|', $this->aFields[28]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[28]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[28]['sell-form-id']) : $this->aFields[28]['sell-form-def-value'];

        foreach ($options as $k => $o)
        {
            $form .= '<option value="' . $values[$k] . '" ' . ($def == $values[$k] ? 'selected="selected"' : '') . '>' . $o . '</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[29]['sell-form-title'] . '<!-- format sprzedazy --></th>
          <td>
            <select name="fid_' . $this->aFields[29]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[29]['sell-form-desc']);
        $values = explode('|', $this->aFields[29]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[29]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[29]['sell-form-id']) : $this->aFields[29]['sell-form-def-value'];

        foreach ($options as $k => $o)
        {
            $form .= '<option value="' . $values[$k] . '" ' . ($def == $values[$k] ? 'selected="selected"' : '') . '>' . $o . '</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[32]['sell-form-title'] . '<!-- kod pocztowy --></th>
          <td><input class="inputForm" type="text" name="fid_' . $this->aFields[32]['sell-form-id'] . '" value="' . Configuration::get('PS_SHOP_CODE') . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[35]['sell-form-title'] . '<!-- darmowe opcje odbioru --></th>
          <td>
            <select name="fid_' . $this->aFields[35]['sell-form-id'] . '">';
        $options = explode('|', $this->aFields[35]['sell-form-desc']);
        $values = explode('|', $this->aFields[35]['sell-form-opts-values']);
        $def = Tools::getValue('fid_' . $this->aFields[35]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[35]['sell-form-id']) : $this->aFields[35]['sell-form-def-value'];

        foreach ($options as $k => $o)
        {
            $form .= '<option value="' . $values[$k] . '" ' . ($def == $values[$k] ? 'selected="selected"' : '') . '>' . $o . '</option>';
        }
        $form .= '</select>
          </td>
        </tr>
        <tr>
          <th>' . $this->aFields[36]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[36]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[36]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[36]['sell-form-id']) : $this->aprzes1) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[37]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[37]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[37]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[37]['sell-form-id']) : $this->aprzes2) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[38]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[38]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[38]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[38]['sell-form-id']) : $this->aprzes3) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[39]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[39]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[39]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[39]['sell-form-id']) : $this->aprzes4) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[40]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[40]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[40]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[40]['sell-form-id']) : $this->aprzes5) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[41]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[41]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[41]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[41]['sell-form-id']) : $this->aprzes6) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[42]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[42]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[42]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[42]['sell-form-id']) : $this->aprzes7) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[43]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[43]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[43]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[43]['sell-form-id']) : $this->aprzes8) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[44]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[44]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[44]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[44]['sell-form-id']) : $this->aprzes9) . '"></input></td>
        </tr>
        <tr>
          <th>' . $this->aFields[45]['sell-form-title'] . '<!-- koszt przesylki --></th>
          <td><input type="text" name="fid_' . $this->aFields[45]['sell-form-id'] . '" value="' . (Tools::getValue('fid_' . $this->aFields[45]['sell-form-id']) ? Tools::getValue('fid_' . $this->aFields[45]['sell-form-id']) : $this->aprzes10) . '"></input></td>
        </tr>
        <tr>
          <td style="text-align: center;" colspan="2">
          <input type="submit" name="submitAllegro" value="Utwórz aukcję"></input>
          </td>
        </tr>
      </table>
    </form>
    ';

        return $form;
    }

    public function getConfigForm()
    {
        $output = '<br />
    <script type="text/javascript">
    function setup() {
      tinyMCE.init({
          mode : "exact",
          elements : "allegroSzablon",
          theme : "advanced",
          plugins : "autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

          // Theme options
          theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
          theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
          theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
          theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage",
          theme_advanced_toolbar_location : "top",
          theme_advanced_toolbar_align : "left",
          theme_advanced_statusbar_location : "bottom",
          theme_advanced_resizing : true,
      });
    }
    </script>

    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data">
    <fieldset><legend>' . $this->l('Konfiguracja') . ' <a title="Rozwiń/Zwiń" id="toggler" href="#konfiguracja"><img alt="Rozwiń/Zwiń" src="../img/admin/more.png" /></a></legend>
      <span id="konf-form" style="' . ($this->allegro ? 'display: none;' : '') . '">
    <br />';
        /* $output .= '<label for="testmode">' . $this->l('Tryb testowy allegro') . '</label><input type="checkbox" name="testmode" ' . ($this->testmode == 1 ? 'checked="checked"' : '' ) . ' />
          <br class="clear"/>
          <br />'; */
        $output .= '<label for="aid">' . $this->l('Identyfikator użytkownika allegro') . '</label><input type="text" name="aid" value="' . $this->aid . '" />
    <br class="clear"/>
    <br />
    <label for="alogin">' . $this->l('Login allegro') . '</label><input type="text" name="alogin" value="' . $this->alogin . '" />
    <br class="clear"/>
    <br />
    <label for="apass">' . $this->l('Hasło allegro') . '</label><input type="password" name="apass" value="' . $this->apass . '" />
    <br class="clear"/>
    <br />
    <label for="akey">' . $this->l('Klucz webapi allegro') . '</label><input type="text" name="akey" value="' . $this->akey . '" />
    <br class="clear"/>
    <br />
    <label for="acountry">' . $this->l('Identyfikator kraju') . '<span class="help">' . $this->l('1 - PL, 228 - Testmode') . '</span></label><input type="text" name="acountry" value="' . $this->acountry . '" />
    <br class="clear"/>
    <br />
    <label for="acountry">' . $this->l('Domyślne wojewodztwo') . '</label>
        '.$this->getSelectProvince($this->province).'
    <br class="clear"/>
    <br />
    <label for="acountry">' . $this->l('Prowizja allegro') . '<span class="help">' . $this->l('Wartość w procentach') . '</span></label><input type="text" name="provision" value="' . $this->provision . '" />
    <br class="clear"/>
    <br />
    
    <label for="atemplate">' . $this->l('Kod szablonu allegro') . '<span class="help">' . $this->l('Umieść znacznik') . '<b> {{allegro}} </b>' . $this->l('w wybranym miejscu w kodzie szablonu - znacznik ten będzie zastąpiony treścią twojej aukcji.') . '</span></label>
      <textarea id="allegroSzablon" rows="30" cols="100" name="atemplate">' . $this->atemplate . '</textarea>
        <br /><span style="margin: 0 0 0 210px;"><a onclick="tinymce.execCommand(\'mceToggleEditor\',false,\'allegroSzablon\');" href="javascript:;">[Przełącz tryb edycji]</a></span>
    <br class="clear"/>
    <br />
    <h5>Koszty przesyłki</h5>
    <label for="atemplate">' . $this->l('Paczka pocztowa ekonomiczna (pierwsza sztuka)') . '</label><input type="text" name="aprzes1" value="' . $this->aprzes1 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('List ekonomiczny (pierwsza sztuka)') . '</label><input type="text" name="aprzes2" value="' . $this->aprzes2 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('Paczka pocztowa priorytetowa (pierwsza sztuka)') . '</label><input type="text" name="aprzes3" value="' . $this->aprzes3 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('List priorytetowy (pierwsza sztuka)') . '</label><input type="text" name="aprzes4" value="' . $this->aprzes4 . '" />
    <br class="clear"/>
    <br />
    
    <label for="atemplate">' . $this->l('Przesyłka pobraniowa (pierwsza sztuka)') . '</label><input type="text" name="aprzes5" value="' . $this->aprzes5 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('List polecony ekonomiczny (pierwsza sztuka)') . '</label><input type="text" name="aprzes6" value="' . $this->aprzes6 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('Przesyłka pobraniowa priorytetowa (pierwsza sztuka)') . '</label><input type="text" name="aprzes7" value="' . $this->aprzes7 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('List polecony priorytetowy (pierwsza sztuka)') . '</label><input type="text" name="aprzes8" value="' . $this->aprzes8 . '" />
    <br class="clear"/>
    <br />
    
    <label for="atemplate">' . $this->l('Przesyłka kurierska (pierwsza sztuka)') . '</label><input type="text" name="aprzes9" value="' . $this->aprzes9 . '" />
    <br class="clear"/>
    <br />
    <label for="atemplate">' . $this->l('Przesyłka kurierska pobraniowa (pierwsza sztuka)') . '</label><input type="text" name="aprzes10" value="' . $this->aprzes10 . '" />
    <br class="clear"/>
    <br />
    
    <input class="button" type="submit" name="submitConf" value="' . $this->l('Zapisz') . '" style="margin-left: 200px;"/>
      </span>
    </fieldset>
    </form>
      <script type="text/javascript">
      $("a#toggler").click(function (event) {
        $("#konf-form").toggle();
      });
      </script>
    ';

        return $output;
    }

    /**
     * Lista dodanych aukcji
     * 
     * @return string 
     */
    public function getAuctionsList()
    {
        $result = array();
        $list = array();

        $url = 'http://www.allegro.pl/';
        if ($this->acountry != 1)
            $url = 'http://www.testwebapi.pl/';

        $query = 'SELECT a.*, p.active, l.name FROM ps_allegro a, ps_product p, ps_product_lang l where a.id_product = p.id_product and p.id_product = l.id_product and a.date_add < "' . date('Y-m-d H:i:s', strtotime('+ 1 month 7 days')) . '" and id_lang = 6 order by a.date_add DESC;';
        $result = Db::getInstance()->ExecuteS($query);

        // ile dni po wygasnieciu aukcji pokazywac ja na liscie
        $buff = 7;

        foreach ($result as $r)
        {
            $passedTime = strtotime($r['date_add'] . ' + ' . ($r['duration'] + $buff) . ' days');

            if ($passedTime > time())
                $list[] = $r;
        }

        $html = '<table id="auction-list" class="table"><tbody>';
        if (count($list) > 0)
        {
            $html .= '<tr>
        <th>Lp.</th>
        <th>Id aukcji</th>
        <th>Id prod.</th>
        <th>Tytuł</th>
        <th>Czas</th>
        <th>Wyst.</th>
        <th>Sprzed.</th>
        <th>Cena</th>
        <th>Data utw.</th>
        <th>Data zak.</th>
        <th>Status</th>
        <th>Opcje</th>
        </tr>';
            foreach ($list as $key => $a)
            {
                $endDate = date('Y-m-d H:i:s', strtotime($a['date_add'] . ' + ' . $a['duration'] . ' days'));
                $html .= '<tr>
        <td>' . ($key + 1) . '</td>
        <td><a target="_blank" href="' . $url . 'ShowItem2.php?item=' . $a['allegro_id'] . '">' . $a['allegro_id'] . '</a></td>
        <td>' . $a['id_product'] . '</td>        
        <td>' . $a['title'] . '</td>
        <td>' . $a['duration'] . '</td>
        <td>' . $a['quantity'] . '</td>
        <td>' . $a['sold'] . '</td>
        <td>' . sprintf("%.2f", $a['price']) . '</td>
        <td>' . $a['date_add'] . '</td>
        <td>' . ($a['status'] == 1 ? $endDate : $a['date_upd']) . '</td>
        <td class="status_' . $a['allegro_id'] . '">' . ($a['status'] == 1 ? 'trwająca' : 'zakończona') . '</td>
        <td class="options_' . $a['allegro_id'] . '">' . ($a['status'] == 1 ? '<a rel="' . $a['allegro_id'] . '" class="closeauction ' . $a['allegro_id'] . '" href="' . $_SERVER['REQUEST_URI'] . '&closeauction=' . $a['allegro_id'] . '">Zakończ</a>' : '') . '</td>
      </tr>';
            }
        }
        else
            $html .= '<tr><td>Brak dodanych aukcji</td></tr>';
        $html .= '</tbody></table>
        <script type="text/javascript">
        $("a.closeauction").click(function (event) {
          event.preventDefault();
          var answer = confirm("Czy na pewno zakończyć tą aukcję?");
          if (answer){
              //window.location = $(this).attr(\'href\');
              var id = $(this).attr("rel");
              $(".options_" + id + " a").hide();
              $(".options_" + id).append("<span>Czekaj...</span>");
              $.ajax({
                  url: \'/modules/jballegro/ajax.php\',
                  data: \'&closeauction=\' + id,
                  type: "POST",
                  context: id,
                  dataType: "json",
                  success: function(data) {
                    $(".options_" + id + " span").remove();
                    if (data.status == "success") {
                      $(".status_" + id).text("Zakończona");
                      $(".options_" + id).html("");
                    }
                    else $(".options_" + id + " a").show();
                    alert(data.msg);
                  }
               });
          }
        });
        </script>
    ';

        return $html;
    }

    /**
     * Wyszukiwanie podkategorii dla danej kategorii
     * 
     * @param integer $id Id kategorii
     * @param array $categories List kategorii
     * @return array
     */
    public function findCategoryChildrenInArray($id, $categories)
    {
        $output = array();

        foreach ($categories as $element)
        {
            if ($element['cat-parent'] == $id)
            {
                $output[] = $element;
            }
        }

        return $output;
    }

    /**
     * Metoda buduje listę podkategori dla podanej kategorii
     * 
     * @param Integer $id Id rodzica
     * @param Array $categories Tablica kategorii
     */
    public function buildSubcategoriesHtml($id, $array, $lvl)
    {
        $children = $this->findCategoryChildrenInArray($id, $array);

        $html = '';

        $lvl++;

        foreach ($children as $child)
        {
            $html .= "<option style='padding-left: " . ($lvl * 10) . "px;' value='" . $child['cat-id'] . "'>" . $child['cat-name'] . "</li>";
            $html .= $this->buildSubcategoriesHtml($child['cat-id'], $array, $lvl);
        }

        return $html;
    }

    /**
     * Przygotowanie formularza z wyborem kategorii allegro
     * 
     * @param type $categories
     * @return string 
     */
    public function buildCategoriesHtml($categories)
    {
        $html = '<option value="">Wybierz kategorię</option>';

        foreach ($categories as $category)
        {
            $html .= "<option value='" . $category['id'] . "'>" . $category['name'] . "</option>";
        }

        return $html;
    }

    /**
     * Pobranie obrazka produktu do wstawienia na allegro
     * 
     * @param type $id
     * @param type $ext
     * @return type 
     */
    public function getProductImage($id, $ext = 'jpg')
    {
        $query = 'SELECT id_image FROM ' . _DB_PREFIX_ . 'image where id_product = ' . $id . ' AND cover = 1 LIMIT 1;';
        $result = Db::getInstance()->ExecuteS($query);

        if (count($result) > 0)
        {
            $img_id = $result[0]['id_image'];

            $sourcepath1 = getcwd() . '/../img/p/' . $id . "-$img_id-large.$ext";

            $folders = str_split($img_id);
            $path = implode('/', $folders) . '/';
            $sourcepath2 = getcwd() . '/../img/p/' . $path . $img_id . "-large.$ext";

            if (file_exists($sourcepath1))
                return file_get_contents($sourcepath1);
            else if (file_exists($sourcepath2))
                return file_get_contents($sourcepath2);
        }
    }

    /**
     * Parsuje opis produktu i zamienia linki na absolutne sciezki oraz zastepuje zmienne danymi
     * 
     * @param string $desc 
     */
    public function parseAuctionContent($string)
    {

        $string = preg_replace('/src\=\"\.\.\/img\/cms\//', 'src="http://' . $_SERVER['SERVER_NAME'] . '/img/cms/', $string);

        return $string;
    }

    /**
     * Lista wojewodztw - konfiguracja
     * @param type $current
     * @return string 
     */
    public function getSelectProvince($current)
    {
        $provinces = array(
            'Dolnośląskie',
            'Kujawsko-pomorskie',
            'Lubelskie',
            'Lubuskie',
            'Łódzkie',
            'Małopolskie',
            'Mazowieckie',
            'Opolskie',
            'Podkarpackie',
            'Podlaskie',
            'Pomorskie',
            'Śląskie',
            'Świętokrzyskie',
            'Warmińsko-mazurskie',
            'Wielkopolskie',
            'Zachodniopomorskie'
        );

        $str = '<select name="province">';
        foreach ($provinces as $province)
        {
            $selected = ($province == $current ? 'selected="selected"' : '');
            $str .= '<option val="'.$province.'" '.$selected.'>'.$province.'</option>';
        }
        $str .= '</select>';
        
        return $str;
    }

}