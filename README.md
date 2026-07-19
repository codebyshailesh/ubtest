# NeighbourShed

NeighbourShed is a small web application for sharing tools within a neighbourhood. People can register as a **tool owner** or a **renter**, log in, and browse verified tools near their registered address — borrowing them for a chosen date range and paying the owner cash on delivery when the tool changes hands.

This project follows the same structure and auth approach as the TutorFinder template it was built from.

## What this project includes

- A landing page with a location- and category-filterable tool listing
- Registration (tool owner or renter role) with a **required address**, since tools are shown based on the renter's location
- Login / logout with cookie-based auth tokens
- Tool owner dashboard: list tools, edit/delete listings, confirm or reject borrow requests
- Renter dashboard: request a tool for a start/end date range, track and cancel requests
- Admin dashboard: verify (approve/reject) new and edited tool listings, browse users
- A MySQL schema for users, tools, bookings, and auth tokens
- Cash-on-delivery is the only supported payment method — there is no online payment integration

## Actors

- **tool_owner** — lists tools for others to borrow. New listings (and edits to existing ones) start as `pending` until an admin verifies them.
- **renter** — browses approved tools near their address and requests to borrow one for a date range.
- **admin** — approves or rejects pending tool listings and can review the user directory. A seed admin account is created by the schema (see below).

## Project structure

- `public_html/index.php` — landing page with location/category filters over approved tools
- `public_html/login.php` / `register.php` / `logout.php` — auth pages
- `public_html/tool.php` — tool detail page with the borrow-date booking form
- `public_html/dashboard.php` — redirects a logged-in user to their role's dashboard
- `public_html/owner/` — tool owner dashboard, add/edit tool forms
- `public_html/renter/` — renter dashboard (booking history)
- `public_html/admin/` — admin dashboard, tool verification queue, user directory
- `public_html/api/auth/` — register, login, logout endpoints
- `public_html/api/tools/` — create, update, delete endpoints (owner-only)
- `public_html/api/bookings/` — create, update_status endpoints
- `public_html/api/admin/verify_tool.php` — approve/reject endpoint
- `public_html/includes/db.php` — database connection config
- `public_html/includes/auth_middleware.php` — token generation, auth/role guards
- `public_html/includes/functions.php` — shared helpers (formatting, validation)
- `public_html/assets/css/style.css` — dark teal design system
- `database/schema.sql` — database schema + seed admin account

## Requirements

- PHP 8+
- MySQL or MariaDB
- A web server such as Apache, Nginx, or a hosting platform like InfinityFree

## Setup

1. Create a MySQL database.
2. Import the SQL file:

   ```bash
   mysql -u YOUR_USER -p YOUR_DATABASE < database/schema.sql
   ```

3. Update the database credentials in `public_html/includes/db.php`.
4. Serve the project from the `public_html` folder, or point your web server document root to `public_html`.
5. Open the app in your browser:
   - Home: `/`
   - Register: `/register.php`
   - Login: `/login.php`
   - Admin login: `admin@neighbourshed.test` / `Admin@123` (change this password immediately after import)

## Authentication notes

The authentication flow uses:

- password hashing with PHP's `password_hash` / `password_verify`
- cookie-based auth tokens stored in the database (`auth_tokens` table)
- token expiration after 7 days
- a `logout.php` endpoint that invalidates the token and clears the cookie
- role guards (`require_role()` / `api_require_role()`) protecting owner, renter, and admin pages/endpoints

## Booking rules

- A renter picks a **start date** and **end date** on a tool's page; the total is calculated as `days × daily_rate` and shown up front.
- The server rejects bookings that overlap an existing `pending` or `confirmed` booking for the same tool.
- **Payment is cash on delivery only** — there is no card/online payment step. The renter pays the owner in person when picking up the tool.
- New bookings start as `pending`. The tool owner can `confirm` or `reject` them; the renter can `cancel` while still `pending`; the owner marks a booking `completed` once the tool is returned.

## Location matching

Every user must supply a full address (street, city, state, postal code) at registration — this isn't optional, because the homepage and search filter tools by the renter's city/neighbourhood rather than showing every listing platform-wide. Tool owners can set a different pickup address per tool if it differs from their own.

## Data model

- **users** — id, name, email, password_hash, role (`tool_owner`, `renter`, or `admin`), phone, address_line, city, state, postal_code, created_at
- **tools** — id, owner_id, name, description, category, daily_rate, deposit_amount, address_line/city/state/postal_code (pickup location), status (`pending`/`approved`/`rejected`), admin_notes, created_at
- **bookings** — id, tool_id, renter_id, start_date, end_date, total_days, total_price, payment_method (always `cash_on_delivery`), status (`pending`/`confirmed`/`rejected`/`cancelled`/`completed`), created_at
- **auth_tokens** — token, expiry, linked to a user id

## Notes

This repository currently contains the authentication foundation, tool listing + admin verification, and date-range booking with cash-on-delivery. Messaging between owners and renters, ratings/reviews, and photo uploads for tools are not yet implemented.

## License

This project is provided as a simple demo/example application/template.
