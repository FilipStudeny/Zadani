# Zadání

## 🔧 Setup

1. Spusť `docker compose up` pro nastartování prostředí.
2. Spusť databázový skript:
   ```bash
   php cli/update-database.php
   ```
3. Spusť seedování databáze:
   ```bash
   php cli/seed-database.php
   ```

## 🛠️ Vytvoření migrace

1. Uprav model dle potřeby.
2. Vygeneruj migraci:
   ```bash
   php cli/create-migration.php
   ```
3. Aplikuj migraci:
   ```bash
   php cli/update-database.php
   ```
4. Pokud je potřeba vrátit změny:
   ```bash
   php cli/rollback-migration.php
   ```
5. Pro seedování dat:
   ```bash
   php cli/seed-database.php
   ```

## 🧪

- **Zobrazení všech dostupných rout**:  
  ```http
  GET /debug/routes
  ```

## 🗂️ Struktura projektu

```
/app
├── /Api
│   └── /Controllers         → API kontroléry
├── /Domain                  → Doménové modely, byznys logika
└── /Infrastructure          → Infrastruktura (routování, databáze, DI, HTTP)

/tests
├── /application             → Integrační testy
└── /http                    → HTTP request soubory

/index.php                   → Vstupní bod aplikace, konfigurace routování
```
