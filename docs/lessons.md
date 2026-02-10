# Lessons Learned — log poprawek i błędów

Ten plik rejestruje błędy popełnione przez Claude i korekty od użytkownika.
Co 3-5 iteracji robimy rewizję i najważniejsze wnioski przenosimy do CLAUDE.md.

## 2026-02-10

### Lesson 1: Nazwa projektu MedDuties
- **Błąd:** Claude szukał projektu "MedNutis" — błędna nazwa
- **Korekta:** Projekt nazywa się **MedDuties** (katalog `../dyzury`)
- **Wniosek:** Gdy user podaje nazwę projektu, dopytaj jeśli nie znajdziesz — nie zgaduj

### Lesson 2: Nie przenoś założeń z innych projektów
- **Błąd:** CLAUDE.md zawierał "MySQL, Redis, PHP 8.2+" — skopiowane z PreVisit bez pytania
- **Korekta:** PHP 8.4 (bez dyskusji), baza danych i cache do ustalenia
- **Wniosek:** Każdy projekt ma własne założenia. Nie kopiuj stacku z siostrzanych projektów — pytaj albo oznacz jako TBD
