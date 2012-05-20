Jballegro 0.6 dla Prestashop 1.4
================================

Moduł integracji Prestashop z api allegro

Strona domowa autora: www.jbator.pl

Instrukcja obsługi modułu:


1) Instalacja:
--------------

Skopiować zawartość folderów na serwer:
### a) zawartość folderu admin (znajduje się w katalogu lib) skopiować do swojego folderu gdzie znajduje się panel administracyjny 
(nadpisać plik AdminProducts.php lub jeśli mamy wprowadzone jakieś zmiany dodać odpowiednie modyfikacje)
### b) skopiować folder classes (znajduje się w katalogu lib) do główego katalogu gdzie mamy zainstalowany sklep
(nadpisać jeden plik AdminTab.php)
### c) skopiować folder modułu do modułów sklepu

Kroki a i b są opcjonalne - dodają ikonkę allegro na liście produktów w katalogu i pozwalają na 
tworzenie aukcji bezpośrednio z listy produktów (Działa tylko w przypadku Prestashop 1.4!!!).

Instalacja polega na standardowej instalacji modułu w Prestashop (moduł znajduje się w zakładce Inne moduły). 

!!! WAŻNE: !!!
Mogą być problemy z pobraniem danych z allegro na hostingach, które limitują znacznie działanie
skryptów PHP to znaczy ograniczają użycie pamięci do 64MB i czas maksymalnego wykonania skryptu (do 30 sekund) - np: nazwa.pl.

Moduł po instalacji podczas pierwszego uruchomienia z poprawną konfiguracją próbuje pobrać z Api Allegro dostępne kategorie i dane dotyczące 
formularzy - jendakże na przykład w przypadku nazwa.pl nie jest w stanie tego zrobić w 30 sekund ponieważ danych jest zbyt dużo.
Dlatego trzeba te dane ręcznie zaimportować poprzez PhpMyAdmin bezpośrednio do bazy sklepu z przygotowanego pliku sql "jballegro.sql" (znajduje się w folderze data).
Sama instalacja modułu bez wpisywania danych konfiguracyjnych powinna przejść bezproblemowo (dodaje się struktura tabel ale bez zawartości).

W późniejszej wersji modułu możne znajdzie się porcjowanie danych dzięki instalator poradzi sobie z instalacją.


2) Konfiguracja:
----------------

Należy przejść do zakładki moduły, dalej rozwinąć zakładkę pozostałe moduły i wybrać konfigurację modułu Jballegro.
Moduł jest podzielony na 3 sekcje - konfiguracja, formularz aukcji allegro oraz lista aukcji allegro.
W formularzu konfiguracyjnym (należy rozwinąć poprzez kliknięcie na +) trzeba wpisać swoje dane konfiguracyjne allegro tj.
login i hasło allegro, klucz webapi allegro oraz numer id allegro (numeru szukaj w koncie allegro) 
oraz pozostałe dane:
* identyfikator kraju czyli kod Polski - w trybie testowym 228 lub 1 w przypadku normalego trybu pracy
* kod szablonu - jesli posiadasz szablon, mozesz wkleić jego kod pamiętając o umieszczeniu specjalnego znacznika {{allegro}}
w miejscu w kodzie w którym ma się pojawiać opis aukcji
* koszty transportu - dane te będą używane domyślnie w każdej aukcji  


3) Jak wystawiać produkty na allegro:
-------------------------------------

### A:
* Można kliknąć ikonkę allegro przy produkcie na liście w katalogu produktów (ikonka obok ikonek edycji, usuwania i duplikacji).
* Po kliknięciu nastąpi przekierowanie do modułu jballegro, który jest odpowiedzialny za wystawianie przedmiotów.
* W module jest dostępny formularz wystawiania aukcji podobny do tego standardowego formularza z allegro. 
Większość pól zostanie automatycznie uzupełniona na podstawie danych produktów ze sklepu: tytuł aukcji - nazwa produktu, opis produktu (jeśli jest),
oraz cena produktu. 
* Inne dane takie jak koszty dostawy, opcje wysyłki zależą odpowiednio od konfiguracji modułu jballego oraz konfiguracji 
samego sklepu - można je edytować w formularzu akucji albo w odpowienich miejscach sklepu (będą automatycznie wczytywane w kolejnych aukcjach)
* Samemu należy wybrać w formularzu kategorię i województwo. Należy też pamietać że tytuł aukcji nie może przekraczać 50 znaków.
* Po wypełnieniu należy kliknąć przycisk Utwórz aukcję - jeśli wszystko było podane poprawnie aukcja zostanie utworzona a w bazie zostanie 
zapisana odpowiednia informacja - historia aukcji. 
* Lista aukcji znajduje się na samym dole strony modułu jballegro - jest widoczna informacja o
produkcie, cenie, ilosci, numerze aukcji - bezpośredni link do aukcji.

### B: 
* Produkty można też dodawać bezpośrednio ze strony modułu jballegro:
Moduł znajduje się w zakładce Moduły / Pozostałe moduły / Jballegro -> konfiguracja.
* Na stronie konfiguracji można ustawić wszyskie dane dotyczące web-api allegro, dodać nową aukcję oraz przeglądać listę produktów które zostały wystawione.
* W formularzu dodawania aukcji w pierwszym polu należy wpisywać nazwę produktu lub numer lub Id - działa tutaj autopodpowiadanie gdzie 
należy wybrać produkt. 
* Po wybraniu dane produktu powinny być automatycznie załadowane do formularza.
* Dalsze kroki występują tak samo jak w pkt. A (pkt 4 i dalej)


4) Uruchomienie sprawdzania stanów i automatycznej aktualizacji aukcji
----------------------------------------------------------------------

Możliwe jest uruchomienie skryptu, który będzie sprawdzał stan produktów w sklepie i jeśli będzie wynosił 0, aukcja zostanie automatycznie
zakończona. 
Moduł również posiada skrypt, który sprawdza stan aukcji na allegro i odpowienio aktualizuje stan magazynowy w sklepie.
Aby powyższe funkcje zadziałały, należy uruchomić 2 zadania CRON na swoim hostingu:

* aktualizacja stanów i sprawdzanie stanu akukcji - wywołanie np. co pół godziny url'a: 

    http://www.example.com/modules/jballegro/cron.php?checkallegro=1

* sprawdzanie stanu w sklepie i kończenie aukcji - wywołanie np. co 5 minut: 

    http://www.example.com/modules/jballegro/cron.php?checkaqty=1

Do wywoływania url'a za pomocą cron'a służą komendy wget lub lynx, np aby wykonać powyższe zadania należy dla crona zdefiniować:

    */30	*	*	*	*	/usr/bin/lynx --dump http://www.example.com/modules/jballegro/cron.php?checkallegro=1
    */5         *	*	*	*	/usr/bin/wget http://www.example.com/modules/jballegro/cron.php?checkaqty=1

(www.example.com - ten adres należy zastąpić swoim adresem sklepu:)


5) Info
-------

Moduł to na razie wersja rozwojowa więc możliwe są błędy. 
Nie odpowiadam za jakiekolwiek szkody wynikające z jego użytkowania 
- używasz go na własną odpowiedzialność.

Jeśli znalazłeś błąd lub masz pomysł jak rozbudować moduł - zapraszam na moją stronę www.jbator.pl

6) Chcesz pomóc w rozwoju modułu?
---------------------------------

Zapoznaj się z poniższymi złotymi zasadami zanim zaczniesz przesyłać swoje Pull Requesty:

### Zasady:

* Dopisuj komentarze przy każdej klasie, metodzie oraz w miejscach gdzie uważasz, że komentarz może pomóc zrozumieć twój kod
* DRY - Don't Repeat Yourself
* Nigdy nie wymuszaj zapisu pliku binarnego do repozytorium
* Używaj CamelCase dla zmiennych, nazwy klas rozpoczynaj dużą literą, nazwy metod oraz zmiennych rozpoczynaj małą literą
* Zalecany edytor: NetBeans 7.1.1
* Komituj tylko jeśli jesteś w 100% pewien że twój kod nic nie zepsuje i moduł będzie działał

### W miarę możliwości stosuj się do standardów kodowania:

Following coding standards is one of the easiest way for everybody to understand everybody's code:

* Never use tabulations in the code. Indentation is done by steps of 4 spaces:

    <?php
    class sfFoo
    {
        public function bar()
        {
            sfCoffee::make();
        }
    }

* Don't put spaces after an opening parenthesis and before a closing one.

    <?php
    if ($myVar == getRequestValue($name))    // correct
    if ( $myVar == getRequestValue($name) )  // incorrect

* Use `camelCase`, not underscores, for variable, function and method names:

    Good: `function makeCoffee()`
    Bad: `function MakeCoffee()`
    Bad: `function make_coffee()`

* Use underscores for option/argument/parameter names.

* Braces always go on their own line.

* Use braces for indicating control structure body regardless of number of statements it contains.

* Every class method or member definition should explicitly declare its visibility using the `private`, `protected` or `public` keywords.

* In a function body, return statements should have a blank line prior to it to increase readability:

    <?php
    function makeCoffee()
    {
        if (isSleeping() !== false && hasEnoughCaffeineForToday() !== false)
        {
            canMakeCoffee();
            return 1;
        }
        else
        {
            cantMakeCoffee();
        }
        return null;
    }

* All one line comments should be on their own lines and in this format:

    `<?php
    // space first, with no full stop needed`

* Avoid evaluating variables within strings, instead opt for concatenation:

    `<?php
    $string = 'something';
    $newString = "$string is awesome!";  // bad, not awesome
    $newString = $string.' is awesome!'; // better
    $newString = sprintf('%s is awesome', $string); // for exception messages and strings with a lot of substitutions`

* Use lowercase PHP native typed constants: `false`, `true` and `null`. The same http://www.tiffanyjewellers.us/ tiffany jewellersgoes for `array()`. At the opposite, always use uppercase strings for user defined constants, like `define('MY_CONSTANT', 'foo/bar')`. Better, try to always use class constants:

    `<?php
    class sfCoffee
    {
        const HAZ_SUGAR = true;
    }
    var_dump(sfCoffee::HAZ_SUGAR);`

* To check if a variable is `null` or not, don't use the `is_null()` native PHP function:

    `<?php
    if ($coffee !== null)
    {
        echo 'I can haz coffee';
    }`

* When comparing a variable to a string, put the string first and use type testing when applicable:

    `<?php
    if ($variable === 1)`

* Use PHP type hinting in functions and method signatures:

    `<?php
    public function notify(sfEvent $event)
    {
        // ...
    }`

* All function and class methods should have their phpdoc own block:

* All `@...` statements do not end with a dot.
`@param` lines state the type and the variable name. If the variable can have multiple types, then the `mixed` type must be used.
Ideally `@...` lines are vertically lined up (using spaces):
    
    <?php
    /**
     * Notifies all listeners of a given event.
     *
     * @param  sfEvent  $event  A sfEvent instance
     *
     * @return sfEvent          The sfEvent instance
     */
    public function notify(sfEvent $event)`


