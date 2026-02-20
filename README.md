# Expense Tracker

A Laravel-based expense tracking application for managing income and expenses with category support.

## Features

- Track income and expenses
- Categorize transactions
- Filter by date range, category, and type
- View statistics and summaries
- RESTful API endpoints

## Tech Stack

- Laravel 12
- PHP 8.2+
- SQLite (default) / MySQL / PostgreSQL
- Alpine.js
- Tailwind CSS
- Vite

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy `.env.example` to `.env` and configure your database
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations and seed sample data:
   ```bash
   php artisan migrate --seed
   ```
6. Build frontend assets:
   ```bash
   npm run build
   ```
7. Start the development server:
   ```bash
   php artisan serve
   ```
8. Access the application at `http://localhost:8000`

**Default credentials:**
- Email: `test@example.com`
- Password: `password`

## API Endpoints

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/categories` | List all categories |
| POST | `/api/v1/categories` | Create a new category |
| GET | `/api/v1/categories/{id}` | Get a specific category |
| PUT/PATCH | `/api/v1/categories/{id}` | Update a category |
| DELETE | `/api/v1/categories/{id}` | Delete a category |

**Category Fields:**
- `name` (required, string, max:255)
- `type` (required, enum: income/expense)
- `color` (optional, string, hex color code)

**Filter Parameters:**
- `type` - Filter by category type (income/expense)

### Expenses

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/expenses` | List all expenses |
| POST | `/api/v1/expenses` | Create a new expense |
| GET | `/api/v1/expenses/{id}` | Get a specific expense |
| PUT/PATCH | `/api/v1/expenses/{id}` | Update an expense |
| DELETE | `/api/v1/expenses/{id}` | Delete an expense |
| GET | `/api/v1/expenses/statistics/summary` | Get expense statistics |

**Expense Fields:**
- `user_id` (required, exists in users table)
- `category_id` (required, exists in categories table)
- `amount` (required, numeric, min:0.01, max:9999999.99)
- `type` (required, enum: income/expense)
- `description` (optional, string, max:1000)
- `date` (required, date, cannot be future)
- `payment_method` (optional, string, max:255)
- `reference` (optional, string, max:255)

**Filter Parameters:**
- `type` - Filter by transaction type (income/expense)
- `category_id` - Filter by category
- `user_id` - Filter by user
- `start_date` and `end_date` - Filter by date range

**Statistics Response:**
- `total_income` - Sum of all income
- `total_expense` - Sum of all expenses
- `balance` - Net balance (income - expense)
- `expenses_by_category` - Breakdown by expense categories
- `income_by_category` - Breakdown by income categories

## Frontend Views

The application includes a modern, responsive UI built with Alpine.js and Tailwind CSS:

### Dashboard (`/dashboard`)
- Overview of total income, expenses, and balance
- Visual breakdown of expenses and income by category
- Quick action buttons for adding transactions
- Real-time statistics from API

### Categories (`/categories`)
- Grid view of all categories with color coding
- Filter by income/expense type
- Create, edit, and delete categories
- Modal-based forms with validation

### Transactions (`/expenses`)
- Paginated table of all transactions
- Advanced filtering (type, category, date range)
- Create, edit, and delete transactions
- Modal-based forms with category filtering
- Color-coded transaction types

## Models and Relationships

### Category Model
- `id` - Primary key
- `name` - Category name
- `type` - Category type (income/expense)
- `color` - Hex color code
- `timestamps`

**Relationships:**
- `hasMany(Expense::class)` - Categories have many expenses

**Scopes:**
- `income()` - Filter income categories
- `expense()` - Filter expense categories

### Expense Model
- `id` - Primary key
- `user_id` - Foreign key to users table
- `category_id` - Foreign key to categories table
- `amount` - Transaction amount
- `type` - Transaction type (income/expense)
- `description` - Optional description
- `date` - Transaction date
- `payment_method` - Payment method used
- `reference` - Optional reference number
- `timestamps`

**Relationships:**
- `belongsTo(User::class)` - Expense belongs to a user
- `belongsTo(Category::class)` - Expense belongs to a category

**Scopes:**
- `income()` - Filter income transactions
- `expense()` - Filter expense transactions
- `forUser($userId)` - Filter by user
- `inDateRange($start, $end)` - Filter by date range

## Testing

Generate test data using factories:

```php
// Create categories
Category::factory()->income()->count(5)->create();
Category::factory()->expense()->count(10)->create();

// Create expenses
Expense::factory()->income()->count(20)->create();
Expense::factory()->expense()->count(50)->create();
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
