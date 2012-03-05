Jballegro 0.5 dla Prestashop 1.4
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