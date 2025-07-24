# Slim Simple Site Skeleton

A lightweight, reusable Slim 4 + Twig boilerplate for static company or portfolio websites (4–7 pages). Ideal for fast deployment of simple informational sites like:

- Company Homepage
- About Us
- Mission / Vision
- Contact Page
- Team / Partners
- Impact / Services

---

## Features

-  Slim 4 routing
-  Twig templating
-  Clean layout with partials
-  Custom 404 & 500 error pages
-  No session/authentication overhead
-  Fast and easy to clone for new websites
-  A `slim` cli command for ease in creating or removing pages and serving the site

## 🛠 Slim CLI

Easily scaffold pages, partials, or run the local server using the built-in CLI tool:

```bash
# Create a new page (e.g., /about)
php slim make:page about

# Remove a page
php slim remove:page about

# Create a new Twig partial (e.g., footer.twig in /partials)
php slim make:partial footer

# Start the development server at http://localhost:8000
php slim serve
````



---

## Project Structure

```

company-site/
├── public/
│   └── index.php          # Entry point
├── templates/
│   ├── layout.twig        # Base layout
│   ├── partials/
│   │   ├── header.twig
│   │   └── footer.twig
│   ├── pages/
│   │   ├── home.twig
│   │   ├── about.twig
│   │   └── ...
│   └── errors/
│       ├── 404.twig
│       └── 500.twig
├── routes/
│   └── web.php            # Route definitions
├── bootstrap.php          # App + middleware setup
├── composer.json
├── slim                   # a simple commandline interface
└── .htaccess              # Optional (Apache friendly URLs)


````

---

## Setup Instructions

### 1. Clone This Skeleton

```bash
git clone https://github.com/wanjaswilly/slim-simple-site-skeleton.git
cd slim-simple-site-skeleton
````

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Start Development Server

```bash
php slim serve
```

Now open [http://localhost:8000](http://localhost:8000) in your browser.

---

## Adding New Pages

To create a page and define its route in `routes/web.php` you call the slim command:

```php
 php slim make:page yourpagename
```

---

## Error Pages

* `templates/errors/404.twig` handles Not Found errors
* `templates/errors/500.twig` handles general server errors

These are rendered automatically via middleware defined in `bootstrap.php`.

---

## 🔗 License

MIT License © 2025 Wilson Wanja



---
