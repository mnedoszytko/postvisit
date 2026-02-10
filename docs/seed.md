# PostVisit.ai — dokumentacja robocza (hackathon / demo)

## 0. Kontekst hackathonu i kryteria oceny
Ten projekt powstaje **w ramach hackathonu Built with Opus 4.6** i musi spełnić jego warunki. Najważniejsze punkty do podkreślenia w repo i demo:
- **Open Source**: wszystko pokazane w demo musi być open source (backend, frontend, modele, pozostałe komponenty).
- **New Work Only**: projekt musi być zbudowany od zera w trakcie hackathonu.
- **Team size**: do 2 osób.
- **Banned Projects**: dyskwalifikacja, jeśli projekt narusza prawo/etykę/polityki platform, lub używa kodu/danych/assetów bez praw do ich wykorzystania.

Kryteria oceny (na które świadomie projektujemy demo i narrację):
- **Impact (25%)** — realny potencjał i wartość rozwiązania.
- **Opus 4.6 Use (25%)** — kreatywne użycie możliwości Opus 4.6.
- **Depth & Execution (20%)** — inżynieria, dbałość o detale, gotowość do rozwoju.
- **Demo (30%)** — jakość i atrakcyjność pokazu na filmie.

## 0.1. Problem statements (dopasowanie projektu)
Hackathon wyróżnia 3 główne kierunki projektów:
- **Build a Tool That Should Exist** — AI‑native narzędzie, które eliminuje żmudną pracę.
- **Break the Barriers** — dostęp do ekspertowej wiedzy i narzędzi dla wszystkich.
- **Amplify Human Judgment** — AI wzmacnia profesjonalny osąd bez zastępowania człowieka.

PostVisit.ai mapuje się na **wszystkie trzy**:
- **Build a Tool That Should Exist**: eliminuje żmudne „przebijanie się" przez rekordy i kontekst po wizycie; porządkuje to, co już jest w dokumentacji.
- **Break the Barriers**: daje pacjentowi dostęp do eksperckiej wiedzy z wizyty w prostym języku, bez bariery specjalistycznego żargonu.
- **Amplify Human Judgment**: feedback i kontekst wracają do lekarza (doctor‑in‑the‑loop), a system nie zastępuje decyzji klinicznej.

## 1. Cel i kontekst
PostVisit.ai to system, który utrzymuje kontekst **konkretnej wizyty klinicznej** i pomaga pacjentowi po wyjściu z gabinetu. Jego zadaniem jest wyjaśnianie i porządkowanie informacji z wizyty, a nie zastępowanie lekarza.

Główne założenie: **pacjent po wizycie potrzebuje jasnych, prostych wyjaśnień tego, co już zostało powiedziane i zapisane przez lekarza**. System nie jest ogólnym chatbotem zdrowotnym i nie działa poza kontekstem tej wizyty.

## 2. Problem
- Pacjent po wizycie często nie pamięta zaleceń.
- Nie rozumie terminów medycznych.
- W okresie rekonwalescencji nie ma wsparcia i dopytuje telefonicznie o rzeczy związane z tą samą wizytą.

## 3. Kluczowa różnica
PostVisit.ai nie odpowiada „ogólnie" o zdrowiu. Jest **zakotwiczony w jednej, konkretnej wizycie**, a wszystkie odpowiedzi i wyjaśnienia wynikają tylko z tego kontekstu.

## 4. Primary user
- **Pacjent**

## 5. Źródła kontekstu
- wypis / zalecenia
- dokumenty medyczne
- rekord medyczny pacjenta (healthcare record)
- transkrypcja rozmowy (ambient scribing po stronie pacjenta)
- opcjonalnie: oddzielny skryba lekarza (temat otwarty)

## 6. Zakres funkcjonalny (demo)
### 6.1. Ekran pacjenta (ostatnia wizyta)
Ekran pacjenta pokazuje **ostatnią wizytę** (nie tylko kardiologiczną — docelowo wiele specjalizacji). W demo używamy scenariusza kardiologicznego, ale struktura jest wspólna dla wszystkich specjalizacji.

Widoczne sekcje:
- Protokół / opis wizyty
- Dodatkowe badania (np. ECHO, EKG, inne badania — zależnie od specjalizacji)
- Zalecenia lekarza
- Leki (nowe / zmienione / kontynuowane)
- Kolejne kroki

Każdy element jest klikalny i może zostać wyjaśniony w prostym języku.

### 6.2. Q&A po wizycie (w kontekście)
Pacjent może zadawać pytania dotyczące treści z wizyty:
- wyjaśnienie pojęć i diagnozy
- informacje o lekach i interakcjach
- wyjaśnienie zaleceń

System nie generuje nowych zaleceń. W przypadku ryzykownych objawów ma eskalować i odsyłać do lekarza.

## 7. Scenariusz demo
- Młody lekarz i młody pacjent
- Pacjent: dodatkowe pobudzenia komorowe
- Zalecenia: propranolol, więcej snu, mniej stresu
- System:
  - pokazuje zalecenia i leki
  - tłumaczy terminologię
  - utrzymuje kontekst jednej wizyty

## 8. Język produktu
- Dokumentacja robocza: **język polski**
- Produkt / UI: **język angielski** z opcją „International"
- Inne języki: opcjonalnie później

## 9. Stack
- Backend: **Laravel**
- Frontend: **Vue**
- Model docelowy: **Claude Opus 4.6**
- Testy: możliwe na tańszych modelach (dla kosztów)

## 10. Compliance i bezpieczeństwo (dla demo)
Ta sekcja opisuje **sposób myślenia o zgodności** i to, co pokażemy w demo. To nie jest porada prawna ani pełna analiza.

### 10.1. HIPAA — plan minimum (USA)
Założenia:
- Przetwarzamy dane medyczne z wizyty (PHI) → HIPAA ma zastosowanie, jeśli działamy z podmiotem medycznym w USA.
- W takim przypadku system działa jako **Business Associate** i wymaga **BAA** z placówką.

Co musimy pokazać w demo (symulacyjnie):
- **Minimum necessary**: system używa tylko danych niezbędnych do obsługi wizyty.
- **Access control**: oddzielny dostęp pacjenta i lekarza.
- **Audit trail**: logowanie dostępu do danych (choćby w podstawowej formie).
- **Encryption**: dane szyfrowane w ruchu i w spoczynku (deklaracja i widoczny kierunek w architekturze).
- **Incident response**: informacja, że system ma procedury raportowania incydentów.

### 10.2. GDPR — plan minimum (UE)
Założenia:
- Dane osobowe i medyczne są szczególnie wrażliwe.

Co musimy pokazać w demo (symulacyjnie):
- **Podstawa przetwarzania** (zgoda / umowa).
- **Prawo do wglądu i usunięcia** (możliwość w UI lub deklaracja w dokumentacji).
- **Minimalizacja danych** i ograniczenie celu do jednej wizyty.

### 10.3. Inne certyfikacje (docelowo)
- **SOC 2** (często wymagane przez klientów w USA)
- **ISO 27001** (system zarządzania bezpieczeństwem informacji)
- **HITRUST** (często spotykane w healthcare)

W demo nie wdrażamy certyfikacji, ale pokazujemy, że architektura jest gotowa do ich wymagań.

### 10.4. Guardrails w produkcie
- System nie wydaje zaleceń poza tym, co wynika z wizyty.
- W przypadku ryzykownych objawów: komunikat o konieczności kontaktu z lekarzem.
- Informacje są tłumaczone, nie „diagnozowane".
