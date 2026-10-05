# Blog Site (PHP + MySQL / PDO)

Simple blog with session-based authentication.

## Setup (XAMPP / any PHP 8+ + MySQL)

1. Copy this folder into `htdocs` (e.g. `C:\xampp\htdocs\blog_site`).
2. Start Apache + MySQL, open phpMyAdmin → **Import** → choose `database.sql`
   (this creates the `blog_site` database and the `users`, `posts`, `comments` tables).
3. Check the credentials in `db.php` (defaults: user `root`, empty password).
4. Visit `http://localhost/blog_site/register.php`.

## Files

| File | Purpose |

|------|---------|
| `database.sql` | `blog_site` schema (AUTO_INCREMENT PKs, UNIQUE email, FKs with ON DELETE CASCADE) |
| `db.php` | DB config + the shared `$pdo` instance (loaded with `require`) |
| `includes/helpers.php` | session start, auth guards, CSRF, flash messages, `e()` escaping |
| `includes/header.php`, `footer.php` | shared layout |
| `register.php`, `login.php` | **guest-only** pages |
| `logout.php` | destroys the session (POST + CSRF) |
| `index.php` | news feed (newest first), new post, add comment |
| `post_edit.php`, `post_delete.php` | owner-only post changes |
| `comment_edit.php`, `comment_delete.php` | owner-only comment changes |
| `assets/js/validation.js` | client-side validation |
| `assets/css/style.css` | styling |
