<?php

class Allegrowebapi {

  protected $instance;
  protected $config;
  protected $session;
  protected $client;
  protected $local_version;
  protected $countryCode;


  const QUERY_ALLEGROWEBAPI = 1;

  /**
   * Zapis ustawień oraz połączenie z WebAPI
   * 
   * @param type $id
   * @param type $key
   * @param type $login
   * @param type $pass 
   */
  public function __construct($id, $key, $login, $pass, $countryCode) {
    $this->config = array(
        'allegro_id' => $id,
        'allegro_key' => $key,
        'allegro_login' => $login,
        'allegro_password' => $pass
    );

    $this->countryCode = $countryCode;

    $this->client = new SoapClient('https://webapi.allegro.pl/uploader.php?wsdl');
  }

  /**
   * Metoda pozwala na pobranie pełnego drzewa kategorii dostępnych we wskazanym kraju.
   * (http://allegro.pl/webapi/documentation.php/show/id,46)
   *
   * @return array
   */
  public function getCatsData() {
    return $this->client->doGetCatsData(
                    $this->countryCode, '0', $this->config['allegro_key']
    );
  }

  /**
   * Metoda pozwala na pobranie licznika kategorii dostępnych we wskazanym kraju.
   * (http://allegro.pl/webapi/documentation.php/show/id,47)
   *
   * @return array
   */
  public function getCatsDataCount() {
    return $this->client->doGetCatsDataCount(
                    $this->countryCode, '0', $this->config['allegro_key']
    );
  }

  /**
   * Metoda pozwala na pobranie w porcjach pełnego drzewa kategorii dostępnych we wskazanym kraju.
   * Domyślnie zwracanych jest 50 pierwszych kategorii. Rozmiar porcji pozwala regulować parametr package-element,
   * a sterowanie pobieraniem kolejnych porcji danych umożliwia parametr offset.
   * (http://allegro.pl/webapi/documentation.php/show/id,48)
   *
   * @param array $Options
   * @return array
   */
  public function getCatsDataLimit($Options) {
    return $this->client->doGetCatsDataLimit(
                    $this->countryCode, '0', $this->config['allegro_key'], $Options['offset'], $Options['package-element']
    );
  }

  /**
   * Metoda pozwala na pobranie wartości wszystkich wersjonowanych komponentów oraz umożliwia
   * podgląd kluczy wersji dla wszystkich krajów.
   * (http://allegro.pl/webapi/documentation.php/show/id,62)
   *
   * @return array
   */
  public function queryAllSysStatus() {
    return $this->client->doQueryAllSysStatus(
                    $this->countryCode, $this->config['allegro_key']
    );
  }

  /**
   * Metoda pozwala na pobranie wartości jednego z wersjonowanych komponentów (program, drzewo kategorii, usługa,
   * parametry, pola formularza sprzedaży, serwisy) oraz umożliwia podgląd klucza wersji dla wskazanego krajów.
   * (http://allegro.pl/webapi/documentation.php/show/id,61)
   *
   * @param int $Component
   * 		1 - usługa Allegro WebAPI,
   * 		2 - aplikacja,
   * 		3 - struktura drzewa kategorii,
   * 		4 - pola formularza sprzedaży,
   * 		5 - serwisy
   *
   * @return array
   */
  public function querySysStatus($Component) {
    return $this->client->doQuerySysStatus(
                    $Component, $this->countryCode, $this->config['allegro_key']
    );
  }

  /*   * ********************************************************************************************************
   * Logowanie (http://allegro.pl/webapi/documentation.php/theme/id,22)
   * ******************************************************************************************************* */

  /**
   * Metoda pozwala na uwierzytelnienie i autoryzację użytkownika za pomocą danych dostępowych do konta
   * (podając hasło w postaci zakodowanej SHA-256 a następnie base64 lub hasło w wersji tekstowej).
   * Po pomyślnym uwierzytelnieniu, użytkownik otrzymuje identyfikator sesji, którym następnie może
   * posłużyć się przy wywoływaniu metod wymagających autoryzacji. Identyfikator sesji zachowuje
   * ważność przez 3 godziny od momentu jego utworzenia.
   * (http://allegro.pl/webapi/documentation.php/show/id,82)
   *
   * @param bool $Encode
   */
  public function login($Encode=false) 
  {
    $version = $this->QuerySysStatus(1);
    $this->local_version = $version['ver-key'];

    if (!$Encode) {
      $session = $this->client->doLogin(
              $this->config['allegro_login'], $this->config['allegro_password'], $this->countryCode, $this->config['allegro_key'], $version['ver-key']
      );
    }
    else {
      if (function_exists('hash') && in_array('sha256', hash_algos())) {
        $pass = hash('sha256', $this->config['allegro_password'], true);
      }
      else if (function_exists('mhash') && is_int(MHASH_SHA256)) {
        $pass = mhash(MHASH_SHA256, $this->config['allegro_password']);
      }

      $password = base64_encode($pass);

      $session = $this->client->doLoginEnc(
              $this->config['allegro_login'], $password, $this->countryCode, $this->config['allegro_key'], $version['ver-key']
      );
    }

    $this->session = $session;

    unset($password);
    unset($this->config['allegro_password']);
  }

  /**
   * Metoda pozwala na kończenie przed czasem (z lub bez odwołania ofert) aukcji zalogowanego użytkownika.
   * (http://allegro.pl/webapi/documentation.php/show/id,221)
   * @param integer $id Id aukcji allegro
   * @param array $Options
   * @return array
   */
  public function finishItem($id, $cancelBids = 0, $cancelReason = '') {
    
    $this->checkConnection();
    return $this->client->doFinishItem(
            $this->session['session-handle-part'], 
            $id, 
            $cancelBids,
            $cancelReason
    );
  }

  /**
   * Metoda pozwala na kończenie przed czasem (bez lub z odwołaniem ofert) wielu aukcji zalogowanego użytkownika.
   * (http://allegro.pl/webapi/documentation.php/show/id,623)
   *
   * @param array $Options
   * @return array
   */
  public function finishItems($Options) {
    $this->checkConnection();
    return $this->client->doFinishItems(
                    $this->session['session-handle-part'], $Options
    );
  }

  /**
   * Metoda pozwala na pobranie listy ulubionych kategorii zalogowanego użytkownika.
   * (http://allegro.pl/webapi/documentation.php/show/id,49)
   *
   * @return array
   */
  public function getFavouriteCategories() {
    $this->checkConnection();
    return $this->client->doGetFavouriteCategories(
                    $this->session['session-handle-part']
    );
  }

  /**
   * Metoda pozwala na pobranie listy komunikatów serwisowych ze strony Nowości i komunikaty dla wskazanego kraju.
   * Zwróconych może być maks. 50 ostatnich komunikatów dla danej kategorii - ich lista posortowana
   * jest malejąco po czasie dodania. W przypadku nie podania daty (an-it-date) lub identyfikatora (ani-it-id)
   * komunikatu, zwrócony zostanie jeden najnowszy komunikat ze wskazanej kategorii.
   * (http://allegro.pl/webapi/documentation.php/show/id,93)
   *
   * @param array $Options
   * @return array
   */
  public function getServiceInfo($Options) {
    return $this->client->doGetServiceInfo(
                    $this->countryCode, $Options['an-cat-id'], $Options['an-it-date'], $Options['an-it-id'], $this->config['allegro_key']
    );
  }

  /*   * ********************************************************************************************************
   * Opłaty i prowizje (http://allegro.pl/webapi/documentation.php/theme/id,66)
   * ******************************************************************************************************* */

  /**
   * Metoda pozwala na pobranie informacji o opłatach związanych z korzystaniem z serwisu odpowiedniego dla wskazanego kraju.
   * (http://allegro.pl/webapi/documentation.php/show/id,88)
   *
   * @return array
   */
  public function getPaymentData() {
    return $this->client->doGetPaymentData(
                    $this->countryCode, $this->config['allegro_key']
    );
  }

  /**
   * Metoda pozwala na pobranie bieżącego salda z konta zalogowanego użytkownika.
   * (http://allegro.pl/webapi/documentation.php/show/id,109)
   *
   * @return array
   */
  public function myBilling() {
    return $this->client->doMyBilling(
                    $this->countryCode
    );
  }

  /*   * ********************************************************************************************************
   * Produkty w Allegro (http://allegro.pl/webapi/documentation.php/theme/id,141)
   * ******************************************************************************************************* */

  /**
   * Metoda pozwala obsłużyć mechanizm wyszukiwarki Produktów w Allegro i wyszukać produkty po kodzie EAN
   * (ISBN/ISSN/etc.). Podczas wywoływania metody możliwe jest określenie kategorii, do której ma być
   * zawężone wyszukiwanie, dzięki czemu zostanie zwiększona celność wyników. Metoda zwraca: ID znalezionego
   * produktu, jego nazwę, opis, zdjęcia oraz parametry. Część tych danych można potem wykorzystać podczas
   * wystawiania nowej aukcji za pomocą metody doNewAuctionExt.
   * (http://allegro.pl/webapi/documentation.php/show/id,643)
   *
   * @param string $Code
   * @return array
   */
  public function findProductByCode($Code) {
    $this->checkConnection();
    return $this->client->doFindProductByCode(
                    $this->session['session-handle-part'], $Code
    );
  }

  /**
   * Metoda pozwala obsłużyć mechanizm wyszukiwarki Produktów Allegro i wyszukać produkty po ich nazwie
   * lub części nazwy. Podczas wywoływania metody możliwe jest określenie kategorii, do której ma
   * być zawężone wyszukiwanie, dzięki czemu zostanie zwiększona celność wyników.
   * Metoda zwraca: ID znalezionych produktów, ich nazwę, opis, zdjęcia oraz parametry.
   * Część tych danych można potem wykorzystać podczas wystawiania nowej aukcji za pomocą metody doNewAuctionExt.
   * (http://allegro.pl/webapi/documentation.php/show/id,642)
   *
   * @param array $Options
   * @return array
   */
  public function findProductByName($Options) {
    $this->checkConnection();
    return $this->client->doFindProductByName(
                    $this->session['session-handle-part'], $Options['product-name'], $Options['category-id']
    );
  }

  /**
   * Metoda pozwala na pobranie danych na temat konkretnego produktu z katalogu Produktów w Allegro.
   * Do wywołania metody wymagany jest identyfikator produktu oraz hash - obie wartości mogą być
   * pobrane za pomocą metod doShowItemInfoExt oraz doGetItemsInfo (dla aukcji zintegrowanych z produktem).
   * (http://allegro.pl/webapi/documentation.php/show/id,644)
   *
   * @param array $Options
   * @return array
   */
  public function showProductInfo($Options) {
    $this->checkConnection();
    return $this->client->doShowProductInfo(
                    $this->session['session-handle-part'], $Options['product-id'], $Options['category-hash']
    );
  }

  /**
   * Metoda pozwala na pobranie pełnej listy sposobów dostawy dostępnych we wskazanym kraju.
   * (http://allegro.pl/webapi/documentation.php/show/id,624)
   *
   * @return array
   */
  public function getShipmentData() {
    return $this->client->doGetShipmentData(
                    $this->countryCode, $this->config['allegro_key']
    );
  }

  /**
   * Metoda pozwala na pobranie aktualnego (dla danego kraju) czasu z serwera Allegro.
   * (http://allegro.pl/webapi/documentation.php/show/id,81)
   *
   * @return array
   */
  public function getSystemTime() {
    return $this->client->doGetSystemTime(
                    $this->countryCode, $this->config['allegro_key']
    );
  }

  /*   * ********************************************************************************************************
   * Wystawianie aukcji (http://allegro.pl/webapi/documentation.php/theme/id,41)
   * ******************************************************************************************************* */

  /**
   * Metoda pozwala na sprawdzenie ogólnych oraz szczegółowych kosztów związanych z wystawieniem
   * aukcji przed jej faktycznym wystawieniem. Metoda może służyć także jako symulator poprawności
   * wystawienia aukcji, ponieważ struktura pól jaką przyjmuje jako jeden z parametrów jest
   * identyczną z tą przyjmowaną przez doNewAuctionExt.
   * (http://allegro.pl/webapi/documentation.php/show/id,41)
   *
   * @param array $Fields
   * @return array
   */
  public function checkNewAuctionExt($Fields) {
    $this->checkConnection();
    return $this->client->doCheckNewAuctionExt(
                    $this->session['session-handle-part'], $Fields
    );
  }

  /**
   * Metoda pozwala na pobranie listy pól formularza sprzedaży dostępnych we wskazanym kraju.
   * Wybrane pola mogą następnie posłużyć np. do zbudowania i wypełnienia formularza
   * wystawienia nowej aukcji z poziomu metody doNewAuctionExt.
   * (http://allegro.pl/webapi/documentation.php/show/id,91)
   *
   * @return array
   */
  public function getSellFormFieldsExt() {
    return $this->client->doGetSellFormFieldsExt(
                    $this->countryCode, '0', $this->config['allegro_key']
    );
  }

  /**
   * Metoda pozwala na pobranie w porcjach listy pól formularza sprzedaży dostępnych we wskazanym kraju.
   * Wybrane pola mogą następnie posłużyć np. do zbudowania i wypełnienia formularza wystawienia
   * nowej aukcji z poziomu metody doNewAuctionExt. Domyślnie zwracanych jest 50 pierwszych pól.
   * Rozmiar porcji pozwala regulować parametr package-element, a sterowanie pobieraniem
   * kolejnych porcji danych umożliwia parametr offset.
   * (http://allegro.pl/webapi/documentation.php/show/id,92)
   *
   * @param array $Options
   * @return array
   */
  public function getSellFormFieldsExtLimit($offset = null, $packageElement = null) {
    return $this->client->doGetSellFormFieldsExt(
                    $this->countryCode, '0', $this->config['allegro_key'], $offset, $packageElement
    );
  }

  public function getSellFormFieldsForCategory($category) {
    return $this->client->doGetSellFormFieldsForCategory(
                    $this->config['allegro_key'], $this->countryCode, $category
    );
  }

  /**
   * Metoda pozwala na wystawienie nowej aukcji w serwisie. Aby sprawdzić poprawność wystawienia aukcji,
   * należy nadać jej dodatkowy, lokalny identyfikator (local-id), a następnie zweryfikować aukcję za
   * pomocą metody doVerifyItem (wartość local-id jest zawsze unikalna w ramach konta danego użytkownika).
   * Aby przetestować poprawność wypełnienia kolejnych pól formularza sprzedaży i/lub sprawdzić koszta związane
   * z wystawieniem aukcji, bez jej faktycznego wystawiania w serwisie, należy skorzystać z metody doCheckNewAuctionExt.
   * (http://allegro.pl/webapi/documentation.php/show/id,113)
   *
   * @param array $Options
   * @return array
   */
  public function newAuctionExt($fields, $private = null, $localId = null) {
    $this->checkConnection();
    return $this->client->doNewAuctionExt(
                    $this->session['session-handle-part'], $fields, $private, $localId
    );
  }

  /**
   * Metoda pozwala na wystawienie aukcji w serwisie na podstawie aukcji istniejących. Z uwagi na specyfikę
   * działania mechanizmu ponownego wystawiania aukcji - identyfikatory aukcji zwracane na wyjściu, to identyfikatory
   * aukcji na podstawie których nowe aukcje zostały/miały zostać wystawione - nie identyfikatory nowo wystawionych aukcji.
   * (http://allegro.pl/webapi/documentation.php/show/id,321)
   *
   * @param array $Options
   * @return array
   */
  public function sellSomeAgain($Options) {
    $this->checkConnection();
    return $this->client->doSellSomeAgain(
                    $this->session['session-handle-part'], $Options['sell-items-array'], $Options['sell-starting-time'], $Options['sell-auction-duration'], $Options['sell-option']
    );
  }

  /**
   * Metoda pozwala na sprawdzenie poprawności wystawienia aukcji (utworzonej za pomocą metody
   * doNewAuctionExt, w przypadku gdy przekazano przy jej wywołaniu wartość w parametrze local-id)
   * z konta zalogowanego użytkownika. Wartość local-id jest zawsze unikalna w ramach konta danego użytkownika.
   * (http://allegro.pl/webapi/documentation.php/show/id,181)
   *
   * @param int $LocalID
   * @return array
   */
  public function verifyItem($LocalID) {
    $this->checkConnection();
    return $this->client->doVerifyItem(
                    $this->session['session-handle-part'], $LocalID
    );
  }

  /*   * ********************************************************************************************************
   * Wyszukiwarka i listingi (http://allegro.pl/webapi/documentation.php/theme/id,68)
   * ******************************************************************************************************* */

  /**
   * Metoda pozwala na pobranie listy parametrów dostępnych dla danej kategorii we wskazanym kraju.
   * Wybrane parametry mogą następnie posłużyć np. do budowy filtra przy listowaniu
   * zawartości kategorii z poziomu metody doShowCat.
   * (http://allegro.pl/webapi/documentation.php/show/id,90)
   *
   * @param int $Cat
   * @return array
   */
  public function getSellFormAttribs($Cat) {
    return $this->client->doGetSellFormAttribs(
                    $this->countryCode, $this->config['allegro_key'], '0', $Cat
    );
  }

  /*   * ********************************************************************************************************
   * Przydatne funkcje
   * ******************************************************************************************************* */

  /**
   * Sprawdzanie połączenia oraz poprawnego zalogowania do allegro
   */
  private function checkConnection() {
    if (!$this->session) {
      throw new userException('Nie utworzono połączenia z kontem allegro. Należy użyć metody <strong>Login()</strong>');
    }
  }

  /**
   * Wywołanie dowolnej metody przez SOAP
   *
   * @param string $Method
   * @param string/int/array $Data
   * @return array
   */
  public function getMethod($Method, $Data=array()) {
    return $this->client->__soapCall($Method, $Data);
  }

  /**
   * Metoda pozwala na pobranie identyfikatora sesji po zalogowaniu.
   * Do wykorzystania z metodą getMethod
   *
   * @return string
   */
  public function getSession() {
    $this->checkConnection();
    return $this->session['session-handle-part'];
  }

  /**
   * Metoda pozwala na pobranie używanego kodu kraju.
   * Do wykorzystania z metodą getMethod
   *
   * @return int
   */
  public function getCountry() {
    return $this->countryCode;
  }

  /**
   * Metoda pozwala na pobranie aktualnie uzywanego klucza WebAPI
   * Do wykorzystania z metodą getMethod
   *
   * @return string
   */
  public function getKey() {
    return $this->config['allegro_key'];
  }

  /**
   * Metoda pozwala na pobranie klucza wersji WebAPI
   *
   * @return int
   */
  public function getVersion() {
    $version = $this->QuerySysStatus(1);
    return $version['ver-key'];
  }

  /**
   * Metoda pozwala na pobranie wszystkich aktualnie używanych
   * danych konfiguracyjnych
   *
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Konwersja obietu na tablicę
   *
   * @param object $object
   * @return array
   */
  static public function objectToArray($object) {
    if (!is_object($object) && !is_array($object))
      return $object;
    if (is_object($object))
      $object = get_object_vars($object);
    return array_map(array('AllegroWebAPI', 'objectToArray'), $object);
  }

  /**
   * Konwertowanie sekund na czas
   *
   * @param int $Secounds
   * @return string
   */
  public function sec2Time($Secounds) {
    $Time = new DateTime('@' . $Secounds, new DateTimeZone('UTC'));
    $GetTime = array('dni' => $Time->format('z'),
        'godzin' => $Time->format('G'),
        'minut' => $Time->format('i'),
        'sekund' => $Time->format('s')
    );
    if ($GetTime['dni'] > 1) {
      $TimeLeft = $GetTime['dni'] . " dni";
    }
    else if ($GetTime['dni'] == 1) {
      $TimeLeft = $GetTime['dni'] . " dzień";
    }
    else if ($GetTime['godzin'] > 1) {
      $TimeLeft = $GetTime['godzin'] . " godzin";
    }
    else if ($GetTime['godzin'] == 1) {
      $TimeLeft = $GetTime['godzin'] . " godzina";
    }
    else if ($GetTime['minut'] > 1) {
      $TimeLeft = $GetTime['minut'] . " minut";
    }
    else if ($GetTime['minut'] == 1) {
      $TimeLeft = $GetTime['minut'] . " minuta";
    }
    else if ($GetTime['sekund'] > 1) {
      $TimeLeft = $GetTime['sekund'] . " sekund";
    }
    else if ($GetTime['sekund'] == 1) {
      $TimeLeft = $GetTime['sekund'] . " sekunda";
    }
    return $TimeLeft;
  }

  /**
   * Pozostały czas do końca aukcji
   *
   * @param int $Secounds
   * @return string
   */
  public function endDate($Secounds) {
    $GetDay = date("N", time() + $Secounds);
    $num = array("1", "2", "3", "4", "5", "6", "7");
    $pl = array("Poniedziałek", "Wtorek", "Środa", "Czwartek", "Piątek", "Sobota", "Niedziela");
    $GetDay = str_replace($num, $pl, $GetDay);
    $GetDate = date("d-m-Y, H:i:s", time() + $Secounds);
    return $GetDay . " " . $GetDate;
  }

  /**
   * Punktacja użytkowników
   *
   * @param int $Stars
   * @return string
   */
  public function userStars($Stars) {
    $IconHost = "http://static.allegrostatic.pl/site_images/1/0/stars/";

    if ($Stars > 12500) {
      $Star = "star3125";
      $While = 4;
    }
    elseif ($Stars > 12499) {
      $Star = "star3125";
      $While = 4;
    }
    elseif ($Stars > 9374) {
      $Star = "star3125";
      $While = 3;
    }
    elseif ($Stars > 6249) {
      $Star = "star3125";
      $While = 2;
    }
    elseif ($Stars > 3124) {
      $Star = "star3125";
      $While = 1;
    }
    elseif ($Stars > 2499) {
      $Star = "star625";
      $While = 4;
    }
    elseif ($Stars > 1874) {
      $Star = "star625";
      $While = 3;
    }
    elseif ($Stars > 1249) {
      $Star = "star625";
      $While = 2;
    }
    elseif ($Stars > 624) {
      $Star = "star625";
      $While = 1;
    }
    elseif ($Stars > 499) {
      $Star = "star125";
      $While = 4;
    }
    elseif ($Stars > 374) {
      $Star = "star125";
      $While = 3;
    }
    elseif ($Stars > 249) {
      $Star = "star125";
      $While = 2;
    }
    elseif ($Stars > 124) {
      $Star = "star125";
      $While = 1;
    }
    elseif ($Stars > 99) {
      $Star = "star25";
      $While = 4;
    }
    elseif ($Stars > 74) {
      $Star = "star25";
      $While = 3;
    }
    elseif ($Stars > 49) {
      $Star = "star25";
      $While = 2;
    }
    elseif ($Stars > 24) {
      $Star = "star25";
      $While = 1;
    }
    elseif ($Stars > 19) {
      $Star = "star5";
      $While = 4;
    }
    elseif ($Stars > 14) {
      $Star = "star5";
      $While = 3;
    }
    elseif ($Stars > 9) {
      $Star = "star5";
      $While = 2;
    }
    elseif ($Stars > 4) {
      $Star = "star5";
      $While = 1;
    }
    elseif ($Stars > 3) {
      $Star = "star1";
      $While = 4;
    }
    elseif ($Stars > 2) {
      $Star = "star1";
      $While = 3;
    }
    elseif ($Stars > 1) {
      $Star = "star1";
      $While = 2;
    }
    elseif ($Stars > 0) {
      $Star = "star1";
      $While = 1;
    }
    elseif ($Stars > -1) {
      $Star = "star1";
      $While = 0;
    }

    for ($i = 1; $i <= $While; $i++) {
      $GetStars .= "<img src='" . $IconHost . $Star . ".gif' title='" . $Stars . " pkt. allegro' style='vertical-align:middle' alt='' />";
    }
    return $GetStars;
  }

  /**
   *
   * @param type $type
   * @param type $items
   * @param type $limit
   * @return type 
   */
  public function getMyAccountData($type, $items, $limit = 1) {
    
    $this->checkConnection();
    return $this->client->doMyAccount2(
            $this->session['session-handle-part'], 
            $type, 
            0,
            $items,
            $limit
    );
  }
  
}

?>